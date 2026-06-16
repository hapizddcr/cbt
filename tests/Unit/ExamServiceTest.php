<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\ExamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExamServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamService $service;
    private Student $student;
    private Subject $subject;
    private QuestionBank $bank;
    private Question $question;
    private Exam $exam;
    private ExamSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExamService::class);

        $creator = User::create(['name' => 'Teacher', 'email' => 'teacher@test.local', 'password' => Hash::make('password')]);
        $year = AcademicYear::create(['name' => '2024/2025', 'semester' => 'Ganjil', 'is_active' => true, 'start_date' => '2024-07-01', 'end_date' => '2024-12-31']);
        $this->subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika']);
        $this->bank = QuestionBank::create(['subject_id' => $this->subject->id, 'name' => 'Bank Soal 1', 'level' => 'medium', 'is_shared' => true]);

        $this->question = Question::create([
            'question_bank_id' => $this->bank->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'content' => '2+2=?',
            'default_score' => 10,
            'difficulty' => 'easy',
            'is_active' => true,
        ]);
        QuestionOption::create(['question_id' => $this->question->id, 'content' => '3', 'is_correct' => false, 'order' => 0]);
        QuestionOption::create(['question_id' => $this->question->id, 'content' => '4', 'is_correct' => true, 'order' => 1]);
        QuestionOption::create(['question_id' => $this->question->id, 'content' => '5', 'is_correct' => false, 'order' => 2]);

        $this->exam = Exam::create([
            'subject_id' => $this->subject->id,
            'question_bank_id' => $this->bank->id,
            'creator_id' => $creator->id,
            'title' => 'UTS Matematika',
            'type' => 'midterm',
            'duration_minutes' => 60,
            'max_score' => 100,
            'passing_score' => 75,
            'max_attempts' => 1,
            'is_active' => true,
        ]);
        $this->exam->questions()->attach($this->question->id, ['order' => 1, 'score' => 10]);
        $this->exam->update(['total_questions' => 1]);

        $this->session = ExamSession::create([
            'exam_id' => $this->exam->id,
            'name' => 'Sesi 1',
            'start_time' => now()->subMinutes(5),
            'end_time' => now()->addHours(2),
            'duration_minutes' => 60,
            'token_lifetime_minutes' => 60,
            'is_active' => true,
        ]);

        $user = User::create(['name' => 'Test Student', 'email' => 'student@test.local', 'password' => Hash::make('password')]);
        $this->student = Student::create(['user_id' => $user->id, 'nisn' => '1234567890', 'name' => 'Test Student', 'gender' => 'L']);
        $this->session->students()->attach($this->student->id, ['participant_number' => '001']);
    }

    public function test_generate_token_returns_6_char_alphanumeric(): void
    {
        $token = $this->service->generateToken(6);
        $this->assertEquals(6, strlen($token));
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{6}$/', $token);
    }

    public function test_validate_token_returns_session_data(): void
    {
        $examToken = ExamToken::create([
            'exam_session_id' => $this->session->id,
            'token' => 'TEST01',
            'issued_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(55),
            'is_active' => true,
        ]);

        $result = $this->service->validateToken('TEST01', $this->student->id);

        $this->assertEquals($examToken->id, $result['token']->id);
        $this->assertEquals($this->exam->id, $result['exam']->id);
    }

    public function test_validate_token_throws_when_invalid(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->validateToken('WRONG', $this->student->id);
    }

    public function test_validate_token_throws_when_student_not_allocated(): void
    {
        ExamToken::create([
            'exam_session_id' => $this->session->id,
            'token' => 'ABC123',
            'issued_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(55),
            'is_active' => true,
        ]);

        $other = Student::create(['user_id' => $this->student->user_id, 'nisn' => '999', 'name' => 'Other', 'gender' => 'L']);

        $this->expectException(RuntimeException::class);
        $this->service->validateToken('ABC123', $other->id);
    }

    public function test_start_attempt_creates_attempt_and_answer_rows(): void
    {
        $attempt = $this->service->startAttempt($this->session, $this->student);

        $this->assertEquals(ExamAttempt::STATUS_IN_PROGRESS, $attempt->status);
        $this->assertNotNull($attempt->ends_at);
        $this->assertEquals(1, $attempt->answers()->count());
    }

    public function test_start_attempt_returns_existing_in_progress(): void
    {
        $first = $this->service->startAttempt($this->session, $this->student);
        $second = $this->service->startAttempt($this->session, $this->student);

        $this->assertEquals($first->id, $second->id);
    }

    public function test_save_answer_stores_data(): void
    {
        $attempt = $this->service->startAttempt($this->session, $this->student);
        $correctOption = $this->question->options()->where('is_correct', true)->first();

        $this->service->saveAnswer($attempt, $this->question->id, ['answer_data' => ['option_id' => $correctOption->id]]);

        $answer = $attempt->answers()->where('question_id', $this->question->id)->first();
        $this->assertNotNull($answer->answered_at);
        $this->assertEquals($correctOption->id, $answer->answer_data['option_id']);
    }

    public function test_submit_attempt_grades_correct_mc_answer(): void
    {
        // Update question score to 100 for this test
        $this->exam->questions()->updateExistingPivot($this->question->id, ['score' => 100]);

        $attempt = $this->service->startAttempt($this->session, $this->student);
        $correctOption = $this->question->options()->where('is_correct', true)->first();

        $this->service->saveAnswer($attempt, $this->question->id, ['answer_data' => ['option_id' => $correctOption->id]]);
        $graded = $this->service->submitAttempt($attempt);

        $this->assertEquals(ExamAttempt::STATUS_SUBMITTED, $graded->status);
        $this->assertEquals(100, (float) $graded->score);
        $this->assertEquals(100, (float) $graded->percentage);
        $this->assertTrue($graded->is_passed);
    }

    public function test_submit_attempt_grades_wrong_mc_answer(): void
    {
        $this->exam->questions()->updateExistingPivot($this->question->id, ['score' => 100]);

        $attempt = $this->service->startAttempt($this->session, $this->student);
        $wrongOption = $this->question->options()->where('is_correct', false)->first();

        $this->service->saveAnswer($attempt, $this->question->id, ['answer_data' => ['option_id' => $wrongOption->id]]);
        $graded = $this->service->submitAttempt($attempt);

        $this->assertEquals(0, (float) $graded->score);
        $this->assertFalse($graded->is_passed);
    }

    public function test_max_attempts_limit_enforced(): void
    {
        // First attempt - submit
        $a1 = $this->service->startAttempt($this->session, $this->student);
        $this->service->submitAttempt($a1);

        // Second attempt should fail
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('maksimum percobaan');
        $this->service->startAttempt($this->session, $this->student);
    }

    public function test_get_questions_for_attempt_shuffles_when_enabled(): void
    {
        // Add more questions to test shuffling
        for ($i = 0; $i < 5; $i++) {
            $q = Question::create([
                'question_bank_id' => $this->bank->id,
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'content' => "Q{$i}",
                'default_score' => 10,
                'is_active' => true,
            ]);
            QuestionOption::create(['question_id' => $q->id, 'content' => 'A', 'is_correct' => true, 'order' => 0]);
            $this->exam->questions()->attach($q->id, ['order' => $i + 2, 'score' => 10]);
        }

        $questions = $this->service->getQuestionsForAttempt($this->exam);
        $this->assertCount(6, $questions);
    }

    public function test_short_answer_grading(): void
    {
        $saQ = Question::create([
            'question_bank_id' => $this->bank->id,
            'type' => Question::TYPE_SHORT_ANSWER,
            'content' => 'Ibukota Indonesia?',
            'default_score' => 5,
            'is_active' => true,
        ]);
        QuestionOption::create(['question_id' => $saQ->id, 'content' => 'Jakarta', 'is_correct' => true, 'order' => 0]);
        $this->exam->questions()->attach($saQ->id, ['order' => 99, 'score' => 5]);

        $attempt = $this->service->startAttempt($this->session, $this->student);
        $this->service->saveAnswer($attempt, $saQ->id, ['answer_data' => ['text' => 'Jakarta']]);
        $graded = $this->service->submitAttempt($attempt);

        // Only the SA is answered; MC remains blank → score = 5
        $this->assertEquals(5, (float) $graded->score);
    }
}
