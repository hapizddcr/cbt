<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'students' => Student::count(),
            'teachers' => Teacher::count(),
            'subjects' => Subject::count(),
            'exams' => Exam::count(),
            'active_exams' => Exam::where('is_active', true)->count(),
            'academic_years' => AcademicYear::count(),
        ];

        $recentExams = Exam::with('subject')->latest()->limit(5)->get();
        $announcements = Announcement::where(function ($q) {
            $q->where('is_pinned', true)
                ->orWhere('published_at', '<=', now())
                ->where(function ($q2) {
                    $q2->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                });
        })->latest()->limit(5)->get();

        return view('admin.dashboard', compact('stats', 'recentExams', 'announcements'));
    }
}
