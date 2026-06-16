<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cetak Kartu Ujian & Daftar Hadir (PDF) untuk admin.
 *
 * - kartu-ujian/{session}     → PDF semua siswa dalam 1 sesi
 * - daftar-hadir/{session}    → PDF daftar hadir siswa + kolom tanda tangan
 * - kartu-ujian/single/{attempt} → PDF kartu 1 siswa (untuk dicetak massal 1 per 1)
 */
class PrintController extends Controller
{
    public function kartuUjian(ExamSession $session): Response
    {
        $session->load(['exam.subject', 'students.classrooms']);
        $students = $this->hydrateStudentsWithPivot($session);

        $pdf = Pdf::loadView('admin.print.kartu-ujian', [
            'session'  => $session,
            'exam'     => $session->exam,
            'students' => $students,
        ])->setPaper('a4', 'portrait');

        $filename = 'Kartu-Ujian-'.str_replace(' ', '-', $session->name).'-'.date('Ymd').'.pdf';
        return $pdf->stream($filename);
    }

    public function kartuUjianSingle(\App\Models\ExamAttempt $attempt): Response
    {
        $attempt->load(['student.user', 'student.classrooms', 'exam.subject', 'session.students']);

        $pdf = Pdf::loadView('admin.print.kartu-ujian-single', [
            'attempt' => $attempt,
            'student' => $attempt->student,
            'exam'    => $attempt->exam,
            'session' => $attempt->session,
        ])->setPaper('a5', 'portrait');

        $filename = 'Kartu-Ujian-'.$attempt->student?->name.'-'.date('Ymd').'.pdf';
        return $pdf->stream($filename);
    }

    public function daftarHadir(ExamSession $session): Response
    {
        $session->load(['exam.subject', 'students.classrooms']);
        $students = $this->hydrateStudentsWithPivot($session)
            ->sortBy(fn($s) => ($s['room_name'] ?? 'zz').'-'.str_pad((string)($s['seat_number'] ?? 999), 4, '0', STR_PAD_LEFT))
            ->values();

        $pdf = Pdf::loadView('admin.print.daftar-hadir', [
            'session'  => $session,
            'exam'     => $session->exam,
            'students' => $students,
        ])->setPaper('a4', 'portrait');

        $filename = 'Daftar-Hadir-'.str_replace(' ', '-', $session->name).'-'.date('Ymd').'.pdf';
        return $pdf->stream($filename);
    }

    public function kartuUjianBulk(ExamSession $session): Response
    {
        $session->load(['exam.subject', 'students.classrooms']);
        $students = $this->hydrateStudentsWithPivot($session)->sortBy('name')->values();

        $pdf = Pdf::loadView('admin.print.kartu-ujian-bulk', [
            'session'  => $session,
            'exam'     => $session->exam,
            'students' => $students,
        ])->setPaper('a4', 'portrait');

        $filename = 'Kartu-Ujian-Bulk-'.str_replace(' ', '-', $session->name).'-'.date('Ymd').'.pdf';
        return $pdf->stream($filename);
    }

    /**
     * Convert Collection<Student> + pivot to a Collection of arrays with
     * denormalized fields (name, nis, nisn, classroom, participant_number, seat_number, room_name).
     */
    protected function hydrateStudentsWithPivot(ExamSession $session): \Illuminate\Support\Collection
    {
        $roomIds = $session->students
            ->pluck('pivot.exam_room_id')
            ->filter()
            ->unique()
            ->all();
        $rooms = \App\Models\ExamRoom::whereIn('id', $roomIds)->get()->keyBy('id');

        return $session->students->map(function ($student) use ($rooms) {
            $roomId = $student->pivot->exam_room_id ?? null;
            return [
                'id'                 => $student->id,
                'name'               => $student->name,
                'nis'                => $student->nis,
                'nisn'               => $student->nisn,
                'classroom_name'     => $student->classrooms->first()?->name,
                'photo_path'         => $student->photo_path,
                'participant_number' => $student->pivot->participant_number,
                'seat_number'        => $student->pivot->seat_number,
                'room_id'            => $roomId,
                'room_name'          => $roomId ? ($rooms[$roomId]->name ?? null) : null,
            ];
        });
    }
}
