<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kartu Ujian - {{ $student?->name }}</title>
<style>
    @page { margin: 1cm; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10pt; color: #222; }
    .card { border: 3px solid #1a3a6c; border-radius: 8px; padding: 12px; max-width: 100%; }
    .header { text-align: center; border-bottom: 2px solid #1a3a6c; padding-bottom: 6px; margin-bottom: 10px; }
    .header h1 { margin: 0; font-size: 11pt; color: #1a3a6c; }
    .header h2 { margin: 3px 0 0; font-size: 14pt; letter-spacing: 1px; }
    .body-grid { display: table; width: 100%; }
    .photo, .info { display: table-cell; vertical-align: top; }
    .photo { width: 90px; text-align: center; padding-right: 12px; }
    .photo .box { width: 80px; height: 100px; border: 2px dashed #1a3a6c; display: table-cell; vertical-align: middle; text-align: center; color: #888; font-size: 8pt; }
    .photo img { width: 80px; height: 100px; object-fit: cover; border: 1px solid #1a3a6c; }
    .info { font-size: 10pt; }
    .info table { border-collapse: collapse; width: 100%; }
    .info td { padding: 2px 4px; }
    .info td:first-child { font-weight: bold; width: 36%; }
    .footer { margin-top: 10px; border-top: 1px solid #ccc; padding-top: 6px; font-size: 8.5pt; }
    .signature { display: table; width: 100%; margin-top: 16px; }
    .signature div { display: table-cell; text-align: center; font-size: 9pt; }
    .signature .line { margin-top: 36px; border-top: 1px solid #333; padding-top: 2px; }
    .participant { text-align: center; margin: 8px 0; padding: 6px; background: #f4f7fa; border-radius: 4px; }
    .participant .num { font-size: 16pt; font-weight: bold; color: #1a3a6c; letter-spacing: 2px; }
</style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>SISTEM COMPUTER-BASED TEST (CBT)</h1>
            <h2>KARTU PESERTA UJIAN</h2>
        </div>

        <div class="body-grid">
            <div class="photo">
                @if($student?->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->photo_path))
                    <img src="{{ storage_path('app/public/'.$student->photo_path) }}" alt="Foto">
                @else
                    <div class="box">FOTO<br>3x4</div>
                @endif
            </div>
            <div class="info">
                <table>
                    <tr><td>Nama</td><td>: <strong>{{ $student?->name ?? '-' }}</strong></td></tr>
                    <tr><td>NIS / NISN</td><td>: {{ $student?->nis ?? '-' }} / {{ $student?->nisn ?? '-' }}</td></tr>
                    <tr><td>Kelas</td><td>: {{ $student?->classrooms?->first()?->name ?? '-' }}</td></tr>
                    <tr><td>Ujian</td><td>: {{ $exam->title }}</td></tr>
                    <tr><td>Mata Pelajaran</td><td>: {{ $exam->subject->name ?? '-' }}</td></tr>
                    <tr><td>Sesi</td><td>: {{ $session->name }}</td></tr>
                    <tr><td>Tanggal</td><td>: {{ $session->start_time?->format('d F Y') ?? '-' }}</td></tr>
                    <tr><td>Waktu</td><td>: {{ $session->start_time?->format('H:i') ?? '-' }} - {{ $session->end_time?->format('H:i') ?? '-' }}</td></tr>
                    <tr><td>Durasi</td><td>: {{ $session->duration_minutes ?? $exam->duration_minutes }} menit</td></tr>
                </table>
            </div>
        </div>

        @php
            $pivot = $session->students->where('id', $student?->id)->first();
        @endphp
        @if($pivot)
        <div class="participant">
            <div style="font-size: 8pt; color: #555;">NOMOR PESERTA</div>
            <div class="num">{{ $pivot->pivot->participant_number ?? str_pad((string)$pivot->id, 4, '0', STR_PAD_LEFT) }}</div>
        </div>
        @endif

        <div class="footer">
            <strong>Peraturan:</strong>
            <ol style="margin: 4px 0; padding-left: 18px;">
                <li>Hadirlah 15 menit sebelum ujian dimulai.</li>
                <li>Bawa kartu ini &amp; identitas diri saat ujian.</li>
                <li>Dilarang membawa HP, catatan, atau alat komunikasi.</li>
                <li>Pelanggaran = diskualifikasi.</li>
            </ol>
        </div>

        <div class="signature">
            <div style="width: 50%;">
                Peserta<br><div class="line">{{ $student?->name ?? '' }}</div>
            </div>
            <div style="width: 50%;">
                Pengawas<br><div class="line">( ........................... )</div>
            </div>
        </div>
    </div>
</body>
</html>
