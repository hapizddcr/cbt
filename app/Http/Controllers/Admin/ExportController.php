<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ExamResultExport;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(protected ExamResultExport $examExport)
    {
    }

    /**
     * Export hasil ujian ke XLSX multi-sheet.
     */
    public function examResult(Request $request, Exam $exam): StreamedResponse|BinaryFileResponse
    {
        $sessionId = $request->integer('session_id') ?: null;
        $filename  = 'Hasil-Ujian-'.str_replace(' ', '-', $exam->title).'-'.date('Ymd_His').'.xlsx';

        $tmpPath = storage_path('app/exports/'.uniqid('export-').'.xlsx');
        @mkdir(dirname($tmpPath), 0775, true);

        $this->examExport->write($exam, $tmpPath, $sessionId);

        return response()
            ->download($tmpPath, $filename, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ])
            ->deleteFileAfterSend(true);
    }
}
