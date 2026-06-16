<?php

namespace App\Exports;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;
use App\Services\ItemAnalysisService;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\Common\Entity\Sheet;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Multi-sheet XLSX writer untuk export hasil ujian lengkap.
 *
 * Sheet 1: Rekap Nilai Per Siswa
 * Sheet 2: Rekap Per Sesi
 * Sheet 3: Analisis Butir (jika ada submissions)
 * Sheet 4: Statistik
 *
 * Menggunakan OpenSpout (kompatibel PHP 8.5, tidak butuh PhpSpreadsheet).
 */
class ExamResultExport
{
    public function __construct(protected ItemAnalysisService $analysis)
    {
    }

    /**
     * Tulis XLSX ke path atau stream resource.
     */
    public function write(Exam $exam, $destination, ?int $sessionId = null): void
    {
        $writer = new Writer();
        $writer->openToFile($destination);

        $this->writeStudentSheet($writer, $exam, $sessionId);
        $this->writeSessionSheet($writer, $exam, $sessionId);
        $this->writeAnalysisSheet($writer, $exam, $sessionId);
        $this->writeSummarySheet($writer, $exam, $sessionId);

        $writer->close();
    }

    protected function writeStudentSheet(Writer $writer, Exam $exam, ?int $sessionId): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Rekap Nilai Siswa');

        $headerStyle = (new Style())->withFontBold(true)->withBackgroundColor('1A3A6C')->withFontColor(Color::WHITE);
        $writer->addRow(Row::fromValuesWithStyle([
            'No', 'Nama Siswa', 'NIS', 'NISN', 'Kelas',
            'Sesi', 'Ruang', 'No. Peserta', 'No. Kursi',
            'Status', 'Waktu Mulai', 'Waktu Selesai', 'Durasi (menit)',
            'Nilai', 'Persentase (%)', 'Lulus', 'Benar', 'Salah', 'Kosong',
        ], $headerStyle));

