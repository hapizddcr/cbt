@extends('layouts.admin')
@section('title', 'Analisis: ' . $exam->title)

@section('content')
@php
    $dist = $summary['distribution'] ?? ['diterima'=>0,'direvisi'=>0,'ditolak'=>0];
    $dd   = $summary['difficulty_dist'] ?? ['mudah'=>0,'sedang'=>0,'sukar'=>0];
    $hasData = $attemptsCount > 0;
@endphp

<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
    <div>
        <h2 class="mb-1"><i class="bi bi-bar-chart-line"></i> {{ $exam->title }}</h2>
        <p class="text-muted mb-0">
            {{ $exam->subject->name ?? '-' }} &middot;
            <span class="badge bg-secondary">{{ $summary['total_items'] ?? 0 }} butir</span>
            <span class="badge bg-info">{{ $attemptsCount }} siswa</span>
            @if($sessionId)
                <span class="badge bg-warning text-dark">Sesi #{{ $sessionId }}</span>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.analysis.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('admin.exams.export', array_merge([$exam], request()->only('session_id'))) }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel"></i> Export XLSX
        </a>
        <a href="{{ route('admin.analysis.export', array_merge([$exam], request()->only('session_id'))) }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet"></i> Analisis CSV
        </a>
    </div>
</div>

{{-- Filter per sesi --}}
@if($sessions->isNotEmpty())
<form method="GET" class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 d-flex align-items-center gap-2 flex-wrap">
        <label class="form-label mb-0 small text-muted">Filter sesi:</label>
        <select name="session_id" class="form-select form-select-sm" style="max-width: 320px;" onchange="this.form.submit()">
            <option value="">— Semua Sesi ({{ $attemptsCount }} siswa) —</option>
            @foreach($sessions as $s)
                <option value="{{ $s->id }}" @selected($sessionId == $s->id)>
                    {{ $s->name }} ({{ $s->start_time?->format('d M Y H:i') }})
                </option>
            @endforeach
        </select>
        <span class="text-muted small">Kelompok 27% atas/bawah: <strong>{{ $groupSize }}</strong> siswa</span>
    </div>
</form>
@endif

@if(!$hasData)
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Belum ada submission untuk dianalisis.
    </div>
@else

