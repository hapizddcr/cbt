<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Services\ItemAnalysisService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ItemAnalysisController extends Controller
{
    public function __construct(protected ItemAnalysisService $analysis)
    {
    }

    /**
     * Index: list of exams that have at least one submitted attempt.
     */
    public function index(Request $request): View
    {
        $exams = Exam::query()
            ->whereHas('attempts', fn($q) => $q->where('status', 'submitted'))
            ->withCount(['attempts as submitted_attempts_count' => fn($q) => $q->where('status', 'submitted')])
            ->orderByDesc('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('admin.analysis.index', compact('exams'));
    }

    /**
     * Show detailed analysis for a specific exam.
     */
    public function show(Request $request, Exam $exam): View
    {
        $sessionId = $request->integer('session_id') ?: null;

        $sessions = ExamSession::where('exam_id', $exam->id)
            ->orderBy('start_time')
            ->get(['id', 'name', 'start_time', 'end_time']);

        $result = $this->analysis->analyze($exam, $sessionId);

        return view('admin.analysis.show', [
            'exam'     => $exam,
            'sessions' => $sessions,
            'sessionId'=> $sessionId,
            'summary'  => $result['summary'],
            'items'    => $result['items'],
            'attemptsCount' => $result['attempts_count'] ?? 0,
            'groupSize'     => $result['group_size'] ?? 0,
        ]);
    }

    /**
     * Export analysis as CSV.
     */
    public function export(Request $request, Exam $exam): Response
    {
        $sessionId = $request->integer('session_id') ?: null;
        $result    = $this->analysis->analyze($exam, $sessionId);

        $filename = 'analisis-butir-'.str_replace(' ', '-', strtolower($exam->title)).'-'.date('Ymd_His').'.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($result, $exam) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel UTF-8
            fputcsv($out, ['#', 'Tipe', 'Soal', 'P (Tingkat Kesukaran)', 'Kategori P', 'D (Daya Pembeda)', 'Kategori D', 'Status', 'Dijawab', 'Benar']);
            foreach ($result['items'] as $idx => $item) {
                fputcsv($out, [
                    $idx + 1,
                    $item['question_type_label'],
                    mb_strimwidth(strip_tags($item['question_content']), 0, 80, '…'),
                    $item['difficulty_p'],
                    $item['difficulty_label'],
                    $item['discrimination_d'],
                    $item['discrimination_label'],
                    $item['status_label'],
                    $item['total_respondents'],
                    $item['correct_count'],
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
