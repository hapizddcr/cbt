<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(): View
    {
        $stats = [
            'subjects' => Subject::count(),
            'classrooms' => Classroom::count(),
            'teachers' => Teacher::count(),
            'students' => \App\Models\Student::count(),
            'academic_years' => AcademicYear::count(),
        ];
        return view('admin.master.index', compact('stats'));
    }

    public function subjects(Request $request): View
    {
        $query = Subject::query();
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('code', 'like', "%{$request->search}%");
            });
        }
        $subjects = $query->latest()->paginate(20);
        return view('admin.master.subjects', compact('subjects'));
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:20|unique:subjects,code',
            'name' => 'required|string|max:255',
            'group' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);
        Subject::create($data);
        return back()->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function updateSubject(Request $request, Subject $subject): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:20|unique:subjects,code,' . $subject->id,
            'name' => 'required|string|max:255',
            'group' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);
        $subject->update($data);
        return back()->with('success', 'Mata pelajaran berhasil diperbarui.');
    }

    public function destroySubject(Subject $subject): RedirectResponse
    {
        $subject->delete();
        return back()->with('success', 'Mata pelajaran berhasil dihapus.');
    }

    public function classrooms(Request $request): View
    {
        $query = Classroom::with(['academicYear', 'homeroomTeacher']);
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->academic_year_id);
        }
        $classrooms = $query->latest()->paginate(20);
        $academicYears = AcademicYear::all();
        return view('admin.master.classrooms', compact('classrooms', 'academicYears'));
    }

    public function storeClassroom(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:50',
            'grade' => 'required|string|max:10',
            'major' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:1',
        ]);
        Classroom::create($data);
        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function updateClassroom(Request $request, Classroom $classroom): RedirectResponse
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:50',
            'grade' => 'required|string|max:10',
            'major' => 'nullable|string|max:50',
            'capacity' => 'required|integer|min:1',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
        ]);
        $classroom->update($data);
        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroyClassroom(Classroom $classroom): RedirectResponse
    {
        $classroom->delete();
        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    public function academicYears(): View
    {
        $academicYears = AcademicYear::latest()->get();
        return view('admin.master.academic-years', compact('academicYears'));
    }

    public function storeAcademicYear(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'semester' => 'required|in:Ganjil,Genap',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);
        if ($data['is_active'] ?? false) {
            AcademicYear::query()->update(['is_active' => false]);
        }
        AcademicYear::create($data);
        return back()->with('success', 'Tahun pelajaran berhasil ditambahkan.');
    }

    public function setActiveAcademicYear(AcademicYear $academicYear): RedirectResponse
    {
        AcademicYear::query()->update(['is_active' => false]);
        $academicYear->update(['is_active' => true]);
        return back()->with('success', 'Tahun pelajaran diaktifkan.');
    }
}
