@extends('layouts.admin')
@section('title', 'Tambah Soal')
@section('content')
<h2>Tambah Soal</h2>
<form method="POST" action="{{ route('admin.questions.store') }}" id="questionForm">
    @csrf
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Bank Soal</label>
                    <select name="question_bank_id" class="form-select @error('question_bank_id') is-invalid @enderror" required>
                        <option value="">-- Pilih --</option>
                        @foreach($banks as $b)<option value="{{ $b->id }}" {{ old('question_bank_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>@endforeach
                    </select>
                    @error('question_bank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipe</label>
                    <select name="type" class="form-select" id="typeSelect" required>
                        @foreach(\App\Models\Question::TYPES as $key => $label)<option value="{{ $key }}" {{ $type == $key ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tingkat Kesulitan</label>
                    <select name="difficulty" class="form-select">
                        <option value="easy">Mudah</option><option value="medium" selected>Sedang</option><option value="hard">Sulit</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Soal</label>
                    <textarea name="content" class="form-control @error('content') is-invalid @enderror" rows="4" required>{{ old('content') }}</textarea>
                    <small class="text-muted">Mendukung HTML dan LaTeX ($...$)</small>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Skor Default</label>
                    <input type="number" step="0.01" name="default_score" class="form-control" value="{{ old('default_score', 10) }}" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Pembahasan (opsional)</label>
                    <input type="text" name="explanation" class="form-control" value="{{ old('explanation') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Dynamic type-specific fields --}}
    <div class="card border-0 shadow-sm mt-3" id="typeSpecificCard">
        <div class="card-header bg-white"><strong>Pilihan / Opsi</strong></div>
        <div class="card-body" id="typeSpecificArea"></div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>

<script>
const types = {
    multiple_choice: { label: 'Opsi Pilihan Ganda', type: 'mc', min: 2, hasCorrect: 'single' },
    complex_mc: { label: 'Opsi (bisa lebih dari 1 benar)', type: 'mc', min: 2, hasCorrect: 'multi' },
    true_false: { label: 'Benar / Salah', type: 'mc', min: 2, hasCorrect: 'single' },
    short_answer: { label: 'Jawaban Benar', type: 'short', min: 1 },
    matching: { label: 'Pasangan', type: 'matching', min: 2 },
    ordering: { label: 'Item Urutan', type: 'ordering', min: 2 },
    essay: { label: 'Essai (tidak perlu opsi)', type: 'essay', min: 0 },
};

function renderType() {
    const type = document.getElementById('typeSelect').value;
    const cfg = types[type];
    const area = document.getElementById('typeSpecificArea');
    if (cfg.type === 'mc') {
        let html = `<p class="text-muted small">${cfg.label}</p>`;
        for (let i = 0; i < Math.max(cfg.min, 4); i++) {
            html += `<div class="input-group mb-2">
                <span class="input-group-text">${String.fromCharCode(65+i)}</span>
                <input type="text" name="options[${i}][content]" class="form-control" placeholder="Opsi ${String.fromCharCode(65+i)}">
                <span class="input-group-text"><input type="${cfg.hasCorrect === 'multi' ? 'checkbox' : 'radio'}" name="options[${i}][is_correct]" value="1"> Benar</span>
            </div>`;
        }
        if (cfg.hasCorrect === 'single') {
            area.innerHTML = html.replace(/name="options\[\d+\]\[is_correct\]"/g, 'name="is_correct_radio"').replace(/value="1"/g, 'value="$0" onclick="this.form.querySelectorAll(\'[name=is_correct_radio]\').forEach(r=>r.checked=false);this.checked=true"');
            // simpler: rename back
        }
        area.innerHTML = html;
        // Fix radio for single
        if (cfg.hasCorrect === 'single') {
            area.querySelectorAll('[name="options[0][is_correct]"]').forEach((el, i) => {
                el.setAttribute('name', 'is_correct_radio');
                el.setAttribute('onclick', `document.querySelectorAll('[name=is_correct_radio]').forEach(r => r.checked = false); this.checked = true;`);
            });
        }
    } else if (cfg.type === 'short') {
        area.innerHTML = `<label class="form-label">Jawaban yang benar:</label><input type="text" name="options[0][content]" class="form-control" required>`;
    } else if (cfg.type === 'matching') {
        let html = '';
        for (let i = 0; i < 4; i++) {
            html += `<div class="row g-2 mb-2">
                <div class="col"><input type="text" name="matching_pairs[${i}][left]" class="form-control" placeholder="Item kiri ${i+1}"></div>
                <div class="col"><input type="text" name="matching_pairs[${i}][right]" class="form-control" placeholder="Pasangan kanan ${i+1}"></div>
            </div>`;
        }
        area.innerHTML = html;
    } else if (cfg.type === 'ordering') {
        let html = '<p class="text-muted small">Masukkan item dan urutan yang benar (1, 2, 3, ...)</p>';
        for (let i = 0; i < 4; i++) {
            html += `<div class="row g-2 mb-2">
                <div class="col"><input type="text" name="ordering_items[${i}][content]" class="form-control" placeholder="Item ${i+1}"></div>
                <div class="col-auto"><input type="number" name="ordering_items[${i}][correct_order]" class="form-control" placeholder="Urutan" value="${i+1}" min="1" style="width: 100px;"></div>
            </div>`;
        }
        area.innerHTML = html;
    } else {
        area.innerHTML = '<p class="text-muted">Tipe essai tidak memerlukan opsi tambahan. Siswa akan menulis jawaban bebas.</p>';
    }
}

document.getElementById('typeSelect').onchange = renderType;
renderType();
</script>
@endsection
