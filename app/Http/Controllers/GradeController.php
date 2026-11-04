<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GradeController extends Controller
{
    /**
     * Rekap nilai untuk mata kuliah yang diampu dosen.
     */
    public function courseRecap(Course $course)
    {
        Gate::authorize('viewAny', [Grade::class, $course]);

        $submissions = Submission::query()
            ->whereHas('assignment', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->with([
                'student',
                'assignment',
                'grade.grader',
            ])
            ->orderBy('assignment_id')
            ->orderBy('user_id')
            ->paginate(20)
            ->withQueryString();

        return view('grades.course-recap', [
            'course' => $course,
            'submissions' => $submissions,
        ]);
    }

    /**
     * Daftar nilai milik mahasiswa yang sedang login.
     */
    public function studentIndex(Request $request)
    {
        abort_unless(
            $request->user()->role === 'mahasiswa',
            403,
            'Halaman ini hanya untuk mahasiswa.'
        );

        $submissions = Submission::query()
            ->where('user_id', $request->user()->id)
            ->whereHas('assignment.course.students', function ($query) use ($request) {
                $query->where('users.id', $request->user()->id);
            })
            ->with([
                'assignment.course',
                'grade.grader',
            ])
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('grades.student-index', [
            'submissions' => $submissions,
        ]);
    }
}
