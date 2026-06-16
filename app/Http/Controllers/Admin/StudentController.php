<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::with(['user', 'classrooms']);
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        $students = $query->latest()->paginate(20)->withQueryString();
        return view('admin.students.index', compact('students'));
    }

    public function create(): View
    {
        $classrooms = Classroom::active()->get();
        return view('admin.students.create', compact('classrooms'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nisn' => 'required|string|max:20|unique:students,nisn',
            'nis' => 'nullable|string|max:20|unique:students,nis',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'classroom_id' => 'nullable|exists:classrooms,id',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);
            $user->assignRole('student');

            $student = Student::create([
                'user_id' => $user->id,
                'nisn' => $validated['nisn'],
                'nis' => $validated['nis'] ?? null,
                'name' => $validated['name'],
                'gender' => $validated['gender'],
                'birth_place' => $validated['birth_place'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
                'address' => $validated['address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'parent_name' => $validated['parent_name'] ?? null,
                'parent_phone' => $validated['parent_phone'] ?? null,
            ]);

            if (! empty($validated['classroom_id'])) {
                $student->classrooms()->attach($validated['classroom_id']);
            }
        });

        return redirect()->route('admin.students.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function show(Student $student): View
    {
        $student->load(['user', 'classrooms', 'examAttempts.exam']);
        return view('admin.students.show', compact('student'));
    }

    public function edit(Student $student): View
    {
        $student->load('user');
        $classrooms = Classroom::active()->get();
        return view('admin.students.edit', compact('student', 'classrooms'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'nisn' => 'required|string|max:20|unique:students,nisn,' . $student->id,
            'nis' => 'nullable|string|max:20|unique:students,nis,' . $student->id,
            'name' => 'required|string|max:255',
            'gender' => 'required|in:L,P',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'parent_name' => 'nullable|string|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'email' => 'required|email|unique:users,email,' . $student->user_id,
            'classroom_id' => 'nullable|exists:classrooms,id',
        ]);

        DB::transaction(function () use ($student, $validated) {
            $student->user->update(['name' => $validated['name'], 'email' => $validated['email']]);
            $student->update(collect($validated)->except(['email', 'classroom_id'])->toArray());
            if (! empty($validated['classroom_id'])) {
                $student->classrooms()->sync([$validated['classroom_id']]);
            }
        });

        return redirect()->route('admin.students.show', $student)->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        DB::transaction(function () use ($student) {
            $student->user->delete(); // cascades to student
        });
        return redirect()->route('admin.students.index')->with('success', 'Siswa berhasil dihapus.');
    }
}
