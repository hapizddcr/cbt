<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GradingController extends Controller
{
    public function index(Request $request): View
    {
        $query = ExamAttempt::with(['exam', 'student.user', 'session'])
            ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_GRADED]);

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }
        if ($request->filled('graded')) {
            if ($request->graded === 'pending') {
                $query->whereHas('answers', fn($q) => $q->where('is_graded', false)->whereHas('question', fn($qq) => $qq->where('type', Question::TYPE_ESSAY)));
            }
        }
        $attempts = $query->latest('submitted_at')->paginate(20)->withQueryString();
        $exams = Exam::all();
        return view('admin.grading.index', compact('attempts', 'exams'));
    }

    public function show(ExamAttempt $attempt): View
    {
        $attempt->load(['exam.subject', 'student.user', 'answers.question.options', 'answers.question.matchingPairs']);
        return view('admin.grading.show', compact('attempt'));
    }

    public function gradeEssay(Request $request, ExamAttempt $attempt, ExamAnswer $answer): RedirectResponse
    {
        abort_unless($answer->exam_attempt_id === $attempt->id, 404);

        $data = $request->validate([
            'score' => 'required|numeric|min:0|max:' . $answer->question->default_score,
            'is_correct' => 'boolean',
            'grading_notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($attempt, $answer, $data) {
            $answer->update([
                'score' => $data['score'],
                'is_correct' => $data['is_correct'] ?? ($data['score'] > 0),
                'is_graded' => true,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
                'grading_notes' => $data['grading_notes'] ?? null,
            ]);

            $this->recalculateScore($attempt);
        });

        return back()->with('success', 'Nilai essai berhasil disimpan.');
    }

    private function recalculateScore(ExamAttempt $attempt): void
    {
        $auto = (float) $attempt->answers()->sum('score');
        $exam = $attempt->exam;
        $attempt->update([
            'score_auto' => $auto,
            'score' => $auto + (float) $attempt->score_manual,
            'percentage' => $exam->max_score > 0 ? ($auto / $exam->max_score) * 100 : 0,
            'is_passed' => $exam->passing_score !== null ? $auto >= $exam->passing_score : null,
            'status' => ExamAttempt::STATUS_GRADED,
        ]);
    }
}
