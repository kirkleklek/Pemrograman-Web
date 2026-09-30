<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Course $course)
    {
        $user = auth()->user();

        if ($user->role === 'dosen') {
            abort_unless(
                $course->lecturer_id === $user->id,
                403
            );
        } else {
            abort_unless(
                $user->courses()->whereKey($course->id)->exists(),
                403
            );
        }

        $assignments = $course->assignments()
            ->orderBy('due_at')
            ->get();

        return view('assignments.index', compact('course', 'assignments'));
    }

    public function create(Course $course)
    {
        abort_unless(
            auth()->user()->role === 'dosen'
                && $course->lecturer_id === auth()->id(),
            403
        );

        return view('assignments.create', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        abort_unless(
            auth()->user()->role === 'dosen'
                && $course->lecturer_id === auth()->id(),
            403
        );

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'due_at' => ['required', 'date'],
            'max_score' => ['required', 'integer', 'between:0,100'],
            'allow_late' => ['required', 'boolean'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $assignment = $course->assignments()->create([
            'created_by' => auth()->id(),
            'title' => $validated['title'],
            'instructions' => $validated['instructions'],
            'due_at' => $validated['due_at'],
            'max_score' => $validated['max_score'],
            'allow_late' => $validated['allow_late'],
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('dosen.assignments.show', $assignment)
            ->with('success', 'Tugas berhasil ditambahkan.');
    }

    public function show(Assignment $assignment)
    {
        $user = auth()->user();
        $course = $assignment->course;

        if ($user->role === 'dosen') {
            abort_unless(
                $course->lecturer_id === $user->id,
                403
            );
        } else {
            abort_unless(
                $user->courses()->whereKey($course->id)->exists(),
                403
            );
        }

        return view('assignments.show', compact('course', 'assignment'));
    }

    public function edit(Assignment $assignment)
    {
        $course = $assignment->course;

        abort_unless(
            auth()->user()->role === 'dosen'
                && $course->lecturer_id === auth()->id(),
            403
        );

        return view('assignments.edit', compact('course', 'assignment'));
    }

    public function update(Request $request, Assignment $assignment)
    {
        $course = $assignment->course;

        abort_unless(
            auth()->user()->role === 'dosen'
                && $course->lecturer_id === auth()->id(),
            403
        );

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'due_at' => ['required', 'date'],
            'max_score' => ['required', 'integer', 'between:0,100'],
            'allow_late' => ['required', 'boolean'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $assignment->update([
            'title' => $validated['title'],
            'instructions' => $validated['instructions'],
            'due_at' => $validated['due_at'],
            'max_score' => $validated['max_score'],
            'allow_late' => $validated['allow_late'],
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('dosen.assignments.show', $assignment)
            ->with('success', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Assignment $assignment)
    {
        $course = $assignment->course;

        abort_unless(
            auth()->user()->role === 'dosen'
                && $course->lecturer_id === auth()->id(),
            403
        );

        $assignment->delete();

        return redirect()
            ->route('dosen.courses.assignments.index', $course)
            ->with('success', 'Tugas berhasil dihapus.');
    }
}