<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Item Analysis Service — Classic Test Theory (CTT).
 *
 * Menghitung untuk setiap butir soal pada exam tertentu:
 *   - Tingkat Kesukaran (Difficulty Index / P)        : proporsi siswa yang menjawab benar
 *   - Daya Pembeda (Discrimination Index / D)         : selisih proporsi kelompok atas vs bawah
 *   - Distractor Analysis                             : sebaran pilihan salah (PG)
 *   - Status kelayakan butir (kombinasi P & D)
 *
 * Hanya menganalisis soal yang auto-gradable (PG, PG-Kompleks, B/S, Isian, Jodohkan, Urutan).
 * Soal essai di-skip (memerlukan penilaian manual).
 */
class ItemAnalysisService
{
    public const CATEGORY_DIFFICULTY = [
        'mudah'      => ['min' => 0.70, 'max' => 1.00, 'label' => 'Mudah'],
        'sedang'     => ['min' => 0.30, 'max' => 0.70, 'label' => 'Sedang'],
        'sukar'      => ['min' => 0.00, 'max' => 0.30, 'label' => 'Sukar'],
    ];

    public const CATEGORY_DISCRIMINATION = [
        'sangat_baik' => ['min' => 0.40, 'max' => 1.00, 'label' => 'Sangat Baik',  'quality' => '★'],
        'baik'        => ['min' => 0.30, 'max' => 0.40, 'label' => 'Baik',         'quality' => '✓'],
        'cukup'       => ['min' => 0.20, 'max' => 0.30, 'label' => 'Cukup',        'quality' => '~'],
        'jelek'       => ['min' => 0.00, 'max' => 0.20, 'label' => 'Jelek',        'quality' => '✗'],
        'negatif'     => ['min' => -1.0, 'max' => 0.00, 'label' => 'Negatif',      'quality' => '⚠'],
    ];

    /**
     * Compute full item analysis for an exam.
     *
     * @return array{summary: array, items: Collection<int, array>}
     */
    public function analyze(Exam $exam, ?int $sessionId = null): array
    {
        $attemptsQuery = ExamAttempt::query()
            ->where('exam_id', $exam->id)
            ->where('status', ExamAttempt::STATUS_SUBMITTED);

        if ($sessionId) {
            $attemptsQuery->where('exam_session_id', $sessionId);
        }

        $attempts = $attemptsQuery->with(['student', 'answers'])->get();

        if ($attempts->isEmpty()) {
            return [
                'summary' => $this->emptySummary(),
                'items'   => collect(),
                'attempts_count' => 0,
            ];
        }

        // Group attempts by total score, then split into upper & lower 27% (Kelly's criterion).
        $sorted = $attempts->sortByDesc('score')->values();
        $count  = $sorted->count();
        $groupSize = (int) floor($count * 0.27);
        if ($groupSize < 1) {
            $groupSize = (int) max(1, ceil($count / 2));
        }

        $upperGroup = $sorted->take($groupSize)->pluck('id')->all();
        $lowerGroup = $sorted->slice($count - $groupSize)->pluck('id')->all();

        $questions = $exam->questions()->with('options')->get();

        $items = $questions->map(function (Question $q) use ($attempts, $upperGroup, $lowerGroup) {
            return $this->analyzeQuestion($q, $attempts, $upperGroup, $lowerGroup);
        });

        $summary = $this->buildSummary($items, $attempts, $count);

        return [
            'summary' => $summary,
            'items'   => $items,
            'attempts_count' => $count,
            'group_size'     => $groupSize,
        ];
    }

