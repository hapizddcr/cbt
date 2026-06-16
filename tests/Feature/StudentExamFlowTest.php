<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentExamFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;
    private Student $student;
    private Exam $exam;
    private ExamSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $creator = User::create(['name' => 'Teacher', 'email' => 't@test.local', 'password' => Hash::make('password')]);

        $year = AcademicYear::create(['name' => '2024/2025', 'semester' => 'Ganjil', 'is_active' => true, 'start_date' => '2024-07-01', 'end_date' => '2024-12-31']);
        $subject = Subject::create(['code' => 'MTK', 'name' => 'Matematika']);
        $bank = QuestionBank::create(['subject_id' => $subject->id, 'name' => 'Bank', 'level' => 'medium', 'is_shared' => true]);

        $q = Question::create([
            'question_bank_id' => $bank->id,
            'type' => Question::TYPE_MULTIPLE_CHOICE,
            'content' => '1+1=?',
            'default_score' => 100,
            'is_active' => true,
        ]);
        QuestionOption::create(['question_id' => $q->id, 'content' => '1', 'is_correct' => false, 'order' => 0]);
        QuestionOption::create(['question_id' => $q->id, 'content' => '2', 'is_correct' => true, 'order' => 1]);

        $this->exam = Exam::create([
            'subject_id' => $subject->id,
            'question_bank_id' => $bank->id,
            'creator_id' => $creator->id,
            'title' => 'Test Exam',
            'type' => 'quiz',
            'duration_minutes' => 60,
            'max_score' => 100,
            'passing_score' => 75,
            'is_active' => true,
        ]);
        $this->exam->questions()->attach($q->id, ['order' => 1, 'score' => 100]);

        $this->session = ExamSession::create([
            'exam_id' => $this->exam->id,
            'name' => 'Sesi 1',
            'start_time' => now()->subMinutes(5),
            'end_time' => now()->addHours(2),
            'duration_minutes' => 60,
            'token_lifetime_minutes' => 60,
            'is_active' => true,
        ]);

        $this->studentUser = User::factory()->create();
        $this->studentUser->assignRole('student');
        $this->student = Student::create([
            'user_id' => $this->studentUser->id,
            'nisn' => '12345',
            'name' => 'Test Student',
            'gender' => 'L',
        ]);
        $this->session->students()->attach($this->student->id);
    }

    public function test_student_can_view_token_form(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('student.exam.token'));
        $response->assertOk();
    }

    public function test_student_can_start_exam_with_valid_token(): void
    {
        ExamToken::create([
            'exam_session_id' => $this->session->id,
            'token' => 'ABCD12',
            'issued_at' => now()->subMinutes(1),
            'expires_at' => now()->addMinutes(30),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->post(route('student.exam.start'), ['token' => 'ABCD12']);

        $response->assertRedirect();
        $this->assertDatabaseHas('exam_attempts', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_student_cannot_start_exam_with_invalid_token(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->post(route('student.exam.start'), ['token' => 'WRONGX']);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(0, \App\Models\ExamAttempt::count());
    }

    public function test_student_can_save_answer_via_ajax(): void
    {
        $attempt = \App\Models\ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'ends_at' => now()->addHour(),
        ]);
        $correctOption = $this->exam->questions->first()->options->where('is_correct', true)->first();
        \App\Models\ExamAnswer::create([
            'exam_attempt_id' => $attempt->id,
            'question_id' => $this->exam->questions->first()->id,
        ]);

        $response = $this->actingAs($this->studentUser)
            ->postJson(route('student.exam.save', $attempt), [
                'question_id' => $this->exam->questions->first()->id,
                'answer_data' => ['option_id' => $correctOption->id],
            ]);

        $response->assertJson(['success' => true]);
    }

    public function test_other_student_cannot_access_attempt(): void
    {
        $other = User::factory()->create();
        $other->assignRole('student');

        $attempt = \App\Models\ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'exam_session_id' => $this->session->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($other)->get(route('student.exam.take', $attempt));
        $response->assertStatus(403);
    }
}
