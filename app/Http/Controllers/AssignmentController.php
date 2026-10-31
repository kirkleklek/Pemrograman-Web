<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Course;
use App\Rules\MaxTextBytes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AssignmentController extends Controller
{
    public function index(Course $course)
    {
        $user = Auth::user();

        Gate::authorize('viewAny', [Assignment::class, $course]);

        $assignments = $course->assignments()
            ->when(
                $user->role === 'mahasiswa',
                fn ($query) => $query->where('status', 'published')
            )
            ->orderBy('due_at')
            ->paginate(15)
            ->withQueryString();

        return view(
            'assignments.index',
            compact('course', 'assignments')
        );
    }

    public function create(Course $course)
    {
        Gate::authorize('create', [Assignment::class, $course]);

        return view('assignments.create', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        Gate::authorize('create', [Assignment::class, $course]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => [
                'required',
                'string',
                new MaxTextBytes,
            ],
            'due_at' => ['required', 'date'],
            'max_score' => ['required', 'integer', 'between:0,100'],
            'allow_late' => ['required', 'boolean'],
            'status' => ['required', 'in:draft,published'],
        ]);

        $assignment = $course->assignments()->create([
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'instructions' => $validated['instructions'],
            'due_at' => $validated['due_at'],
            'max_score' => $validated['max_score'],
            'allow_late' => $validated['allow_late'],
            'status' => $validated['status'],
        ]);

        $routePrefix = Auth::user()->role === 'admin'
            ? 'admin'
            : 'dosen';

        return redirect()
            ->route("{$routePrefix}.assignments.show", $assignment)
            ->with('success', 'Tugas berhasil ditambahkan.');
    }

    public function show(Assignment $assignment)
    {
        Gate::authorize('view', $assignment);

        $course = $assignment->course;

        return view(
            'assignments.show',
            compact('course', 'assignment')
        );
    }

    public function edit(Assignment $assignment)
    {
        Gate::authorize('update', $assignment);

        $course = $assignment->course;

        return view(
            'assignments.edit',
            compact('course', 'assignment')
        );
    }

    public function update(Request $request, Assignment $assignment)
    {
        Gate::authorize('update', $assignment);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => [
                'required',
                'string',
                new MaxTextBytes,
            ],
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

        $routePrefix = Auth::user()->role === 'admin'
            ? 'admin'
            : 'dosen';

        return redirect()
            ->route("{$routePrefix}.assignments.show", $assignment)
            ->with('success', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Assignment $assignment)
    {
        Gate::authorize('delete', $assignment);

        $course = $assignment->course;

        $assignment->delete();

        $routePrefix = Auth::user()->role === 'admin'
            ? 'admin'
            : 'dosen';

        return redirect()
            ->route("{$routePrefix}.courses.assignments.index", $course)
            ->with('success', 'Tugas berhasil dihapus.');
    }
}
