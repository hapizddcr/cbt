@extends('layouts.exam')
@section('title', $exam->title)
@push('head')
<meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
@endpush

@section('content')
<nav class="navbar bg-white shadow-sm">
    <div class="container-fluid">
        <span class="navbar-brand">{{ $exam->title }}</span>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary fs-6" id="timer">{{ floor($timeRemaining / 60) }}:{{ sprintf('%02d', $timeRemaining % 60) }}</span>
            <form method="POST" action="{{ route('student.exam.submit', $attempt) }}" id="submitForm" class="m-0">@csrf
                <button class="btn btn-success" onclick="return confirm('Yakin submit jawaban?')">Submit Ujian</button>
            </form>
        </div>
    </div>
</nav>

<div class="container-fluid py-3">
    <div class="row g-3">
        <div class="col-lg-9">
            @if($questions)
                <div class="question-card" id="questionCard">
                    @php $currentQ = $questions[0]; $currentAnswer = $answers[$currentQ->id] ?? null; @endphp
                    <div class="d-flex justify-content-between mb-3">
                        <span class="badge bg-secondary">Soal #<span id="currentNum">1</span> dari {{ count($questions) }}</span>
                        <span class="badge bg-info">{{ $currentQ->getTypeLabelAttribute() }}</span>
                    </div>
                    <div class="mb-3" id="questionContent">{!! nl2br(e($currentQ->content)) !!}</div>
                    <div id="answerArea">
                        @if($currentQ->type === 'multiple_choice' || $currentQ->type === 'true_false')
                            @foreach($currentQ->options as $i => $opt)
                                <button type="button" class="option-btn" data-qid="{{ $currentQ->id }}" data-option="{{ $opt->id }}">
                                    <span class="opt-letter">{{ chr(65 + $i) }}</span>{{ $opt->content }}
                                </button>
                            @endforeach
                        @elseif($currentQ->type === 'complex_mc')
                            <p class="text-muted small">Pilih semua yang benar:</p>
                            @foreach($currentQ->options as $i => $opt)
                                <label class="option-btn">
                                    <input type="checkbox" class="me-2" data-qid="{{ $currentQ->id }}" data-option="{{ $opt->id }}">{{ $opt->content }}
                                </label>
                            @endforeach
                        @elseif($currentQ->type === 'short_answer')
                            <input type="text" class="form-control form-control-lg" data-qid="{{ $currentQ->id }}" id="shortAnswer" placeholder="Ketik jawaban Anda...">
                        @elseif($currentQ->type === 'essay')
                            <textarea class="form-control" data-qid="{{ $currentQ->id }}" id="essayText" rows="8" placeholder="Tulis jawaban essai..."></textarea>
                        @elseif($currentQ->type === 'matching')
                            <table class="table">
                                <thead><tr><th>Item</th><th>Pasangan</th></tr></thead>
                                <tbody>
                                @foreach($currentQ->matchingPairs as $pair)
                                    <tr>
                                        <td>{{ $pair->left_content }}</td>
                                        <td>
                                            <select class="form-select" data-qid="{{ $currentQ->id }}" data-pair="{{ $pair->id }}">
                                                <option value="">-- pilih --</option>
                                                @foreach($currentQ->matchingPairs as $right)
                                                    <option value="{{ $right->id }}">{{ $right->right_content }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @elseif($currentQ->type === 'ordering')
                            <ol class="list-group" id="sortable">
                                @foreach($currentQ->orderingItems as $item)
                                    <li class="list-group-item" data-qid="{{ $currentQ->id }}" data-item="{{ $item->id }}">{{ $item->content }}</li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-3">
                    <button class="btn btn-secondary" id="prevBtn">← Sebelumnya</button>
                    <button class="btn btn-primary" id="nextBtn">Selanjutnya →</button>
                </div>
            @endif
        </div>
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><strong>Navigasi Soal</strong></div>
                <div class="card-body">
                    <div class="d-grid gap-1" style="grid-template-columns: repeat(5, 1fr); display: grid !important;" id="questionNav">
                        @foreach($questions as $i => $q)
                            <button class="nav-btn {{ $i === 0 ? 'current' : '' }}" data-idx="{{ $i }}">{{ $i + 1 }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const questions = @json(array_map(fn($q) => ['id' => $q->id, 'type' => $q->type, 'content' => $q->content, 'options' => $q->options->map->only(['id','content','is_correct']), 'pairs' => $q->matchingPairs->map->only(['id','left_content','right_content']), 'ordering' => $q->orderingItems->map->only(['id','content','correct_order'])], $questions));
let current = 0;
let answers = {};
let remainingTime = {{ $timeRemaining }};

function renderQuestion(idx) {
    current = idx;
    const q = questions[idx];
    document.getElementById('currentNum').textContent = idx + 1;
    document.getElementById('questionContent').innerHTML = q.content.replace(/\n/g, '<br>');
    let area = '';
    if (q.type === 'multiple_choice' || q.type === 'true_false') {
        area = q.options.map((o, i) => `<button type="button" class="option-btn" data-option="${o.id}"><span class="opt-letter">${String.fromCharCode(65+i)}</span>${o.content}</button>`).join('');
    } else if (q.type === 'short_answer') {
        area = `<input type="text" class="form-control form-control-lg" id="shortAnswer" placeholder="Ketik jawaban Anda...">`;
    } else if (q.type === 'essay') {
        area = `<textarea class="form-control" id="essayText" rows="8" placeholder="Tulis jawaban essai..."></textarea>`;
    }
    document.getElementById('answerArea').innerHTML = area;
    document.querySelectorAll('.nav-btn').forEach((b, i) => {
        b.classList.toggle('current', i === idx);
        b.classList.toggle('answered', !!answers[q.id]);
    });
    attachHandlers(q);
}

function attachHandlers(q) {
    document.querySelectorAll('.option-btn').forEach(btn => {
        btn.onclick = () => {
            document.querySelectorAll('.option-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
            saveAnswer(q.id, { option_id: parseInt(btn.dataset.option) });
        };
    });
    const sa = document.getElementById('shortAnswer');
    if (sa) sa.onblur = () => saveAnswer(q.id, { text: sa.value });
    const es = document.getElementById('essayText');
    if (es) es.onblur = () => saveAnswer(q.id, { essay: es.value });
}

function saveAnswer(questionId, data) {
    answers[questionId] = data;
    fetch('{{ route('student.exam.save', $attempt) }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ question_id: questionId, answer_data: data })
    });
    const idx = questions.findIndex(q => q.id === questionId);
    if (idx >= 0) document.querySelectorAll('.nav-btn')[idx]?.classList.add('answered');
}

document.getElementById('nextBtn').onclick = () => { if (current < questions.length - 1) renderQuestion(current + 1); };
document.getElementById('prevBtn').onclick = () => { if (current > 0) renderQuestion(current - 1); };
document.querySelectorAll('.nav-btn').forEach((btn, i) => btn.onclick = () => renderQuestion(i));

// Timer
setInterval(() => {
    if (remainingTime <= 0) {
        document.getElementById('submitForm').submit();
        return;
    }
    remainingTime--;
    const m = Math.floor(remainingTime / 60);
    const s = remainingTime % 60;
    document.getElementById('timer').textContent = `${m}:${String(s).padStart(2, '0')}`;
    if (remainingTime === 60) document.getElementById('timer').classList.replace('bg-primary', 'bg-danger');
    if (remainingTime === 300) document.getElementById('timer').classList.replace('bg-primary', 'bg-warning');
}, 1000);

// Violation tracking
let violations = 0;
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        violations++;
        fetch('{{ route('student.exam.violation', $attempt) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ type: 'tab_switch', details: 'Tab tidak aktif' })
        });
    }
});
</script>
@endsection
