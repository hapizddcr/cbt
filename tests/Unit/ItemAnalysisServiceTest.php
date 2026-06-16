<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\ItemAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ItemAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    private ItemAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ItemAnalysisService();
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'student']);
    }

    public function test_difficulty_and_discrimination_are_calculated_correctly(): void
    {
        [$exam, $question, $correctOption, $session] = $this->buildExamWithOneQuestion();

        // 10 siswa: 4 kelompok atas (jawab benar), 3 kelompok bawah (jawab salah), 3 sedang
        // Atas (skor 100) → benar, Bawah (skor 0) → salah
        for ($i = 0; $i < 4; $i++) {
            $this->createAttemptWithAnswer($exam, $question, $correctOption, $session, isCorrect: true,  totalScore: 100.0);
        }
        for ($i = 0; $i < 3; $i++) {
            $this->createAttemptWithAnswer($exam, $question, $correctOption, $session, isCorrect: false, totalScore: 0.0);
        }
        for ($i = 0; $i < 3; $i++) {
            $this->createAttemptWithAnswer($exam, $question, $correctOption, $session, isCorrect: true,  totalScore: 50.0);
        }

        $result = $this->service->analyze($exam);
        $this->assertSame(10, $result['attempts_count']);
        $this->assertCount(1, $result['items']);

        $item = $result['items']->first();
        // 4 + 3 = 7 benar dari 10
        $this->assertEquals(0.7, $item['difficulty_p']);
        // P atas = 4/4 = 1, P bawah = 0/3 = 0 → D = 1.0
        $this->assertEquals(1.0, $item['discrimination_d']);
        $this->assertSame('Mudah', $item['difficulty_label']);
        $this->assertSame('Sangat Baik', $item['discrimination_label']);
        $this->assertSame('diterima', $item['status']);
    }

    public function test_high_difficulty_question_marked_as_sukar(): void
    {
        [$exam, $question, $correctOption, $session] = $this->buildExamWithOneQuestion();

        // Hanya 1 dari 10 siswa yang bisa jawab benar → P = 0.10 → sukar
        $this->createAttemptWithAnswer($exam, $question, $correctOption, $session, isCorrect: true,  totalScore: 100.0);
        for ($i = 0; $i < 9; $i++) {
            $this->createAttemptWithAnswer($exam, $question, $correctOption, $session, isCorrect: false, totalScore: 0.0);
        }

        $result = $this->service->analyze($exam);
        $item = $result['items']->first();

        $this->assertEquals(0.1, $item['difficulty_p']);
        $this->assertSame('Sukar', $item['difficulty_label']);
    }

    public function test_negative_discrimination_marks_status_ditolak(): void
    {
        [$exam, $question, $correctOption, $session] = $this->buildExamWithOneQuestion();

        // Kelompok atas (skor tinggi) jawab SALAH, kelompok bawah (skor rendah) jawab BENAR → D negatif
        for ($i = 0; $i < 4; $i++) {
            $this->createAttemptWithAnswer($exam, $question, $correctOption, $session, isCorrect: false, totalScore: 100.0);
        }
        for ($i = 0; $i < 3; $i++) {
            $this->createAttemptWithAnswer($exam, $question, $correctOption, $session, isCorrect: true,  totalScore: 0.0);
        }
        for ($i = 0; $i < 3; $i++) {
            $this->createAttemptWithAnswer($exam, $question, $correctOption, $session, isCorrect: false, totalScore: 50.0);
        }

        $result = $this->service->analyze($exam);
        $item = $result['items']->first();

        $this->assertLessThan(0, $item['discrimination_d']);
        $this->assertSame('ditolak', $item['status']);
        $this->assertSame('Ditolak / Ganti', $item['status_label']);
    }

    public function test_empty_attempts_returns_empty_summary(): void
    {
        [$exam] = $this->buildExamWithOneQuestion();

        $result = $this->service->analyze($exam);

        $this->assertSame(0, $result['attempts_count']);
        $this->assertTrue($result['items']->isEmpty());
        $this->assertSame(0, $result['summary']['total_attempts']);
    }

    public function test_summary_aggregates_distribution(): void
    {
        // Buat 3 soal dengan karakter berbeda
        [$exam, , , $session] = $this->buildExamWithOneQuestion();
        $q1 = $exam->questions->first();
        $opt1 = $q1->options->where('is_correct', true)->first();

        // Soal 2 & 3 ditambahkan
        $bank = $q1->questionBank;
        $q2 = Question::create([
            'question_bank_id' => $bank->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'content' => 'Soal 2',
            'default_score' => 5,
            'is_active' => true,
        ]);
        $opt2 = QuestionOption::create(['question_id' => $q2->id, 'label' => 'A', 'content' => 'Benar', 'is_correct' => true, 'order' => 1]);
        QuestionOption::create(['question_id' => $q2->id, 'label' => 'B', 'content' => 'Salah', 'is_correct' => false, 'order' => 2]);
        $exam->questions()->attach($q2->id, ['order' => 2, 'score' => 5]);

        $q3 = Question::create([
            'question_bank_id' => $bank->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'content' => 'Soal 3',
            'default_score' => 5,
            'is_active' => true,
        ]);
        $opt3 = QuestionOption::create(['question_id' => $q3->id, 'label' => 'A', 'content' => 'Benar', 'is_correct' => true, 'order' => 1]);
        QuestionOption::create(['question_id' => $q3->id, 'label' => 'B', 'content' => 'Salah', 'is_correct' => false, 'order' => 2]);
        $exam->questions()->attach($q3->id, ['order' => 3, 'score' => 5]);

        // Generate 10 siswa dengan skor berbeda
        // Soal 1: mudah & diskriminatif baik (semua atas benar, semua bawah salah)
        // Soal 2: sedang & baik
        // Soal 3: tidak ada yang benar (sukar, ditolak)

        // Atas: siswa 1-3, Bawah: siswa 8-10, Sedang: 4-7
        $plan = [
            // [score, q1_correct, q2_correct, q3_correct]
            [100, true,  true,  false], // 1
            [95,  true,  true,  false], // 2
            [90,  true,  false, false], // 3
            [70,  true,  true,  false], // 4
            [65,  false, true,  false], // 5
            [60,  true,  false, false], // 6
            [55,  false, false, false], // 7
            [30,  false, false, false], // 8
            [20,  false, false, false], // 9
            [10,  false, false, false], // 10
        ];

        foreach ($plan as [$score, $q1c, $q2c, $q3c]) {
            $attempt = $this->createAttemptWithAnswerReturning($exam, $session, totalScore: $score);
            ExamAnswer::create([
                'exam_attempt_id' => $attempt->id,
                'question_id'     => $q1->id,
                'answer_data'     => ['option_id' => $opt1->id],
                'is_correct'      => $q1c,
                'is_graded'       => true,
                'score'           => $q1c ? 5 : 0,
                'answered_at'     => now(),
            ]);
            ExamAnswer::create([
                'exam_attempt_id' => $attempt->id,
                'question_id'     => $q2->id,
                'answer_data'     => ['option_id' => $opt2->id],
                'is_correct'      => $q2c,
                'is_graded'       => true,
                'score'           => $q2c ? 5 : 0,
                'answered_at'     => now(),
            ]);
            ExamAnswer::create([
                'exam_attempt_id' => $attempt->id,
                'question_id'     => $q3->id,
                'answer_data'     => ['option_id' => $opt3->id],
                'is_correct'      => $q3c,
                'is_graded'       => true,
                'score'           => $q3c ? 5 : 0,
                'answered_at'     => now(),
            ]);
        }

        $result = $this->service->analyze($exam);
        $summary = $result['summary'];

        $this->assertSame(10, $summary['total_attempts']);
        $this->assertSame(3, $summary['auto_gradable']);
        $this->assertArrayHasKey('distribution', $summary);
        $this->assertArrayHasKey('difficulty_dist', $summary);
        $this->assertSame(10, $result['attempts_count']);
    }

    // ---- helpers ----

    private function buildExamWithOneQuestion(): array
    {
        $year = AcademicYear::create([
            'name'       => '2025/2026',
            'semester'   => 'Ganjil',
            'is_active'  => true,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date'   => now()->addMonths(5)->toDateString(),
        ]);
        $subject = Subject::create(['name' => 'Matematika', 'code' => 'MTK']);
        $classroom = Classroom::create([
            'name'            => 'X IPA 1',
            'grade'           => 'X',
            'academic_year_id'=> $year->id,
        ]);

        $bank = QuestionBank::create([
            'name'         => 'Bank MTK X',
            'subject_id'   => $subject->id,
            'is_active'    => true,
        ]);

        $question = Question::create([
            'question_bank_id' => $bank->id,
            'type'             => Question::TYPE_MULTIPLE_CHOICE,
            'content'          => '<p>Berapa 2+2?</p>',
            'default_score'    => 10,
            'is_active'        => true,
        ]);

        $correct = QuestionOption::create([
            'question_id' => $question->id,
            'label'       => 'A',
            'content'     => '4',
            'is_correct'  => true,
            'order'       => 1,
        ]);
        QuestionOption::create([
            'question_id' => $question->id,
            'label'       => 'B',
            'content'     => '5',
            'is_correct'  => false,
            'order'       => 2,
        ]);

        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin-'.uniqid().'@test.local',
            'password' => Hash::make('secret123'),
        ]);

        $exam = Exam::create([
            'title'         => 'Ulangan Harian MTK',
            'subject_id'    => $subject->id,
            'creator_id'    => $admin->id,
            'duration_minutes' => 60,
            'start_time'    => now()->subDay(),
            'end_time'      => now()->addDay(),
            'is_active'     => true,
        ]);
        $exam->questions()->attach($question->id, ['order' => 1, 'score' => 10]);
        $exam->load('questions.options');

        // Buat 1 sesi ujian
        $session = \App\Models\ExamSession::create([
            'exam_id'      => $exam->id,
            'name'         => 'Sesi 1',
            'start_time'   => now()->subDay(),
            'end_time'     => now()->addDay(),
            'duration_minutes' => 60,
            'is_active'    => true,
        ]);

        return [$exam, $question, $correct, $session];
    }

    private function createAttemptWithAnswerReturning(
        Exam $exam,
        \App\Models\ExamSession $session,
        float $totalScore,
    ): ExamAttempt {
        $studentUser = User::create([
            'name'     => 'Siswa '.uniqid(),
            'email'    => 'siswa-'.uniqid().'@test.local',
            'password' => Hash::make('secret123'),
        ]);
        $studentUser->assignRole('student');

        $student = Student::create([
            'user_id'         => $studentUser->id,
            'nisn'            => (string) random_int(100000, 999999),
            'nis'             => (string) random_int(10000, 99999),
            'name'            => $studentUser->name,
            'gender'          => 'L',
            'classroom_id'    => Classroom::first()->id,
            'is_active'       => true,
        ]);

        return ExamAttempt::create([
            'exam_id'           => $exam->id,
            'exam_session_id'   => $session->id,
            'student_id'        => $student->id,
            'status'            => ExamAttempt::STATUS_SUBMITTED,
            'score'             => $totalScore,
            'started_at'        => now()->subHour(),
            'submitted_at'      => now(),
            'ends_at'           => now(),
        ]);
    }

    private function createAttemptWithAnswer(
        Exam $exam,
        Question $question,
        QuestionOption $correctOption,
        \App\Models\ExamSession $session,
        bool $isCorrect,
        float $totalScore,
    ): void {
        $attempt = $this->createAttemptWithAnswerReturning($exam, $session, $totalScore);

        ExamAnswer::create([
            'exam_attempt_id' => $attempt->id,
            'question_id'     => $question->id,
            'answer_data'     => ['option_id' => $correctOption->id],
            'is_correct'      => $isCorrect,
            'is_graded'       => true,
            'score'           => $isCorrect ? 10 : 0,
            'answered_at'     => now(),
        ]);
    }
}
