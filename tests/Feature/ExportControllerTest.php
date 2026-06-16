<?php

namespace Tests\Feature;

use App\Exports\ExamResultExport;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamSession;
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

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Exam $exam;
    protected ExamSession $session;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'student']);

        $this->admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin-'.uniqid().'@test.local',
            'password' => Hash::make('x'),
        ]);
        $this->admin->assignRole('admin');

        $year = AcademicYear::create([
            'name' => '2025/2026', 'semester' => 'Ganjil', 'is_active' => true,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addMonths(5)->toDateString(),
        ]);
        $classroom = Classroom::create(['name' => 'X', 'grade' => 'X', 'academic_year_id' => $year->id]);
        $subject = Subject::create(['name' => 'MTK', 'code' => 'MTK']);
        $bank = QuestionBank::create(['name' => 'Bank', 'subject_id' => $subject->id]);
        $q = Question::create(['question_bank_id' => $bank->id, 'type' => 'multiple_choice', 'content' => 'Q', 'default_score' => 10, 'is_active' => true]);
        $opt = QuestionOption::create(['question_id' => $q->id, 'label' => 'A', 'content' => 'Benar', 'is_correct' => true, 'order' => 1]);
        QuestionOption::create(['question_id' => $q->id, 'label' => 'B', 'content' => 'Salah', 'is_correct' => false, 'order' => 2]);

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
    }

    public function test_exam_result_export_writes_xlsx_file(): void
    {
        $exporter = new ExamResultExport(new ItemAnalysisService());
        $tmpPath = storage_path('app/exports/test-'.uniqid().'.xlsx');
        @mkdir(dirname($tmpPath), 0775, true);

        $exporter->write($this->exam, $tmpPath, null);

        $this->assertFileExists($tmpPath);
        $this->assertGreaterThan(0, filesize($tmpPath), 'XLSX file should be non-empty');

        // XLSX is a ZIP — check magic bytes
        $fh = fopen($tmpPath, 'rb');
        $magic = fread($fh, 4);
        fclose($fh);
        $this->assertSame('PK', substr($magic, 0, 2), 'XLSX must be a ZIP (PK header)');

        @unlink($tmpPath);
    }

    public function test_admin_can_download_exam_result_export(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.exams.export', $this->exam));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition') ?? '');
    }

    public function test_non_admin_cannot_download_export(): void
    {
        $u = User::create(['name' => 'S', 'email' => 's-'.uniqid().'@t.local', 'password' => Hash::make('x')]);
        $u->assignRole('student');
        $this->actingAs($u)->get(route('admin.exams.export', $this->exam))->assertForbidden();
    }
}
