<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\ExamController as AdminExamController;
use App\Http\Controllers\Admin\ExamSessionController as AdminSessionController;
use App\Http\Controllers\Admin\GradingController;
use App\Http\Controllers\Admin\ItemAnalysisController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\PrintController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Student\DashboardController as StudentDashboard;
use App\Http\Controllers\Student\ExamController as StudentExamController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('login'));

// PWA routes (serve with correct content type)
Route::get('/manifest.json', function () {
    $content = file_get_contents(public_path('manifest.json'));
    return response($content, 200, [
        'Content-Type' => 'application/manifest+json',
    ]);
})->name('pwa.manifest');

Route::get('/sw.js', function () {
    $content = file_get_contents(public_path('sw.js'));
    return response($content, 200, [
        'Content-Type' => 'application/javascript',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
})->name('pwa.serviceworker');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [\App\Http\Controllers\Auth\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [\App\Http\Controllers\Auth\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [\App\Http\Controllers\Auth\ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('student')) {
            return redirect()->route('student.dashboard');
        }
        return view('dashboard');
    })->name('dashboard');

    // Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

        // Master Data
        Route::get('/master', [MasterDataController::class, 'index'])->name('master.index');
        Route::get('/master/subjects', [MasterDataController::class, 'subjects'])->name('master.subjects');
        Route::post('/master/subjects', [MasterDataController::class, 'storeSubject'])->name('master.subjects.store');
        Route::put('/master/subjects/{subject}', [MasterDataController::class, 'updateSubject'])->name('master.subjects.update');
        Route::delete('/master/subjects/{subject}', [MasterDataController::class, 'destroySubject'])->name('master.subjects.destroy');
        Route::get('/master/classrooms', [MasterDataController::class, 'classrooms'])->name('master.classrooms');
        Route::post('/master/classrooms', [MasterDataController::class, 'storeClassroom'])->name('master.classrooms.store');
        Route::put('/master/classrooms/{classroom}', [MasterDataController::class, 'updateClassroom'])->name('master.classrooms.update');
        Route::delete('/master/classrooms/{classroom}', [MasterDataController::class, 'destroyClassroom'])->name('master.classrooms.destroy');
        Route::get('/master/academic-years', [MasterDataController::class, 'academicYears'])->name('master.academic-years');
        Route::post('/master/academic-years', [MasterDataController::class, 'storeAcademicYear'])->name('master.academic-years.store');
        Route::post('/master/academic-years/{academicYear}/activate', [MasterDataController::class, 'setActiveAcademicYear'])->name('master.academic-years.activate');

        // Students
        Route::resource('students', StudentController::class);

        // Questions
        Route::resource('questions', QuestionController::class);

        // Exams
        Route::resource('exams', AdminExamController::class);
        Route::get('exams/{exam}/questions', [AdminExamController::class, 'questions'])->name('exams.questions');
        Route::post('exams/{exam}/questions', [AdminExamController::class, 'attachQuestions'])->name('exams.questions.attach');
        Route::delete('exams/{exam}/questions/{questionId}', [AdminExamController::class, 'detachQuestion'])->name('exams.questions.detach');

        // Sessions
        Route::resource('sessions', AdminSessionController::class);
        Route::post('sessions/{session}/rooms', [AdminSessionController::class, 'addRoom'])->name('sessions.rooms.add');
        Route::delete('sessions/{session}/rooms/{room}', [AdminSessionController::class, 'destroyRoom'])->name('sessions.rooms.destroy');
        Route::post('sessions/{session}/tokens', [AdminSessionController::class, 'generateToken'])->name('sessions.tokens.generate');
        Route::delete('sessions/{session}/tokens/{token}', [AdminSessionController::class, 'deactivateToken'])->name('sessions.tokens.deactivate');

        // Item Analysis
        Route::get('analysis', [ItemAnalysisController::class, 'index'])->name('analysis.index');
        Route::get('analysis/{exam}', [ItemAnalysisController::class, 'show'])->name('analysis.show');
        Route::get('analysis/{exam}/export', [ItemAnalysisController::class, 'export'])->name('analysis.export');

        // Print PDF
        Route::get('sessions/{session}/kartu-ujian', [PrintController::class, 'kartuUjian'])->name('sessions.kartu');
        Route::get('sessions/{session}/kartu-ujian-bulk', [PrintController::class, 'kartuUjianBulk'])->name('sessions.kartu.bulk');
        Route::get('sessions/{session}/daftar-hadir', [PrintController::class, 'daftarHadir'])->name('sessions.daftar-hadir');
        Route::get('attempts/{attempt}/kartu-ujian', [PrintController::class, 'kartuUjianSingle'])->name('attempts.kartu');

        // Export XLSX
        Route::get('exams/{exam}/export', [ExportController::class, 'examResult'])->name('exams.export');

        // Grading
        Route::get('grading', [GradingController::class, 'index'])->name('grading.index');
        Route::get('grading/{attempt}', [GradingController::class, 'show'])->name('grading.show');
        Route::post('grading/{attempt}/answers/{answer}', [GradingController::class, 'gradeEssay'])->name('grading.essay');
    });

    // Student
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentDashboard::class, 'index'])->name('dashboard');
        Route::get('/exam/token', [StudentExamController::class, 'showTokenForm'])->name('exam.token');
        Route::post('/exam/start', [StudentExamController::class, 'startWithToken'])->name('exam.start');
        Route::get('/exam/{attempt}', [StudentExamController::class, 'take'])->name('exam.take');
        Route::post('/exam/{attempt}/save', [StudentExamController::class, 'saveAnswer'])->name('exam.save');
        Route::post('/exam/{attempt}/submit', [StudentExamController::class, 'submit'])->name('exam.submit');
        Route::get('/exam/{attempt}/result', [StudentExamController::class, 'result'])->name('exam.result');
        Route::post('/exam/{attempt}/violation', [StudentExamController::class, 'recordViolation'])->name('exam.violation');
    });
});

require __DIR__ . '/auth.php';
