@extends('layouts.admin')
@section('title', 'Koreksi - ' . $attempt->student->name)
@section('content')
<div class="d-flex justify-content-between mb-3">
    <div>
        <h2>Koreksi: {{ $attempt->student->name }}</h2>
        <p class="text-muted">{{ $attempt->exam->title }} · {{ $attempt->exam->subject->name }}</p>
    </div>
    <div class="text-end">
        <h3>Nilai: <span class="text-primary">{{ $attempt->score ?? 0 }}</span> / {{ $attempt->exam->max_score }}</h3>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        @foreach($attempt->answers as $i => $answer)
            <div class="border-bottom py-3">
                <div class="d-flex justify-content-between">
                    <strong>Soal #{{ $i + 1 }} ({{ $answer->question->getTypeLabelAttribute() }})</strong>
                    @if($answer->is_graded)
                        <span class="badge bg-{{ $answer->is_correct ? 'success' : 'danger' }}">{{ $answer->score }}/{{ $answer->question->default_score }}</span>
                    @else
                        <span class="badge bg-warning">Belum Dinilai</span>
                    @endif
                </div>
                <div class="my-2">{!! nl2br(e($answer->question->content)) !!}</div>

                @if($answer->question->type === 'multiple_choice' || $answer->question->type === 'true_false')
                    <p class="mb-1"><strong>Jawaban siswa:</strong> 
                        @php $selected = $answer->question->options->where('id', $answer->answer_data['option_id'] ?? 0)->first(); @endphp
                        <span class="badge bg-{{ $selected?->is_correct ? 'success' : 'danger' }}">{{ $selected->content ?? '(tidak dijawab)' }}</span>
                    </p>
                @elseif($answer->question->type === 'essay')
                    <p><strong>Jawaban:</strong> {{ $answer->essay_text ?? '(kosong)' }}</p>
                    @if($answer->question->type === 'essay')
                    <form method="POST" action="{{ route('admin.grading.essay', [$attempt, $answer]) }}" class="row g-2 mt-2">
                        @csrf
                        <div class="col-auto"><input type="number" step="0.01" name="score" max="{{ $answer->question->default_score }}" class="form-control" placeholder="Skor" required></div>
                        <div class="col"><input type="text" name="grading_notes" class="form-control" placeholder="Catatan (opsional)"></div>
                        <div class="col-auto"><button class="btn btn-primary btn-sm">Simpan Nilai</button></div>
                    </form>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
