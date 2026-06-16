@extends('layouts.admin')
@section('title', $session->name)
@section('content')
<div class="d-flex justify-content-between mb-3">
    <div>
        <h2>{{ $session->name }}</h2>
        <p class="text-muted">{{ $session->exam->title ?? '-' }} · {{ $session->start_time->format('d M Y H:i') }} - {{ $session->end_time->format('H:i') }}</p>
    </div>
    <div class="btn-group">
        <a href="{{ route('admin.sessions.kartu', $session) }}" target="_blank" class="btn btn-outline-primary">
            <i class="bi bi-person-badge"></i> Kartu Ujian
        </a>
        <a href="{{ route('admin.sessions.kartu.bulk', $session) }}" target="_blank" class="btn btn-outline-primary">
            <i class="bi bi-card-text"></i> Kartu (4/Hal)
        </a>
        <a href="{{ route('admin.sessions.daftar-hadir', $session) }}" target="_blank" class="btn btn-outline-success">
            <i class="bi bi-list-check"></i> Daftar Hadir
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between">
                <strong>Token Aktif</strong>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#tokenModal">+ Generate Token</button>
            </div>
            <div class="card-body">
                @forelse($session->tokens as $t)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <code class="fs-4 fw-bold {{ $t->isValid() ? 'text-success' : 'text-muted' }}">{{ $t->token }}</code>
                            <br><small class="text-muted">Berlaku: {{ $t->issued_at->format('H:i') }} - {{ $t->expires_at->format('H:i') }} @if($t->room) · {{ $t->room->name }} @endif</small>
                        </div>
                        @if($t->is_active)
                            <form method="POST" action="{{ route('admin.sessions.tokens.deactivate', [$session, $t]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Nonaktifkan</button></form>
                        @else
                            <span class="badge bg-secondary">Off</span>
                        @endif
                    </div>
                @empty
                    <p class="text-muted text-center py-3">Belum ada token.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between">
                <strong>Ruang</strong>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#roomModal">+ Tambah Ruang</button>
            </div>
            <div class="card-body">
                @forelse($session->rooms as $r)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong>{{ $r->name }}</strong> · {{ $r->capacity }} siswa
                            @if($r->supervisor)<br><small class="text-muted">Pengawas: {{ $r->supervisor->name }}</small>@endif
                        </div>
                        <form method="POST" action="{{ route('admin.sessions.rooms.destroy', [$session, $r]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">×</button></form>
                    </div>
                @empty
                    <p class="text-muted text-center py-3">Belum ada ruang.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Token Modal --}}
<div class="modal fade" id="tokenModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.sessions.tokens.generate', $session) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5>Generate Token</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Ruang (opsional)</label>
                <select name="exam_room_id" class="form-select mb-2">
                    <option value="">-- Semua Ruang --</option>
                    @foreach($session->rooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach
                </select>
                <label class="form-label">Berlaku (menit)</label>
                <input type="number" name="duration_minutes" class="form-control" value="{{ $session->token_lifetime_minutes }}" min="1" required>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">Generate</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="roomModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.sessions.rooms.add', $session) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5>Tambah Ruang</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Nama Ruang</label>
                <input type="text" name="name" class="form-control mb-2" required>
                <label class="form-label">Kapasitas</label>
                <input type="number" name="capacity" class="form-control" min="1" value="30" required>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary">Tambah</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            </div>
        </form>
    </div>
</div>
@endsection
