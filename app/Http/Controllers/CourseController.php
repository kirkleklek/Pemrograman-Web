<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\User;
class CourseController extends Controller
{
    public function index(Request $request)
    {
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
=======
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('lecturer')->orderBy('code')->get();
>>>>>>> 1ab157c195b3f37e9d83bf200db789f7c6fa3521

        return view('courses.index', compact('courses'));
    }

    public function create()
    {
        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.create', compact('lecturers'));
    }

<<<<<<< HEAD
    public function store(StoreCourseRequest $request)
    {
        $course = Course::create($request->validated());
=======
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $course = Course::create($validated);
>>>>>>> 1ab157c195b3f37e9d83bf200db789f7c6fa3521

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function show(Course $course)
    {
        $course->load('lecturer');

        return view('courses.show', compact('course'));
    }

    public function edit(Course $course)
    {
        $lecturers = User::where('role', 'dosen')->orderBy('name')->get();

        return view('courses.edit', compact('course', 'lecturers'));
    }

<<<<<<< HEAD
    public function update(UpdateCourseRequest $request, Course $course)
    {
        $course->update($request->validated());
=======
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate($this->rules($course));

        $course->update($validated);
>>>>>>> 1ab157c195b3f37e9d83bf200db789f7c6fa3521

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Mata kuliah berhasil diperbarui.');
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', 'Mata kuliah berhasil dihapus.');
    }
<<<<<<< HEAD
}
=======

    private function rules(?Course $course = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('courses', 'code')->ignore($course),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sks' => ['required', 'integer', 'min:1', 'max:6'],
            'lecturer_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'dosen')),
            ],
            'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
        ];
    }
}
>>>>>>> 1ab157c195b3f37e9d83bf200db789f7c6fa3521
