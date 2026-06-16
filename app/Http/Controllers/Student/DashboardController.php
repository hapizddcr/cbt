<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $student = $user->student;

        $ongoingExams = collect();
        $availableExams = collect();
        $recentResults = collect();

        if ($student) {
            $ongoingExams = ExamAttempt::with('exam.subject')
                ->where('student_id', $student->id)
                ->where('status', ExamAttempt::STATUS_IN_PROGRESS)
                ->where('ends_at', '>', now())
                ->get();

            $recentResults = ExamAttempt::with('exam.subject')
                ->where('student_id', $student->id)
                ->whereIn('status', [ExamAttempt::STATUS_SUBMITTED, ExamAttempt::STATUS_GRADED])
                ->latest('submitted_at')
                ->limit(10)
                ->get();
        }

        return view('student.dashboard', compact('ongoingExams', 'availableExams', 'recentResults'));
    }
}
