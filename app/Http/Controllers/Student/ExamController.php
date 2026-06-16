<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Services\ExamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class ExamController extends Controller
{
    public function __construct(
        private ExamService $examService,
    ) {}

    /**
     * Form to enter token.
     */
    public function showTokenForm(): View
    {
        return view('student.exam.token');
    }

    /**
     * Validate token and start attempt.
     */
    public function startWithToken(Request $request): RedirectResponse|View
    {
        $request->validate([
            'token' => 'required|string|min:4|max:20',
        ]);

        $user = Auth::user();
        $student = $user->student;

        if (! $student) {
            return back()->with('error', 'Akun Anda belum terhubung dengan data siswa.');
        }

        try {
            $validated = $this->examService->validateToken($request->token, $student->id);
            $attempt = $this->examService->startAttempt(
                session: $validated['session'],
                student: $student,
                participantNumber: $validated['session']->students()
                    ->where('students.id', $student->id)
                    ->first()?->pivot->participant_number,
            );

            return redirect()->route('student.exam.take', $attempt);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show exam interface.
     */
    public function take(ExamAttempt $attempt): View
    {
        $this->authorizeAccess($attempt);

        if ($attempt->status === ExamAttempt::STATUS_IN_PROGRESS && $attempt->ends_at->isPast()) {
            $this->examService->submitAttempt($attempt);
            return redirect()->route('student.exam.result', $attempt)->with('info', 'Waktu habis, jawaban Anda telah disubmit otomatis.');
        }

        $exam = $attempt->exam;
        $questions = $this->examService->getQuestionsForAttempt($exam, $attempt);
        $answers = $attempt->answers()->get()->keyBy('question_id');
        $timeRemaining = $attempt->ends_at->diffInSeconds(now(), false) * -1; // positive seconds left

        return view('student.exam.take', compact('attempt', 'exam', 'questions', 'answers', 'timeRemaining'));
    }

    /**
     * Save answer via AJAX.
     */
    public function saveAnswer(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAccess($attempt);

        $request->validate([
            'question_id' => 'required|integer',
            'answer_data' => 'nullable|array',
            'essay_text' => 'nullable|string',
        ]);

        try {
            $answer = $this->examService->saveAnswer(
                attempt: $attempt,
                questionId: $request->question_id,
                data: $request->only(['answer_data', 'essay_text']),
            );

            return response()->json([
                'success' => true,
                'saved_at' => $answer->answered_at->toIso8601String(),
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * Submit the exam.
     */
    public function submit(ExamAttempt $attempt): RedirectResponse
    {
        $this->authorizeAccess($attempt);

        try {
            $attempt = $this->examService->submitAttempt($attempt);
            return redirect()->route('student.exam.result', $attempt)->with('success', 'Ujian berhasil disubmit.');
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show result.
     */
    public function result(ExamAttempt $attempt): View
    {
        $this->authorizeAccess($attempt);

        $exam = $attempt->exam;
        $answers = $attempt->answers()->with('question.options', 'question.matchingPairs')->get();

        return view('student.exam.result', compact('attempt', 'exam', 'answers'));
    }

    /**
     * Record a violation.
     */
    public function recordViolation(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $this->authorizeAccess($attempt);

        $request->validate([
            'type' => 'required|string|max:50',
            'details' => 'nullable|string',
        ]);

        $this->examService->recordViolation(
            attempt: $attempt,
            type: $request->type,
            details: $request->details,
        );

        return response()->json(['success' => true]);
    }

    private function authorizeAccess(ExamAttempt $attempt): void
    {
        $user = Auth::user();
        if (! $user->student || $user->student->id !== $attempt->student_id) {
            abort(403, 'Anda tidak memiliki akses ke ujian ini.');
        }
    }
}
