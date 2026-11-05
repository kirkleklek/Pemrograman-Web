<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Services\DashboardCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    /**
     * Menampilkan mahasiswa yang terdaftar
     * dan mahasiswa yang masih dapat didaftarkan.
     */
    public function index(Course $course): View
    {
        Gate::authorize('manageEnrollments', $course);

        $students = $course->students()
            ->where('users.role', 'mahasiswa')
            ->orderBy('users.name')
            ->paginate(
                15,
                ['users.id', 'users.name', 'users.email', 'users.nim_nip'],
                'students_page'
            )
            ->withQueryString();

        $availableStudents = User::query()
            ->where('role', 'mahasiswa')
            ->whereDoesntHave('courses', function ($query) use ($course) {
                $query->where('courses.id', $course->id);
            })
            ->orderBy('name')
            ->paginate(
                15,
                ['id', 'name', 'email', 'nim_nip'],
                'available_page'
            )
            ->withQueryString();

        return view('enrollments.index', [
            'course' => $course,
            'students' => $students,
            'availableStudents' => $availableStudents,
        ]);
    }

    public function store(
        Request $request,
        Course $course
    ): RedirectResponse {
        Gate::authorize('manageEnrollments', $course);

        $validated = $request->validate([
            'student_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', 'mahasiswa')
                    ->whereNull('deleted_at'),
            ],
        ]);

        $studentId = (int) $validated['student_id'];

        if (
            $course->students()
                ->whereKey($studentId)
                ->exists()
        ) {
            return back()
                ->withErrors([
                    'student_id' => 'Mahasiswa sudah terdaftar pada mata kuliah ini.',
                ])
                ->withInput();
        }

        // Mendaftarkan mahasiswa.
        $course->students()->attach($studentId, [
            'enrolled_at' => now(),
        ]);

        // Menghapus cache dashboard dosen dan mahasiswa terkait.
        $this->dashboardCache->forgetCourse($course->id);

        return back()->with(
            'success',
            'Mahasiswa berhasil didaftarkan ke mata kuliah.'
        );
    }

    public function destroy(
        Course $course,
        User $student
    ): RedirectResponse {
        Gate::authorize('manageEnrollments', $course);

        abort_unless($student->role === 'mahasiswa', 404);

        $isEnrolled = $course->students()
            ->whereKey($student->id)
            ->exists();

        abort_unless($isEnrolled, 404);

        // Mengeluarkan mahasiswa dari mata kuliah.
        $course->students()->detach($student->id);

        // Menghapus cache mahasiswa yang dikeluarkan.
        $this->dashboardCache->forgetStudent((int) $student->id);

        // Menghapus cache dosen dan mahasiswa yang masih terdaftar.
        $this->dashboardCache->forgetCourse($course->id);

        return back()->with(
            'success',
            'Mahasiswa berhasil dikeluarkan dari mata kuliah.'
        );
    }

    public function __construct(
        private DashboardCacheService $dashboardCache
    ) {}
}
