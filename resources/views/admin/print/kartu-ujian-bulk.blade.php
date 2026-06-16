<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Kartu Ujian - {{ $session->name }}</title>
<style>
    @page { margin: 1cm; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9pt; color: #222; margin: 0; }
    .header { text-align: center; border-bottom: 1.5px solid #1a3a6c; padding-bottom: 4px; margin-bottom: 10px; }
    .header h1 { margin: 0; font-size: 10pt; color: #1a3a6c; }
    .header h2 { margin: 2px 0 0; font-size: 12pt; letter-spacing: 0.5px; }
    .header p { margin: 2px 0 0; font-size: 7pt; color: #555; }
    .grid { display: block; }
    .card { display: inline-block; width: 47%; margin: 0 1% 12px; padding: 8px; border: 2px solid #1a3a6c; border-radius: 6px; vertical-align: top; }
    .card-head { text-align: center; border-bottom: 1px solid #1a3a6c; padding-bottom: 3px; margin-bottom: 5px; }
    .card-head h3 { margin: 0; font-size: 9pt; color: #1a3a6c; }
    .card-head h4 { margin: 2px 0 0; font-size: 10pt; }
    .card-body { display: table; width: 100%; }
    .photo, .info { display: table-cell; vertical-align: top; }
    .photo { width: 50px; padding-right: 6px; }
    .photo .box { width: 50px; height: 65px; border: 1.5px dashed #1a3a6c; text-align: center; vertical-align: middle; color: #888; font-size: 6pt; }
    .photo img { width: 50px; height: 65px; object-fit: cover; }
    .info { font-size: 8pt; }
    .info td { padding: 1px 3px; }
    .info td:first-child { font-weight: bold; width: 38%; }
    .participant { text-align: center; margin-top: 4px; padding: 3px; background: #f4f7fa; border-radius: 3px; }
    .participant .num { font-size: 11pt; font-weight: bold; color: #1a3a6c; letter-spacing: 2px; }
    .signature { display: table; width: 100%; margin-top: 6px; font-size: 7pt; text-align: center; }
    .signature > div { display: table-cell; }
    .signature .line { margin-top: 22px; border-top: 0.5px solid #333; padding-top: 1px; }
    .page-break { page-break-after: always; }
</style>
</head>
<body>
    <div class="header">
        <h1>SISTEM CBT &middot; KARTU PESERTA UJIAN</h1>
        <h2>{{ $exam->title }} &middot; {{ $session->name }}</h2>
        <p>
            {{ $session->start_time?->format('d F Y') }} &middot;
            {{ $session->start_time?->format('H:i') }} - {{ $session->end_time?->format('H:i') }} WIB &middot;
            {{ $session->duration_minutes ?? $exam->duration_minutes }} menit
        </p>
    </div>

    @foreach($students->chunk(4) as $chunk)
    <div class="grid">
        @foreach($chunk as $s)
            <div class="card">
                <div class="card-head">
                    <h3>KARTU PESERTA</h3>
                    <h4>{{ $s['name'] ?? '-' }}</h4>
                </div>
                <div class="card-body">
                    <div class="photo">
                        @if(!empty($s['photo_path']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($s['photo_path']))
                            <img src="{{ storage_path('app/public/'.$s['photo_path']) }}" alt="">
                        @else
                            <div class="box">FOTO 3x4</div>
                        @endif
                    </div>
                    <div class="info">
                        <table>
                            <tr><td>NIS</td><td>: {{ $s['nis'] ?? '-' }}</td></tr>
                            <tr><td>Kelas</td><td>: {{ $s['classroom_name'] ?? '-' }}</td></tr>
                            <tr><td>Mapel</td><td>: {{ $exam->subject->code ?? '-' }}</td></tr>
                            <tr><td>Ruang</td><td>: {{ $s['room_name'] ?? '-' }}</td></tr>
                            <tr><td>Kursi</td><td>: {{ $s['seat_number'] ?? '-' }}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="participant">
                    <div style="font-size: 6pt; color: #555;">NOMOR PESERTA</div>
                    <div class="num">{{ $s['participant_number'] ?? str_pad((string)($s['id'] ?? 0), 4, '0', STR_PAD_LEFT) }}</div>
                </div>
                <div class="signature">
                    <div>Peserta<div class="line">{{ $s['name'] ?? '' }}</div></div>
                    <div>Pengawas<div class="line">( ........... )</div></div>
                </div>
            </div>
        @endforeach
    </div>
    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
    @endforeach
</body>
</html>
