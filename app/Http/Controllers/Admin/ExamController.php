<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamController extends Controller
{
    public function index(Request $request): View
    {
        $query = Exam::with(['subject', 'creator']);
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        $exams = $query->latest()->paginate(15)->withQueryString();
        $subjects = Subject::all();
        return view('admin.exams.index', compact('exams', 'subjects'));
    }

    public function create(): View
    {
        $subjects = Subject::all();
        return view('admin.exams.create', compact('subjects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:daily,midterm,final,tryout,quiz',
            'duration_minutes' => 'required|integer|min:1|max:600',
            'max_score' => 'required|numeric|min:0',
            'passing_score' => 'required|numeric|min:0',
            'max_attempts' => 'required|integer|min:1',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_result' => 'boolean',
            'show_answer' => 'boolean',
            'allow_review' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['creator_id'] = auth()->id();
        $exam = Exam::create($validated);

        return redirect()->route('admin.exams.show', $exam)->with('success', 'Ujian berhasil dibuat.');
    }

    public function show(Exam $exam): View
    {
        $exam->load(['subject', 'sessions.rooms', 'questions']);
        return view('admin.exams.show', compact('exam'));
    }

    public function edit(Exam $exam): View
    {
        $subjects = Subject::all();
        return view('admin.exams.edit', compact('exam', 'subjects'));
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:daily,midterm,final,tryout,quiz',
            'duration_minutes' => 'required|integer|min:1|max:600',
            'max_score' => 'required|numeric|min:0',
            'passing_score' => 'required|numeric|min:0',
            'max_attempts' => 'required|integer|min:1',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_result' => 'boolean',
            'show_answer' => 'boolean',
            'allow_review' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $exam->update($validated);
        return redirect()->route('admin.exams.show', $exam)->with('success', 'Ujian berhasil diperbarui.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $exam->delete();
        return redirect()->route('admin.exams.index')->with('success', 'Ujian berhasil dihapus.');
    }

    /**
     * Manage questions attached to exam.
     */
    public function questions(Exam $exam): View
    {
        $exam->load(['questions', 'subject']);
        return view('admin.exams.questions', compact('exam'));
    }

    public function attachQuestions(Request $request, Exam $exam): RedirectResponse
    {
        $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:questions,id',
        ]);

        $order = $exam->questions()->count() + 1;
        foreach ($request->question_ids as $qid) {
            $exam->questions()->syncWithoutDetaching([
                $qid => ['order' => $order++, 'score' => 1],
            ]);
        }
        $exam->update(['total_questions' => $exam->questions()->count()]);

        return back()->with('success', 'Soal berhasil ditambahkan ke ujian.');
    }

    public function detachQuestion(Exam $exam, int $questionId): RedirectResponse
    {
        $exam->questions()->detach($questionId);
        $exam->update(['total_questions' => $exam->questions()->count()]);
        return back()->with('success', 'Soal berhasil dihapus dari ujian.');
    }
}
