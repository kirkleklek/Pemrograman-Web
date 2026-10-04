<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courses = Course::query()
            ->with('lecturer')
            ->withCount(['materials', 'assignments'])
            ->when(
                $user->role === 'dosen',
                fn ($query) => $query->where('lecturer_id', $user->id)
            )
            ->when(
                $user->role === 'mahasiswa',
                fn ($query) => $query->whereHas(
                    'students',
                    fn ($studentQuery) => $studentQuery->where(
                        'users.id',
                        $user->id
                    )
                )
            )
            ->orderBy('code')
            ->paginate(15);

        return CourseResource::collection($courses->getCollection())
            ->additional([
                'meta' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'total' => $courses->total(),
                ],
            ]);
    }

    public function show(Request $request, Course $course)
    {
        $user = $request->user();

        $hasAccess =
            $user->role === 'admin'
            || $course->lecturer_id === $user->id
            || (
                $user->role === 'mahasiswa'
                && $course->students()
                    ->where('users.id', $user->id)
                    ->exists()
            );

        abort_unless(
            $hasAccess,
            403,
            'Anda tidak memiliki akses ke sumber daya ini.'
        );

        $course->load('lecturer')
            ->loadCount(['materials', 'assignments']);

        return new CourseResource($course);
    }
}