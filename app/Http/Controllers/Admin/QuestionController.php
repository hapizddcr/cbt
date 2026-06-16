<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionMatchingPair;
use App\Models\QuestionOption;
use App\Models\QuestionOrderingItem;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Question::with(['questionBank.subject', 'options', 'matchingPairs', 'orderingItems']);

        if ($request->filled('bank_id')) {
            $query->where('question_bank_id', $request->bank_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }
        if ($request->filled('search')) {
            $query->where('content', 'like', '%' . $request->search . '%');
        }

        $questions = $query->latest()->paginate(20)->withQueryString();
        $banks = QuestionBank::with('subject')->get();

        return view('admin.questions.index', compact('questions', 'banks'));
    }

    public function create(Request $request): View
    {
        $banks = QuestionBank::with('subject')->get();
        $type = $request->query('type', Question::TYPE_MULTIPLE_CHOICE);
        return view('admin.questions.create', compact('banks', 'type'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question_bank_id' => 'required|exists:question_banks,id',
            'type' => 'required|in:' . implode(',', array_keys(Question::TYPES)),
            'content' => 'required|string',
            'explanation' => 'nullable|string',
            'default_score' => 'required|numeric|min:0',
            'difficulty' => 'required|in:easy,medium,hard',
            'is_active' => 'boolean',
            // MC / True-False
            'options' => 'required_if:type,multiple_choice,complex_mc,true_false|array|min:2',
            'options.*.content' => 'required_with:options|string',
            'options.*.is_correct' => 'sometimes|boolean',
            // Matching
            'matching_pairs' => 'required_if:type,matching|array|min:2',
            'matching_pairs.*.left' => 'required_with:matching_pairs|string',
            'matching_pairs.*.right' => 'required_with:matching_pairs|string',
            // Ordering
            'ordering_items' => 'required_if:type,ordering|array|min:2',
            'ordering_items.*.content' => 'required_with:ordering_items|string',
            'ordering_items.*.correct_order' => 'required_with:ordering_items|integer',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $question = Question::create([
                    'question_bank_id' => $validated['question_bank_id'],
                    'type' => $validated['type'],
                    'content' => $validated['content'],
                    'explanation' => $validated['explanation'] ?? null,
                    'default_score' => $validated['default_score'],
                    'difficulty' => $validated['difficulty'],
                    'is_active' => $validated['is_active'] ?? true,
                ]);

                match ($question->type) {
                    Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_TRUE_FALSE =>
                        $this->saveSingleChoiceOptions($question, $validated),
                    Question::TYPE_COMPLEX_MC =>
                        $this->saveComplexMcOptions($question, $validated),
                    Question::TYPE_SHORT_ANSWER =>
                        $this->saveShortAnswer($question, $validated),
                    Question::TYPE_MATCHING =>
                        $this->saveMatchingPairs($question, $validated),
                    Question::TYPE_ORDERING =>
                        $this->saveOrderingItems($question, $validated),
                    default => null,
                };
            });

            return redirect()->route('admin.questions.index')
                ->with('success', 'Soal berhasil ditambahkan.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan soal: ' . $e->getMessage());
        }
    }

    public function edit(Question $question): View
    {
        $question->load(['options', 'matchingPairs', 'orderingItems']);
        $banks = QuestionBank::with('subject')->get();
        return view('admin.questions.edit', compact('question', 'banks'));
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $validated = $request->validate([
            'question_bank_id' => 'required|exists:question_banks,id',
            'content' => 'required|string',
            'explanation' => 'nullable|string',
            'default_score' => 'required|numeric|min:0',
            'difficulty' => 'required|in:easy,medium,hard',
            'is_active' => 'boolean',
            'options' => 'nullable|array',
            'options.*.content' => 'required_with:options|string',
            'options.*.is_correct' => 'sometimes|boolean',
            'matching_pairs' => 'nullable|array',
            'matching_pairs.*.left' => 'required_with:matching_pairs|string',
            'matching_pairs.*.right' => 'required_with:matching_pairs|string',
            'ordering_items' => 'nullable|array',
            'ordering_items.*.content' => 'required_with:ordering_items|string',
            'ordering_items.*.correct_order' => 'required_with:ordering_items|integer',
        ]);

        DB::transaction(function () use ($question, $validated) {
            $question->update([
                'question_bank_id' => $validated['question_bank_id'],
                'content' => $validated['content'],
                'explanation' => $validated['explanation'] ?? null,
                'default_score' => $validated['default_score'],
                'difficulty' => $validated['difficulty'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if (in_array($question->type, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_TRUE_FALSE, Question::TYPE_COMPLEX_MC], true)) {
                $question->options()->delete();
                if ($question->type === Question::TYPE_COMPLEX_MC) {
                    $this->saveComplexMcOptions($question, $validated);
                } else {
                    $this->saveSingleChoiceOptions($question, $validated);
                }
            }
            if ($question->type === Question::TYPE_MATCHING) {
                $question->matchingPairs()->delete();
                $this->saveMatchingPairs($question, $validated);
            }
            if ($question->type === Question::TYPE_ORDERING) {
                $question->orderingItems()->delete();
                $this->saveOrderingItems($question, $validated);
            }
            if ($question->type === Question::TYPE_SHORT_ANSWER) {
                $question->options()->delete();
                $this->saveShortAnswer($question, $validated);
            }
        });

        return redirect()->route('admin.questions.index')->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $question->delete();
        return redirect()->route('admin.questions.index')->with('success', 'Soal berhasil dihapus.');
    }

    private function saveSingleChoiceOptions(Question $question, array $data): void
    {
        foreach ($data['options'] ?? [] as $i => $opt) {
            QuestionOption::create([
                'question_id' => $question->id,
                'content' => $opt['content'],
                'is_correct' => (bool) ($opt['is_correct'] ?? false),
                'order' => $i,
            ]);
        }
    }

    private function saveComplexMcOptions(Question $question, array $data): void
    {
        foreach ($data['options'] ?? [] as $i => $opt) {
            QuestionOption::create([
                'question_id' => $question->id,
                'content' => $opt['content'],
                'is_correct' => (bool) ($opt['is_correct'] ?? false),
                'order' => $i,
            ]);
        }
    }

    private function saveShortAnswer(Question $question, array $data): void
    {
        // For short_answer, store correct text in an "option" with is_correct=true
        if (! empty($data['options'][0]['content'] ?? null)) {
            QuestionOption::create([
                'question_id' => $question->id,
                'content' => $data['options'][0]['content'],
                'is_correct' => true,
                'order' => 0,
            ]);
        }
    }

    private function saveMatchingPairs(Question $question, array $data): void
    {
        foreach ($data['matching_pairs'] ?? [] as $i => $pair) {
            QuestionMatchingPair::create([
                'question_id' => $question->id,
                'left_content' => $pair['left'],
                'right_content' => $pair['right'],
                'order' => $i,
            ]);
        }
    }

    private function saveOrderingItems(Question $question, array $data): void
    {
        $i = 0;
        foreach ($data['ordering_items'] ?? [] as $item) {
            QuestionOrderingItem::create([
                'question_id' => $question->id,
                'content' => $item['content'],
                'correct_order' => $item['correct_order'],
                'display_order' => $i++,
            ]);
        }
    }
}
