@extends('layouts.admin')
@section('title', 'Edit Soal')
@section('content')
<h2>Edit Soal</h2>
<form method="POST" action="{{ route('admin.questions.update', $question) }}">
    @csrf @method('PUT')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Bank Soal</label>
                    <select name="question_bank_id" class="form-select" required>
                        @foreach($banks as $b)<option value="{{ $b->id }}" {{ $question->question_bank_id == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipe (tidak dapat diubah)</label>
                    <input type="text" class="form-control" value="{{ $question->type_label }}" disabled>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kesulitan</label>
                    <select name="difficulty" class="form-select">
                        <option value="easy" {{ $question->difficulty == 'easy' ? 'selected' : '' }}>Mudah</option>
                        <option value="medium" {{ $question->difficulty == 'medium' ? 'selected' : '' }}>Sedang</option>
                        <option value="hard" {{ $question->difficulty == 'hard' ? 'selected' : '' }}>Sulit</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Soal</label>
                    <textarea name="content" class="form-control" rows="4" required>{{ $question->content }}</textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Skor</label>
                    <input type="number" step="0.01" name="default_score" class="form-control" value="{{ $question->default_score }}" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Pembahasan</label>
                    <input type="text" name="explanation" class="form-control" value="{{ $question->explanation }}">
                </div>
            </div>
        </div>
    </div>

    @if(in_array($question->type, ['multiple_choice','true_false','complex_mc']))
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white"><strong>Opsi</strong></div>
        <div class="card-body">
            @foreach($question->options as $i => $opt)
            <div class="input-group mb-2">
                <span class="input-group-text">{{ chr(65+$i) }}</span>
                <input type="text" name="options[{{ $i }}][content]" class="form-control" value="{{ $opt->content }}">
                <span class="input-group-text"><input type="checkbox" name="options[{{ $i }}][is_correct]" value="1" {{ $opt->is_correct ? 'checked' : '' }}> Benar</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div class="mt-3 d-flex gap-2">
        <button class="btn btn-primary">Simpan</button>
        <a href="{{ route('admin.questions.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@endsection
