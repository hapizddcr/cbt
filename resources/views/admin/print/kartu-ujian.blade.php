<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kartu Ujian - {{ $session->name }}</title>
<style>
    @page { margin: 1.5cm; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11pt; color: #222; }
    .header { text-align: center; border-bottom: 2px solid #1a3a6c; padding-bottom: 8px; margin-bottom: 18px; }
    .header h1 { margin: 0; font-size: 14pt; color: #1a3a6c; }
    .header h2 { margin: 4px 0 0; font-size: 18pt; }
    .header p { margin: 2px 0 0; font-size: 9pt; color: #555; }
    .info-box { background: #f4f7fa; border-left: 4px solid #1a3a6c; padding: 10px 14px; margin-bottom: 16px; }
    .info-box table { width: 100%; border-collapse: collapse; }
    .info-box td { padding: 3px 6px; font-size: 10pt; }
    .info-box td:first-child { font-weight: bold; width: 32%; }
    table.kartu { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.kartu th { background: #1a3a6c; color: white; padding: 8px; text-align: left; font-size: 10pt; }
    table.kartu td { border: 1px solid #ddd; padding: 8px; font-size: 9.5pt; }
    table.kartu tr:nth-child(even) td { background: #fafbfc; }
    .pagenum { position: fixed; bottom: 0.5cm; right: 0.5cm; font-size: 8pt; color: #888; }
</style>
</head>
<body>
    <div class="header">
        <h1>SISTEM COMPUTER-BASED TEST (CBT)</h1>
        <h2>KARTU PESERTA UJIAN</h2>
        <p>Dicetak otomatis oleh sistem &middot; {{ now()->format('d F Y H:i') }}</p>
    </div>

    <div class="info-box">
        <table>
            <tr><td>Nama Ujian</td><td>: {{ $exam->title }}</td></tr>
            <tr><td>Mata Pelajaran</td><td>: {{ $exam->subject->name ?? '-' }} ({{ $exam->subject->code ?? '-' }})</td></tr>
            <tr><td>Sesi</td><td>: {{ $session->name }}</td></tr>
            <tr><td>Tanggal</td><td>: {{ $session->start_time?->format('d F Y') ?? '-' }}</td></tr>
            <tr><td>Waktu</td><td>: {{ $session->start_time?->format('H:i') ?? '-' }} &ndash; {{ $session->end_time?->format('H:i') ?? '-' }} WIB</td></tr>
            <tr><td>Durasi</td><td>: {{ $session->duration_minutes ?? $exam->duration_minutes }} menit</td></tr>
            <tr><td>Jumlah Peserta</td><td>: {{ $students->count() }} siswa</td></tr>
        </table>
    </div>

    <h3 style="margin-bottom: 6px;">Daftar Peserta</h3>
    <table class="kartu">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th>Nama Siswa</th>
                <th style="width: 80px;">NIS / NISN</th>
                <th style="width: 70px;">No. Peserta</th>
                <th style="width: 60px;">Ruang</th>
                <th style="width: 50px;">Kursi</th>
                <th style="width: 70px;">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $i => $s)
            <tr>
                <td style="text-align: center;">{{ $i + 1 }}</td>
                <td>{{ $s['name'] ?? '-' }}</td>
                <td style="text-align: center;">{{ $s['nis'] ?? '-' }}</td>
                <td style="text-align: center;">{{ $s['participant_number'] ?? '-' }}</td>
                <td style="text-align: center;">{{ $s['room_name'] ?? '-' }}</td>
                <td style="text-align: center;">{{ $s['seat_number'] ?? '-' }}</td>
                <td style="height: 26px;">&nbsp;</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 18px; font-size: 9pt; color: #666; font-style: italic;">
        <strong>Perhatian:</strong>
        <ol style="margin-top: 4px;">
            <li>Kartu ini WAJIB dibawa pada saat ujian.</li>
            <li>Siswa yang tidak membawa kartu ujian TIDAK BOLEH mengikuti ujian.</li>
            <li>Siswa diharap hadir 15 menit sebelum ujian dimulai.</li>
        </ol>
    </p>
</body>
</html>
