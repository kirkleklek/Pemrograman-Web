<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Course::class);

        $search = trim($request->input('q', ''));
        $status = $request->input('status');

        $courses = Course::query()
            ->with('lecturer')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('courses.index', compact('courses'));
    }

    public function lecturerIndex(Request $request)
    {
        Gate::authorize('viewTeaching', Course::class);

        $search = trim($request->input('q', ''));

        $courses = auth()->user()
            ->taughtCourses()
            ->with('lecturer')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('courses.lecturer-index', compact('courses'));
    }

    public function create()
    {
        Gate::authorize('create', Course::class);

        $lecturers = User::where('role', 'dosen')
            ->orderBy('name')
            ->get();

        return view('courses.create', compact('lecturers'));
    }

    public function store(StoreCourseRequest $request)
    {
        Gate::authorize('create', Course::class);

        $course = Course::create($request->validated());

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function show(Course $course)
    {
        Gate::authorize('view', $course);

        $course->load('lecturer');

        return view('courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        Gate::authorize('update', $course);

        $lecturers = User::where('role', 'dosen')
            ->orderBy('name')
            ->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

    public function update(
        UpdateCourseRequest $request,
        Course $course
    ) {
        Gate::authorize('update', $course);

        $course->update($request->validated());

        return redirect()
            ->route('admin.courses.show', $course)
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        Gate::authorize('delete', $course);

        $course->delete();

        return redirect()
            ->route('admin.courses.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }
}