        $attempts = $this->getAttempts($exam, $sessionId)->get();
        $row = 1;
        foreach ($attempts as $attempt) {
            $student  = $attempt->student;
            $session  = $attempt->session;
            $pivot    = $session?->students->where('id', $student?->id)->first();
            $correct  = $attempt->answers->where('is_correct', true)->count();
            $wrong    = $attempt->answers->where('is_correct', false)->where('answer_data', '!=', null)->count();
            $blank    = $attempt->answers->filter(fn($a) => empty($a->answer_data) && empty($a->essay_text))->count();
            $duration = $attempt->started_at && $attempt->submitted_at
                ? (int) round($attempt->started_at->diffInMinutes($attempt->submitted_at))
                : 0;

            $writer->addRow(Row::fromValuesWithStyle([
                $row++,
                $student?->name ?? '-',
                $student?->nis ?? '-',
                $student?->nisn ?? '-',
                $student?->classrooms->first()?->name ?? '-',
                $session?->name ?? '-',
                $pivot?->pivot?->exam_room_id ? \App\Models\ExamRoom::find($pivot->pivot->exam_room_id)?->name : '-',
                $pivot?->pivot?->participant_number ?? '-',
                $pivot?->pivot?->seat_number ?? '-',
                $attempt->status,
                $attempt->started_at?->format('d M Y H:i') ?? '-',
                $attempt->submitted_at?->format('d M Y H:i') ?? '-',
                $duration,
                (float) ($attempt->score ?? 0),
                (float) ($attempt->percentage ?? 0),
                $attempt->is_passed ? 'Ya' : 'Tidak',
                $correct,
                $wrong,
                $blank,
            ]));
        }
    }

    protected function writeSessionSheet(Writer $writer, Exam $exam, ?int $sessionId): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Rekap Per Sesi');

        $headerStyle = (new Style())->withFontBold(true)->withBackgroundColor('1A3A6C')->withFontColor(Color::WHITE);
        $writer->addRow(Row::fromValuesWithStyle([
            'Sesi', 'Tanggal', 'Waktu', 'Peserta Terdaftar',
            'Mengikuti', 'Selesai', 'Lulus', 'Tidak Lulus',
            'Rata-rata Nilai', 'Tertinggi', 'Terendah',
        ], $headerStyle));

        $sessions = $exam->sessions()->with('students')->orderBy('start_time')->get();
        if ($sessionId) {
            $sessions = $sessions->where('id', $sessionId);
        }

        foreach ($sessions as $session) {
            $attempts = ExamAttempt::where('exam_id', $exam->id)
                ->where('exam_session_id', $session->id)
                ->where('status', 'submitted')
                ->get();

            $pesertaTerdaftar = $session->students->count();
            $mengikuti         = $attempts->count();
            $selesai           = $attempts->whereNotNull('submitted_at')->count();
            $lulus             = $attempts->where('is_passed', true)->count();
            $tidakLulus        = $mengikuti - $lulus;
            $avg               = $attempts->avg('score') ?? 0;
            $max               = $attempts->max('score') ?? 0;
            $min               = $attempts->min('score') ?? 0;

            $writer->addRow(Row::fromValues([
                $session->name,
                $session->start_time?->format('d M Y') ?? '-',
                $session->start_time?->format('H:i').' - '.$session->end_time?->format('H:i'),
                $pesertaTerdaftar,
                $mengikuti,
                $selesai,
                $lulus,
                $tidakLulus,
                round($avg, 2),
                round($max, 2),
                round($min, 2),
            ]));
        }
    }

    protected function writeAnalysisSheet(Writer $writer, Exam $exam, ?int $sessionId): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Analisis Butir');

        $headerStyle = (new Style())->withFontBold(true)->withBackgroundColor('1A3A6C')->withFontColor(Color::WHITE);
        $writer->addRow(Row::fromValuesWithStyle([
            'No', 'Tipe Soal', 'Soal',
            'P (Tingkat Kesukaran)', 'Kategori P',
            'D (Daya Pembeda)', 'Kategori D',
            'Status', 'Dijawab', 'Benar',
        ], $headerStyle));

        $result = $this->analysis->analyze($exam, $sessionId);
        $idx = 1;
        foreach ($result['items'] as $item) {
            $statusStyle = match ($item['status']) {
                'diterima' => (new Style())->withBackgroundColor('C6EFCE')->withFontColor('006100'),
                'direvisi' => (new Style())->withBackgroundColor('FFEB9C')->withFontColor('9C5700'),
                'ditolak'  => (new Style())->withBackgroundColor('FFC7CE')->withFontColor('9C0006'),
                default    => null,
            };
            $writer->addRow(Row::fromValuesWithStyle([
                $idx++,
                $item['question_type_label'],
                mb_strimwidth(strip_tags($item['question_content']), 0, 80, '…'),
                (float) $item['difficulty_p'],
                $item['difficulty_label'],
                (float) $item['discrimination_d'],
                $item['discrimination_label'],
                $item['status_label'],
                $item['total_respondents'],
                $item['correct_count'],
            ], $statusStyle));
        }
    }

    protected function writeSummarySheet(Writer $writer, Exam $exam, ?int $sessionId): void
    {
        $sheet = $writer->addNewSheetAndMakeItCurrent();
        $sheet->setName('Statistik');

        $titleStyle = (new Style())->withFontBold(true)->withFontSize(14);
        $labelStyle = (new Style())->withFontBold(true);
        $headerStyle = (new Style())->withFontBold(true)->withBackgroundColor('1A3A6C')->withFontColor(Color::WHITE);

        $writer->addRow(Row::fromValuesWithStyle(['STATISTIK UJIAN: '.strtoupper($exam->title)], $titleStyle));
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Mata Pelajaran', $exam->subject->name ?? '-']));
        $writer->addRow(Row::fromValues(['Tanggal Export', now()->format('d F Y H:i')]));

        $result = $this->analysis->analyze($exam, $sessionId);
        $s = $result['summary'];

        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValuesWithStyle(['Statistik Nilai'], $headerStyle));
        $writer->addRow(Row::fromValuesWithStyle(['Jumlah Siswa', $s['total_attempts'] ?? 0], $labelStyle));
        $writer->addRow(Row::fromValues(['Rata-rata', $s['mean_total_score'] ?? 0]));
        $writer->addRow(Row::fromValues(['Tertinggi',  $s['highest_score'] ?? 0]));
        $writer->addRow(Row::fromValues(['Terendah',   $s['lowest_score'] ?? 0]));
        $writer->addRow(Row::fromValues(['Standar Deviasi', $s['std_dev'] ?? 0]));

        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValuesWithStyle(['Kualitas Soal (Rata-rata)'], $headerStyle));
        $writer->addRow(Row::fromValuesWithStyle(['P (Tingkat Kesukaran)', $s['avg_difficulty_p'] ?? 0], $labelStyle));
        $writer->addRow(Row::fromValues(['Kategori P', $s['difficulty_category'] ?? '-']));
        $writer->addRow(Row::fromValuesWithStyle(['D (Daya Pembeda)', $s['avg_discrimination'] ?? 0], $labelStyle));
        $writer->addRow(Row::fromValues(['Kategori D', $s['discrimination_category'] ?? '-']));
        $writer->addRow(Row::fromValues(['Reliabilitas (indikatif)', $s['reliability_hint'] ?? '-']));

        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValuesWithStyle(['Distribusi Status Butir'], $headerStyle));
        $writer->addRow(Row::fromValuesWithStyle(['Diterima', $s['distribution']['diterima'] ?? 0], $labelStyle));
        $writer->addRow(Row::fromValues(['Perlu Revisi', $s['distribution']['direvisi'] ?? 0]));
        $writer->addRow(Row::fromValues(['Ditolak', $s['distribution']['ditolak'] ?? 0]));

        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValuesWithStyle(['Distribusi Tingkat Kesukaran'], $headerStyle));
        $writer->addRow(Row::fromValuesWithStyle(['Mudah',  $s['difficulty_dist']['mudah']  ?? 0], $labelStyle));
        $writer->addRow(Row::fromValues(['Sedang', $s['difficulty_dist']['sedang'] ?? 0]));
        $writer->addRow(Row::fromValues(['Sukar',  $s['difficulty_dist']['sukar']  ?? 0]));
    }

    protected function getAttempts(Exam $exam, ?int $sessionId)
    {
        $q = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->with(['student.classrooms', 'session.students', 'answers'])
            ->orderBy('submitted_at');

        if ($sessionId) {
            $q->where('exam_session_id', $sessionId);
        }
        return $q;
    }
}
