<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamRoom;
use App\Models\ExamSession;
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

class PrintControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Exam $exam;
    protected ExamSession $session;
    protected ExamRoom $room;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'student']);

        $this->admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin-'.uniqid().'@test.local',
            'password' => Hash::make('secret'),
        ]);
        $this->admin->assignRole('admin');

        $year = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'Ganjil', 'is_active' => true,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
        ]);
        $classroom = Classroom::create(['name' => 'X IPA 1', 'grade' => 'X', 'academic_year_id' => $year->id]);
        $subject = Subject::create(['name' => 'MTK', 'code' => 'MTK']);
        $bank = QuestionBank::create(['name' => 'Bank', 'subject_id' => $subject->id]);
        $q = Question::create(['question_bank_id' => $bank->id, 'type' => 'multiple_choice', 'content' => 'Q', 'default_score' => 10, 'is_active' => true]);

        $this->exam = Exam::create([
            'title' => 'Test Exam',
            'subject_id' => $subject->id,
            'creator_id' => $this->admin->id,
            'duration_minutes' => 60,
            'start_time' => now(),
            'end_time' => now()->addHour(),
        ]);
        $this->exam->questions()->attach($q->id, ['order' => 1, 'score' => 10]);

        $this->session = ExamSession::create([
            'exam_id' => $this->exam->id,
            'name' => 'Sesi 1',
            'start_time' => now(),
            'end_time' => now()->addHour(),
            'duration_minutes' => 60,
        ]);

        $this->room = ExamRoom::create([
            'exam_session_id' => $this->session->id,
            'name' => 'Ruang A',
            'capacity' => 30,
        ]);

        // 3 siswa dialokasikan ke ruang
        for ($i = 0; $i < 3; $i++) {
            $u = User::create(['name' => 'Siswa '.$i, 'email' => "s{$i}-".uniqid().'@test.local', 'password' => Hash::make('x')]);
            $u->assignRole('student');
            $s = Student::create([
                'user_id' => $u->id, 'name' => $u->name, 'gender' => 'L',
                'nisn' => (string) random_int(100000, 999999),
                'nis' => (string) random_int(10000, 99999),
            ]);
            $s->classrooms()->attach($classroom->id, ['student_number' => $i + 1]);
            $this->session->students()->attach($s->id, [
                'exam_room_id' => $this->room->id,
                'participant_number' => str_pad((string)($i + 1), 4, '0', STR_PAD_LEFT),
                'seat_number' => $i + 1,
            ]);
        }
    }

    public function test_admin_can_view_kartu_ujian_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sessions.kartu', $this->session));
        $response->assertOk();
        $this->assertStringContainsString('pdf', $response->headers->get('content-type'));
    }

    public function test_admin_can_view_kartu_ujian_bulk_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sessions.kartu.bulk', $this->session));
        $response->assertOk();
        $this->assertStringContainsString('pdf', $response->headers->get('content-type'));
    }

    public function test_admin_can_view_daftar_hadir_pdf(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.sessions.daftar-hadir', $this->session));
        $response->assertOk();
        $this->assertStringContainsString('pdf', $response->headers->get('content-type'));
    }

    public function test_non_admin_cannot_access_print_routes(): void
    {
        $student = User::create(['name' => 'X', 'email' => 's-'.uniqid().'@t.local', 'password' => Hash::make('x')]);
        $student->assignRole('student');
        $this->actingAs($student)->get(route('admin.sessions.kartu', $this->session))->assertForbidden();
    }
}
