<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionOption;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedRolesAndPermissions();
            $this->seedUsers();
            $this->seedAcademicData();
            $this->seedSampleQuestions();
        });
    }

    private function seedRolesAndPermissions(): void
    {
        $permissions = [
            // Master data
            'manage subjects', 'manage classrooms', 'manage academic years',
            // Students
            'view students', 'create students', 'edit students', 'delete students',
            // Questions
            'view questions', 'create questions', 'edit questions', 'delete questions',
            // Exams
            'view exams', 'create exams', 'edit exams', 'delete exams',
            'manage sessions', 'manage tokens', 'view results', 'grade exams',
            // Reports
            'view reports',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        $student = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
        $student->syncPermissions([]);
    }

    private function seedUsers(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@cbt.local'],
            ['name' => 'Administrator', 'password' => Hash::make('password'), 'is_active' => true]
        );
        $admin->assignRole('admin');

        $student = User::firstOrCreate(
            ['email' => 'siswa@cbt.local'],
            ['name' => 'Budi Siswa', 'password' => Hash::make('password'), 'is_active' => true]
        );
        $student->assignRole('student');
        \App\Models\Student::firstOrCreate(
            ['user_id' => $student->id],
            ['nisn' => '1234567890', 'name' => 'Budi Siswa', 'gender' => 'L']
        );
    }

    private function seedAcademicData(): void
    {
        $year = AcademicYear::firstOrCreate(
            ['name' => '2024/2025', 'semester' => 'Ganjil'],
            ['start_date' => '2024-07-15', 'end_date' => '2024-12-20', 'is_active' => true]
        );

        $subjects = [
            ['code' => 'MTK', 'name' => 'Matematika', 'group' => 'Umum'],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia', 'group' => 'Umum'],
            ['code' => 'BIG', 'name' => 'Bahasa Inggris', 'group' => 'Umum'],
            ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam', 'group' => 'Umum'],
            ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial', 'group' => 'Umum'],
            ['code' => 'PKN', 'name' => 'Pendidikan Pancasila', 'group' => 'Umum'],
        ];
        foreach ($subjects as $subj) {
            Subject::firstOrCreate(['code' => $subj['code']], $subj);
        }

        Classroom::firstOrCreate(
            ['name' => 'X IPA 1'],
            [
                'academic_year_id' => $year->id,
                'grade' => 'X',
                'major' => 'IPA',
                'capacity' => 36,
                'is_active' => true,
            ]
        );
    }

    private function seedSampleQuestions(): void
    {
        $mtk = Subject::where('code', 'MTK')->first();
        if (! $mtk) {
            return;
        }

        $bank = QuestionBank::firstOrCreate(
            ['name' => 'Bank Soal Matematika X'],
            [
                'subject_id' => $mtk->id,
                'creator_id' => 1,
                'level' => 'medium',
                'is_shared' => true,
            ]
        );

        $q = Question::firstOrCreate(
            ['content' => 'Berapakah hasil dari 2 + 2?'],
            [
                'question_bank_id' => $bank->id,
                'type' => Question::TYPE_MULTIPLE_CHOICE,
                'explanation' => '2 + 2 = 4',
                'default_score' => 10,
                'difficulty' => 'easy',
                'is_active' => true,
            ]
        );

        if ($q->options()->count() === 0) {
            $options = [
                ['content' => '3', 'is_correct' => false, 'order' => 0],
                ['content' => '4', 'is_correct' => true, 'order' => 1],
                ['content' => '5', 'is_correct' => false, 'order' => 2],
                ['content' => '6', 'is_correct' => false, 'order' => 3],
            ];
            foreach ($options as $opt) {
                QuestionOption::create(array_merge($opt, ['question_id' => $q->id]));
            }
        }
    }
}