{{-- Summary cards --}}
<div class="row g-3 mb-3">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Rata-rata Nilai</div>
                <div class="fs-3 fw-bold">{{ $summary['mean_total_score'] ?? 0 }}</div>
                <div class="small text-muted">Std dev: {{ $summary['std_dev'] ?? 0 }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tingkat Kesukaran (P)</div>
                <div class="fs-3 fw-bold">{{ number_format($summary['avg_difficulty_p'] ?? 0, 2) }}</div>
                <div class="small text-muted">{{ $summary['difficulty_category'] ?? '-' }} (ideal 0.30-0.70)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Daya Pembeda (D)</div>
                <div class="fs-3 fw-bold">{{ number_format($summary['avg_discrimination'] ?? 0, 2) }}</div>
                <div class="small text-muted">{{ $summary['discrimination_category'] ?? '-' }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Reliabilitas (indikatif)</div>
                <div class="fs-4 fw-bold">{{ $summary['reliability_hint'] ?? '-' }}</div>
                <div class="small text-muted">berdasarkan rata-rata D</div>
            </div>
        </div>
    </div>
</div>

{{-- Distribution row --}}
<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title">Status Kelayakan Butir</h6>
                <div class="d-flex gap-3 align-items-center flex-wrap">
                    <span class="badge bg-success p-2">Diterima: {{ $dist['diterima'] }}</span>
                    <span class="badge bg-warning text-dark p-2">Perlu Revisi: {{ $dist['direvisi'] }}</span>
                    <span class="badge bg-danger p-2">Ditolak/Ganti: {{ $dist['ditolak'] }}</span>
                </div>
                <div class="progress mt-2" style="height: 8px;">
                    @php $t = max(1, ($dist['diterima'] + $dist['direvisi'] + $dist['ditolak'])); @endphp
                    <div class="progress-bar bg-success" style="width: {{ ($dist['diterima'] / $t) * 100 }}%"></div>
                    <div class="progress-bar bg-warning" style="width: {{ ($dist['direvisi'] / $t) * 100 }}%"></div>
                    <div class="progress-bar bg-danger"  style="width: {{ ($dist['ditolak']  / $t) * 100 }}%"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="card-title">Distribusi Tingkat Kesukaran</h6>
                <div class="d-flex gap-3 align-items-center flex-wrap">
                    <span class="badge bg-info p-2">Mudah: {{ $dd['mudah'] }}</span>
                    <span class="badge bg-primary p-2">Sedang: {{ $dd['sedang'] }}</span>
                    <span class="badge bg-dark p-2">Sukar: {{ $dd['sukar'] }}</span>
                </div>
                <div class="progress mt-2" style="height: 8px;">
                    @php $u = max(1, ($dd['mudah'] + $dd['sedang'] + $dd['sukar'])); @endphp
                    <div class="progress-bar bg-info"    style="width: {{ ($dd['mudah']  / $u) * 100 }}%"></div>
                    <div class="progress-bar bg-primary" style="width: {{ ($dd['sedang'] / $u) * 100 }}%"></div>
                    <div class="progress-bar bg-dark"    style="width: {{ ($dd['sukar']  / $u) * 100 }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Items table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Rincian Per Butir</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Soal</th>
                    <th>Tipe</th>
                    <th class="text-center">P</th>
                    <th>Kategori P</th>
                    <th class="text-center">D</th>
                    <th>Kategori D</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Benar / N</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <div class="text-truncate" style="max-width: 380px;" title="{{ strip_tags($item['question_content']) }}">
                            {!! $item['question_content'] !!}
                        </div>
                    </td>
                    <td><span class="badge bg-light text-dark">{{ $item['question_type_label'] }}</span></td>
                    <td class="text-center fw-bold">{{ number_format($item['difficulty_p'], 2) }}</td>
                    <td>
                        @php
                            $pKey = match(true) {
                                $item['difficulty_p'] >= 0.70 => 'mudah',
                                $item['difficulty_p'] >= 0.30 => 'sedang',
                                default => 'sukar',
                            };
                            $pClass = match($pKey) { 'mudah' => 'info', 'sedang' => 'primary', default => 'dark' };
                        @endphp
                        <span class="badge bg-{{ $pClass }}">{{ $item['difficulty_label'] }}</span>
                    </td>
                    <td class="text-center fw-bold">{{ number_format($item['discrimination_d'], 2) }}</td>
                    <td>
                        @php
                            $dKey = match(true) {
                                $item['discrimination_d'] >= 0.40 => 'sangat_baik',
                                $item['discrimination_d'] >= 0.30 => 'baik',
                                $item['discrimination_d'] >= 0.20 => 'cukup',
                                $item['discrimination_d'] >= 0.00 => 'jelek',
                                default => 'negatif',
                            };
                            $dClass = match($dKey) {
                                'sangat_baik' => 'success',
                                'baik'        => 'success',
                                'cukup'       => 'warning',
                                'jelek'       => 'secondary',
                                'negatif'     => 'danger',
                            };
                        @endphp
                        <span class="badge bg-{{ $dClass }}">{{ $item['discrimination_quality'] }} {{ $item['discrimination_label'] }}</span>
                    </td>
                    <td class="text-center">
                        @php
                            $sClass = match($item['status']) {
                                'diterima' => 'success',
                                'direvisi' => 'warning',
                                'ditolak'  => 'danger',
                                default    => 'secondary',
                            };
                        @endphp
                        <span class="badge bg-{{ $sClass }}">{{ $item['status_label'] }}</span>
                    </td>
                    <td class="text-center small text-muted">
                        {{ $item['correct_count'] }} / {{ $item['total_respondents'] }}
                    </td>
                </tr>
                @if(!empty($item['distractors']))
                <tr class="table-light">
                    <td></td>
                    <td colspan="8" class="ps-4 small">
                        <strong>Analisis Distractor:</strong>
                        @foreach($item['distractors'] as $d)
                            <span class="badge bg-{{ $d['is_correct'] ? 'success' : 'light text-dark border' }} me-1" title="Pilih: {{ $d['picked'] }} (atas: {{ round($d['pct_upper']*100) }}%, bawah: {{ round($d['pct_lower']*100) }}%)">
                                {{ $d['label'] }}. {{ Str::limit($d['content'], 40) }}
                                @if(!$d['is_correct'])
                                    <span class="text-muted">→{{ $d['picked'] }}</span>
                                @endif
                            </span>
                        @endforeach
                    </td>
                </tr>
                @endif
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-light border mt-3 small">
    <strong><i class="bi bi-info-circle"></i> Catatan Metodologi:</strong>
    <ul class="mb-0">
        <li><strong>Tingkat Kesukaran (P)</strong>: proporsi siswa yang menjawab benar. Ideal: 0.30-0.70 (sedang).</li>
        <li><strong>Daya Pembeda (D)</strong>: selisih proporsi benar di kelompok 27% atas vs 27% bawah (berdasarkan skor total). Ideal: ≥ 0.30.</li>
        <li><strong>Status</strong>: <span class="badge bg-success">Diterima</span> = D ≥ 0.30 & P 0.30-0.85;
            <span class="badge bg-warning text-dark">Revisi</span> = D 0.20-0.30 atau P di luar rentang;
            <span class="badge bg-danger">Tolak</span> = D &lt; 0.20 (soal tidak membedakan siswa).</li>
        <li>Soal essai di-skip (memerlukan penilaian manual).</li>
    </ul>
</div>

@endif
@endsection
