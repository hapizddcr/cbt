@extends('layouts.admin')
@section('title', 'Kelola Soal Ujian')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2>Soal Ujian: {{ $exam->title }}</h2>
    <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-secondary">← Kembali</a>
</div>

<div class="row">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>Soal Terpilih ({{ $exam->questions->count() }})</strong></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Soal</th><th>Tipe</th><th>Skor</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @foreach($exam->questions as $i => $q)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ Str::limit(strip_tags($q->content), 60) }}</td>
                            <td>{{ $q->type_label }}</td>
                            <td>{{ $q->pivot->score }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.exams.questions.detach', [$exam, $q->id]) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">×</button></form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><strong>Tambah Soal</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.exams.questions.attach', $exam) }}">
                    @csrf
                    <select name="question_ids[]" class="form-select" multiple size="15" required>
                        @foreach(\App\Models\Question::where('question_bank_id', $exam->question_bank_id ?? 0)->get() as $q)
                            <option value="{{ $q->id }}">{{ Str::limit($q->content, 60) }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-primary mt-2 w-100">+ Tambahkan</button>
                </form>
                <a href="{{ route('admin.questions.create') }}" class="btn btn-link mt-2 w-100">+ Buat soal baru</a>
            </div>
        </div>
    </div>
</div>
@endsection
