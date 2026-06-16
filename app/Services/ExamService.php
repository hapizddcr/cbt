<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Question;
use App\Models\QuestionMatchingPair;
use App\Models\QuestionOption;
use App\Models\QuestionOrderingItem;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExamService
{
    /**
     * Validate token and session for a student.
     */
    public function validateToken(string $token, int $studentId): array
    {
        $tokenModel = ExamToken::with('session.exam', 'room')
            ->where('token', $token)
            ->where('is_active', true)
            ->first();

        if (! $tokenModel) {
            throw new RuntimeException('Token tidak valid atau tidak ditemukan.');
        }

        if (! $tokenModel->isValid()) {
            throw new RuntimeException('Token sudah kadaluarsa. Silakan minta token baru kepada pengawas.');
        }

        $session = $tokenModel->session;
        if (! $session->isOngoing()) {
            throw new RuntimeException('Sesi ujian belum dimulai atau sudah berakhir.');
        }

        // Check student is allocated to this session
        $allocated = $session->students()
            ->where('students.id', $studentId)
            ->exists();

        if (! $allocated) {
            throw new RuntimeException('Anda tidak terdaftar di sesi ujian ini.');
        }

        return [
            'token' => $tokenModel,
            'session' => $session,
            'exam' => $session->exam,
            'room' => $tokenModel->room,
        ];
    }

    /**
     * Start a new exam attempt for a student.
     */
    public function startAttempt(ExamSession $session, Student $student, ?string $participantNumber = null): ExamAttempt
    {
        return DB::transaction(function () use ($session, $student, $participantNumber) {
            $exam = $session->exam;

            // Check for in-progress attempt (resume) first - doesn't count against max_attempts
            $inProgress = ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
                ->where('ends_at', '>', now())
                ->first();

            if ($inProgress) {
                return $inProgress;
            }

            // Check existing completed attempts count
            $existingAttempts = ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_GRADED, ExamAttempt::STATUS_EXPIRED])
                ->count();

            if ($existingAttempts >= $exam->max_attempts) {
                throw new RuntimeException("Anda sudah mencapai batas maksimum percobaan ({$exam->max_attempts}x) untuk ujian ini.");
            }

            // Clean up expired in-progress attempts
            ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
                ->update(['status' => ExamAttempt::STATUS_EXPIRED]);

            $duration = $session->duration_minutes ?? $exam->duration_minutes;
            $now = now();
            $endsAt = (clone $now)->addMinutes($duration);

            $attempt = ExamAttempt::create([
                'exam_id' => $exam->id,
                'exam_session_id' => $session->id,
                'student_id' => $student->id,
                'participant_number' => $participantNumber,
                'status' => ExamAttempt::STATUS_IN_PROGRESS,
                'started_at' => $now,
                'ends_at' => $endsAt,
                'time_remaining_seconds' => $duration * 60,
                'ip_address' => request()->ip(),
            ]);

            // Pre-create answer rows (one per question in exam)
            $questions = $this->getQuestionsForAttempt($exam);
            foreach ($questions as $question) {
                ExamAnswer::create([
                    'exam_attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'score' => 0,
                    'is_graded' => false,
                ]);
            }

            return $attempt;
        });
    }

    /**
     * Get shuffled questions for an attempt.
     */
    public function getQuestionsForAttempt(Exam $exam, ?ExamAttempt $attempt = null): array
    {
        $query = DB::table('exam_questions')
            ->join('questions', 'questions.id', '=', 'exam_questions.question_id')
            ->where('exam_questions.exam_id', $exam->id)
            ->whereNull('questions.deleted_at')
            ->where('questions.is_active', true)
            ->orderBy('exam_questions.order')
            ->select('questions.*', 'exam_questions.order', 'exam_questions.score');

        $questions = $query->get();

        if ($exam->shuffle_questions) {
            $questions = $questions->shuffle();
        }

        // Hydrate with relations
        return $questions->map(function ($q) use ($exam) {
            $model = Question::with(['options', 'matchingPairs', 'orderingItems'])
                ->find($q->id);
            $model->exam_score = $q->score;
            if ($exam->shuffle_options && $model->options) {
                $model->setRelation('options', $model->options->shuffle());
            }
            return $model;
        })->all();
    }

    /**
     * Save/update an answer.
     */
    public function saveAnswer(ExamAttempt $attempt, int $questionId, array $data): ExamAnswer
    {
        if ($attempt->status !== ExamAttempt::STATUS_IN_PROGRESS) {
            throw new RuntimeException('Ujian sudah disubmit, tidak bisa mengubah jawaban.');
        }
        if ($attempt->ends_at->isPast()) {
            throw new RuntimeException('Waktu ujian sudah habis.');
        }

        $answer = ExamAnswer::where('exam_attempt_id', $attempt->id)
            ->where('question_id', $questionId)
            ->firstOrFail();

        $answer->update([
            'answer_data' => $data['answer_data'] ?? null,
            'essay_text' => $data['essay_text'] ?? null,
            'answered_at' => now(),
        ]);

        return $answer;
    }

    /**
     * Submit and grade an attempt.
     */
    public function submitAttempt(ExamAttempt $attempt): ExamAttempt
    {
        return DB::transaction(function () use ($attempt) {
            if ($attempt->status !== ExamAttempt::STATUS_IN_PROGRESS) {
                throw new RuntimeException('Ujian sudah disubmit sebelumnya.');
            }

            $attempt->update([
                'status' => ExamAttempt::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            $this->gradeAutoGradable($attempt);

            $attempt->refresh();
            $this->finalizeScore($attempt);

            return $attempt;
        });
    }

    /**
     * Auto-grade all auto-gradable answers.
     */
    public function gradeAutoGradable(ExamAttempt $attempt): void
    {
        $answers = $attempt->answers()->with('question.options', 'question.matchingPairs', 'question.orderingItems')->get();

        foreach ($answers as $answer) {
            $question = $answer->question;
            if (! $question || ! $question->isAutoGradable()) {
                continue;
            }
            if (! $answer->answer_data && ! $answer->essay_text) {
                continue;
            }

            $score = $this->gradeAnswer($question, $answer);
            $answer->update([
                'score' => $score,
                'is_correct' => $score > 0,
                'is_graded' => true,
                'graded_at' => now(),
            ]);
        }
    }

    /**
     * Grade a single answer (auto-gradable types).
     */
    public function gradeAnswer(Question $question, ExamAnswer $answer): float
    {
        $data = $answer->answer_data ?? [];
        $attempt = $answer->attempt;
        $maxScore = (float) DB::table('exam_questions')
            ->where('exam_id', $attempt->exam_id)
            ->where('question_id', $question->id)
            ->value('score') ?: (float) $question->default_score;

        return match ($question->type) {
            Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_TRUE_FALSE =>
                $this->gradeSingleChoice($question, $data, $maxScore),
            Question::TYPE_COMPLEX_MC =>
                $this->gradeComplexMc($question, $data, $maxScore),
            Question::TYPE_SHORT_ANSWER =>
                $this->gradeShortAnswer($question, $data, $maxScore),
            Question::TYPE_MATCHING =>
                $this->gradeMatching($question, $data, $maxScore),
            Question::TYPE_ORDERING =>
                $this->gradeOrdering($question, $data, $maxScore),
            default => 0,
        };
    }

    protected function gradeSingleChoice(Question $question, array $data, float $maxScore): float
    {
        $selected = $data['option_id'] ?? null;
        if (! $selected) {
            return 0;
        }
        $option = $question->options()->where('id', $selected)->first();
        return ($option && $option->is_correct) ? $maxScore : 0;
    }

    protected function gradeComplexMc(Question $question, array $data, float $maxScore): float
    {
        $selected = (array) ($data['option_ids'] ?? []);
        $correct = $question->correctOptions()->pluck('id')->toArray();
        sort($selected);
        sort($correct);
        return $selected === $correct ? $maxScore : 0;
    }

    protected function gradeShortAnswer(Question $question, array $data, float $maxScore): float
    {
        $answer = trim((string) ($data['text'] ?? ''));
        if ($answer === '') {
            return 0;
        }
        // Match against the option marked is_correct (the "correct answer" stored as an option)
        $correctOption = $question->options()->where('is_correct', true)->first();
        if (! $correctOption) {
            return 0;
        }
        $expected = trim(strtolower($correctOption->content));
        $given = strtolower($answer);
        return $expected === $given ? $maxScore : 0;
    }

    protected function gradeMatching(Question $question, array $data, float $maxScore): float
    {
        $pairs = $question->matchingPairs;
        if ($pairs->isEmpty()) {
            return 0;
        }
        $answers = (array) ($data['pairs'] ?? []);
        $totalPairs = $pairs->count();
        $correctCount = 0;

        foreach ($pairs as $pair) {
            $given = $answers[$pair->id] ?? null;
            // Each pair: user selects right_content for a given left_content (pair id)
            if ($given !== null && (int) $given === $pair->id) {
                $correctCount++;
            }
        }
        return ($correctCount / $totalPairs) * $maxScore;
    }

    protected function gradeOrdering(Question $question, array $data, float $maxScore): float
    {
        $items = $question->orderingItems;
        if ($items->isEmpty()) {
            return 0;
        }
        $orders = (array) ($data['orders'] ?? []);
        $totalItems = $items->count();
        $correctCount = 0;

        foreach ($items as $item) {
            if (isset($orders[$item->id]) && (int) $orders[$item->id] === $item->correct_order) {
                $correctCount++;
            }
        }
        return ($correctCount / $totalItems) * $maxScore;
    }

    /**
     * Finalize total score & pass/fail.
     */
    public function finalizeScore(ExamAttempt $attempt): void
    {
        $auto = (float) $attempt->answers()->sum('score');
        $attempt->update([
            'score_auto' => $auto,
            'score' => $auto + (float) $attempt->score_manual,
            'percentage' => $attempt->exam->max_score > 0
                ? (($auto + (float) $attempt->score_manual) / $attempt->exam->max_score) * 100
                : 0,
            'is_passed' => $attempt->exam->passing_score !== null
                ? (($auto + (float) $attempt->score_manual) >= $attempt->exam->passing_score)
                : null,
        ]);
    }

    /**
     * Generate a random token.
     */
    public function generateToken(int $length = 6): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // skip I, O, 0, 1
        $token = '';
        for ($i = 0; $i < $length; $i++) {
            $token .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $token;
    }

    /**
     * Record a violation during exam.
     */
    public function recordViolation(ExamAttempt $attempt, string $type, ?string $details = null): void
    {
        $attempt->violations()->create([
            'type' => $type,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
        $attempt->increment('violation_count');
    }
}