    /**
     * Analyze a single question.
     */
    public function analyzeQuestion(
        Question $question,
        Collection $attempts,
        array $upperGroupIds,
        array $lowerGroupIds
    ): array {
        $answers = ExamAnswer::whereIn('exam_attempt_id', $attempts->pluck('id'))
            ->where('question_id', $question->id)
            ->get();

        $totalRespondents = $answers->count();
        $correctCount     = $answers->where('is_correct', true)->count();

        // Tingkat kesukaran (P) — proporsi yang menjawab benar
        $p = $totalRespondents > 0 ? round($correctCount / $totalRespondents, 4) : 0.0;

        // Daya pembeda (D) — selisih proporsi benar di kelompok atas vs bawah
        $upperAnswers = $answers->whereIn('exam_attempt_id', $upperGroupIds);
        $lowerAnswers = $answers->whereIn('exam_attempt_id', $lowerGroupIds);
        $uGroupSize   = count($upperGroupIds);
        $lGroupSize   = count($lowerGroupIds);

        $pu = $uGroupSize > 0 ? round($upperAnswers->where('is_correct', true)->count() / $uGroupSize, 4) : 0.0;
        $pl = $lGroupSize > 0 ? round($lowerAnswers->where('is_correct', true)->count() / $lGroupSize, 4) : 0.0;
        $d  = round($pu - $pl, 4);

        // Distractor analysis (PG only)
        $distractors = [];
        if (in_array($question->type, [Question::TYPE_MULTIPLE_CHOICE, Question::TYPE_TRUE_FALSE], true)) {
            foreach ($question->options as $opt) {
                $picked = $answers->filter(function ($a) use ($opt) {
                    $data = $a->answer_data ?? [];
                    return in_array($opt->id, $data, true) || (isset($data['option_id']) && $data['option_id'] === $opt->id);
                })->count();
                $distractors[] = [
                    'option_id'   => $opt->id,
                    'label'       => $opt->label,
                    'content'     => $opt->content,
                    'is_correct'  => (bool) $opt->is_correct,
                    'picked'      => $picked,
                    'pct_upper'   => $uGroupSize > 0 ? round($upperAnswers->filter(fn($a) => in_array($opt->id, (array)($a->answer_data ?? []), true))->count() / $uGroupSize, 4) : 0.0,
                    'pct_lower'   => $lGroupSize > 0 ? round($lowerAnswers->filter(fn($a) => in_array($opt->id, (array)($a->answer_data ?? []), true))->count() / $lGroupSize, 4) : 0.0,
                ];
            }
        }

        return [
            'question_id'        => $question->id,
            'question_content'   => $question->content,
            'question_type'      => $question->type,
            'question_type_label'=> $question->type_label,
            'default_score'      => (float) $question->pivot->score ?? (float) $question->default_score,
            'total_respondents'  => $totalRespondents,
            'correct_count'      => $correctCount,
            'difficulty_p'       => $p,
            'difficulty_label'   => $this->difficultyCategory($p)['label'],
            'discrimination_d'   => $d,
            'discrimination_label' => $this->discriminationCategory($d)['label'],
            'discrimination_quality' => $this->discriminationCategory($d)['quality'],
            'pu'                 => $pu,
            'pl'                 => $pl,
            'status'             => $this->itemStatus($p, $d),
            'status_label'       => $this->itemStatusLabel($p, $d),
            'distractors'        => $distractors,
            'is_auto_gradable'   => $question->isAutoGradable(),
        ];
    }

    public function difficultyCategory(float $p): array
    {
        foreach (self::CATEGORY_DIFFICULTY as $key => $cat) {
            if ($p >= $cat['min'] && $p < $cat['max'] || ($p === 1.0 && $key === 'mudah')) {
                return ['key' => $key] + $cat;
            }
        }
        return ['key' => 'sukar'] + self::CATEGORY_DIFFICULTY['sukar'];
    }

    public function discriminationCategory(float $d): array
    {
        if ($d < 0) {
            return ['key' => 'negatif'] + self::CATEGORY_DISCRIMINATION['negatif'];
        }
        if ($d < 0.20) {
            return ['key' => 'jelek'] + self::CATEGORY_DISCRIMINATION['jelek'];
        }
        if ($d < 0.30) {
            return ['key' => 'cukup'] + self::CATEGORY_DISCRIMINATION['cukup'];
        }
        if ($d < 0.40) {
            return ['key' => 'baik'] + self::CATEGORY_DISCRIMINATION['baik'];
        }
        return ['key' => 'sangat_baik'] + self::CATEGORY_DISCRIMINATION['sangat_baik'];
    }

