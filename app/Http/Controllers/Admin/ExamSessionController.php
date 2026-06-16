<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use App\Models\ExamToken;
use App\Services\ExamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamSessionController extends Controller
{
    public function __construct(
        private ExamService $examService,
    ) {}

    public function index(Request $request): View
    {
        $query = ExamSession::with(['exam.subject', 'rooms']);
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }
        $sessions = $query->latest('start_time')->paginate(15)->withQueryString();
        return view('admin.sessions.index', compact('sessions'));
    }

    public function create(Request $request): View
    {
        $examId = $request->query('exam_id');
        return view('admin.sessions.create', compact('examId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'name' => 'required|string|max:100',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'duration_minutes' => 'required|integer|min:1',
            'token_lifetime_minutes' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $session = ExamSession::create($validated);
        return redirect()->route('admin.sessions.show', $session)->with('success', 'Sesi berhasil dibuat.');
    }

    public function show(ExamSession $session): View
    {
        $session->load(['exam.subject', 'rooms.supervisor', 'students', 'tokens']);
        return view('admin.sessions.show', compact('session'));
    }

    public function edit(ExamSession $session): View
    {
        return view('admin.sessions.edit', compact('session'));
    }

    public function update(Request $request, ExamSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'duration_minutes' => 'required|integer|min:1',
            'token_lifetime_minutes' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);
        $session->update($validated);
        return redirect()->route('admin.sessions.show', $session)->with('success', 'Sesi berhasil diperbarui.');
    }

    public function destroy(ExamSession $session): RedirectResponse
    {
        $session->delete();
        return redirect()->route('admin.sessions.index')->with('success', 'Sesi berhasil dihapus.');
    }

    /**
     * Add a room to the session.
     */
    public function addRoom(Request $request, ExamSession $session): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'capacity' => 'required|integer|min:1',
            'supervisor_id' => 'nullable|exists:users,id',
        ]);
        $session->rooms()->create($data);
        return back()->with('success', 'Ruang berhasil ditambahkan.');
    }

    public function destroyRoom(ExamSession $session, ExamRoom $room): RedirectResponse
    {
        abort_unless($room->exam_session_id === $session->id, 404);
        $room->delete();
        return back()->with('success', 'Ruang berhasil dihapus.');
    }

    /**
     * Generate a fresh token.
     */
    public function generateToken(Request $request, ExamSession $session): RedirectResponse
    {
        $request->validate([
            'exam_room_id' => 'nullable|exists:exam_rooms,id',
            'duration_minutes' => 'nullable|integer|min:1',
        ]);

        $duration = $request->input('duration_minutes', $session->token_lifetime_minutes);

        ExamToken::create([
            'exam_session_id' => $session->id,
            'exam_room_id' => $request->input('exam_room_id'),
            'token' => $this->examService->generateToken(6),
            'issued_at' => now(),
            'expires_at' => now()->addMinutes((int) $duration),
            'is_active' => true,
        ]);

        return back()->with('success', 'Token baru berhasil dibuat.');
    }

    public function deactivateToken(ExamSession $session, ExamToken $token): RedirectResponse
    {
        abort_unless($token->exam_session_id === $session->id, 404);
        $token->update(['is_active' => false]);
        return back()->with('success', 'Token dinonaktifkan.');
    }
}
