<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Daftar Hadir - {{ $session->name }}</title>
<style>
    @page { margin: 1.8cm 1.5cm; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11pt; color: #222; }
    .header { text-align: center; border-bottom: 2px solid #1a3a6c; padding-bottom: 8px; margin-bottom: 16px; }
    .header h1 { margin: 0; font-size: 13pt; color: #1a3a6c; }
    .header h2 { margin: 4px 0 0; font-size: 16pt; }
    .info { display: table; width: 100%; margin-bottom: 14px; }
    .info > div { display: table-cell; vertical-align: top; }
    .info .left { width: 55%; }
    .info .right { text-align: right; }
    .info table { border-collapse: collapse; }
    .info td { padding: 2px 8px 2px 0; font-size: 10pt; }
    .info td:first-child { font-weight: bold; }
    table.daftar { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.daftar th { background: #1a3a6c; color: white; padding: 8px 6px; text-align: center; font-size: 9.5pt; border: 1px solid #1a3a6c; }
    table.daftar td { border: 1px solid #888; padding: 8px 6px; font-size: 9.5pt; }
    table.daftar tr:nth-child(even) td { background: #fafbfc; }
    table.daftar td.center { text-align: center; }
    table.daftar td.ttd { height: 36px; }
    .sign-block { display: table; width: 100%; margin-top: 28px; }
    .sign-block > div { display: table-cell; width: 50%; text-align: center; font-size: 10pt; }
    .sign-block .line { margin-top: 60px; border-top: 1px solid #333; width: 220px; display: inline-block; padding-top: 4px; }
</style>
</head>
<body>
    <div class="header">
        <h1>DAFTAR HADIR PESERTA UJIAN</h1>
        <h2>{{ $exam->title }}</h2>
    </div>

    <div class="info">
        <div class="left">
            <table>
                <tr><td>Mata Pelajaran</td><td>: {{ $exam->subject->name ?? '-' }}</td></tr>
                <tr><td>Sesi</td><td>: {{ $session->name }}</td></tr>
                <tr><td>Hari / Tanggal</td><td>: {{ $session->start_time?->translatedFormat('l, d F Y') ?? '-' }}</td></tr>
                <tr><td>Waktu</td><td>: {{ $session->start_time?->format('H:i') ?? '-' }} - {{ $session->end_time?->format('H:i') ?? '-' }} WIB</td></tr>
            </table>
        </div>
        <div class="right">
            <table style="margin-left: auto;">
                <tr><td>Durasi</td><td>: {{ $session->duration_minutes ?? $exam->duration_minutes }} menit</td></tr>
                <tr><td>Jumlah Peserta</td><td>: {{ $students->count() }} siswa</td></tr>
                <tr><td>Jumlah Hadir</td><td>: .............. siswa</td></tr>
                <tr><td>Jumlah Tidak Hadir</td><td>: .............. siswa</td></tr>
            </table>
        </div>
    </div>

    <table class="daftar">
        <thead>
            <tr>
                <th style="width: 26px;">No</th>
                <th>Nama Peserta</th>
                <th style="width: 80px;">NIS / NISN</th>
                <th style="width: 60px;">No. Peserta</th>
                <th style="width: 55px;">Ruang</th>
                <th style="width: 45px;">Kursi</th>
                <th style="width: 70px;">Jam Hadir</th>
                <th style="width: 130px;">Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($students as $i => $s)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $s['name'] ?? '-' }}</td>
                <td class="center">{{ $s['nis'] ?? '-' }}</td>
                <td class="center">{{ $s['participant_number'] ?? '-' }}</td>
                <td class="center">{{ $s['room_name'] ?? '-' }}</td>
                <td class="center">{{ $s['seat_number'] ?? '-' }}</td>
                <td class="center ttd">&nbsp;</td>
                <td class="ttd">&nbsp;</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="sign-block">
        <div>
            <div>Pengawas,</div>
            <div class="line">( ........................... )</div>
        </div>
        <div>
            <div>{{ now()->translatedFormat('l, d F Y') }}<br>Proktor,</div>
            <div class="line">( ........................... )</div>
        </div>
    </div>
</body>
</html>