    /**
     * Status kelayakan butir (gabungan P & D).
     *  - diterima      : P 0.30-0.70, D >= 0.30
     *  - direvisi      : di luar rentang P ideal ATAU D 0.20-0.30
     *  - ditolak       : D < 0
     */
    public function itemStatus(float $p, float $d): string
    {
        if ($d < 0) {
            return 'ditolak';
        }
        if ($d < 0.20) {
            return 'ditolak';
        }
        if ($d < 0.30 || $p < 0.30 || $p > 0.85) {
            return 'direvisi';
        }
        return 'diterima';
    }

    public function itemStatusLabel(float $p, float $d): string
    {
        return match ($this->itemStatus($p, $d)) {
            'diterima' => 'Diterima',
            'direvisi' => 'Perlu Revisi',
            'ditolak'  => 'Ditolak / Ganti',
        };
    }

    /**
     * Build summary statistics over the whole exam.
     */
    protected function buildSummary(Collection $items, Collection $attempts, int $count): array
    {
        $autoItems = $items->where('is_auto_gradable', true);

        $avgP = $autoItems->avg('difficulty_p');
        $avgD = $autoItems->avg('discrimination_d');

        $distribution = [
            'diterima' => $autoItems->where('status', 'diterima')->count(),
            'direvisi' => $autoItems->where('status', 'direvisi')->count(),
            'ditolak'  => $autoItems->where('status', 'ditolak')->count(),
        ];

        $difficultyDist = [
            'mudah'  => $autoItems->filter(fn($i) => ($this->difficultyCategory($i['difficulty_p'])['key'] ?? '') === 'mudah')->count(),
            'sedang' => $autoItems->filter(fn($i) => ($this->difficultyCategory($i['difficulty_p'])['key'] ?? '') === 'sedang')->count(),
            'sukar'  => $autoItems->filter(fn($i) => ($this->difficultyCategory($i['difficulty_p'])['key'] ?? '') === 'sukar')->count(),
        ];

        return [
            'total_attempts'    => $count,
            'mean_total_score'  => round($attempts->avg('score') ?? 0, 2),
            'highest_score'     => round($attempts->max('score') ?? 0, 2),
            'lowest_score'      => round($attempts->min('score') ?? 0, 2),
            'std_dev'           => $this->stdDev($attempts->pluck('score')->toArray()),
            'total_items'       => $items->count(),
            'auto_gradable'     => $autoItems->count(),
            'essay_items'       => $items->where('is_auto_gradable', false)->count(),
            'avg_difficulty_p'  => round($avgP ?? 0, 4),
            'avg_discrimination' => round($avgD ?? 0, 4),
            'difficulty_category' => $this->difficultyCategory((float)($avgP ?? 0))['label'] ?? '-',
            'discrimination_category' => $this->discriminationCategory((float)($avgD ?? 0))['label'] ?? '-',
            'distribution'      => $distribution,
            'difficulty_dist'   => $difficultyDist,
            'reliability_hint'  => $this->reliabilityHint((float)($avgD ?? 0)),
        ];
    }

    protected function emptySummary(): array
    {
        return [
            'total_attempts' => 0,
            'total_items'    => 0,
            'distribution'   => ['diterima' => 0, 'direvisi' => 0, 'ditolak' => 0],
            'difficulty_dist' => ['mudah' => 0, 'sedang' => 0, 'sukar' => 0],
        ];
    }

    protected function stdDev(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }
        $mean = array_sum($values) / $n;
        $sum = 0.0;
        foreach ($values as $v) {
            $sum += ($v - $mean) ** 2;
        }
        return round(sqrt($sum / ($n - 1)), 4);
    }

    /**
     * Indikator kasar reliabilitas berdasarkan rata-rata daya pembeda.
     * Bukan alpha Cronbach penuh (butuh matrix covariance), tapi indikatif.
     */
    protected function reliabilityHint(float $avgD): string
    {
        return match (true) {
            $avgD >= 0.40 => 'Sangat Andal',
            $avgD >= 0.30 => 'Andal',
            $avgD >= 0.20 => 'Cukup Andal',
            $avgD >  0.00 => 'Kurang Andal',
            default       => 'Tidak Andal',
        };
    }
}
