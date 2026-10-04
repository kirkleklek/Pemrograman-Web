<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\UpdateAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use App\Models\Course;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function index(Request $request, Course $course)
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

        $assignments = $course->assignments()
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where(
                    'status',
                    $request->input('status')
                )
            )
            ->orderBy('due_at')
            ->paginate(15);

        return AssignmentResource::collection(
            $assignments->getCollection()
        )->additional([
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'last_page' => $assignments->lastPage(),
                'total' => $assignments->total(),
            ],
        ]);
    }

    public function store(StoreAssignmentRequest $request)
    {
        $validated = $request->validated();

        $course = Course::findOrFail($validated['course_id']);

        abort_unless(
            $course->lecturer_id === $request->user()->id,
            403,
            'Anda tidak memiliki akses ke sumber daya ini.'
        );

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'created_by' => $request->user()->id,
            'title' => $validated['title'],
            'instructions' => $validated['instructions'],
            'due_at' => $validated['due_at'],
            'max_score' => $validated['max_score'],
            'allow_late' => $validated['allow_late'] ?? true,
            'status' => $validated['status'],
        ]);

        return (new AssignmentResource($assignment))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateAssignmentRequest $request,
        Assignment $assignment
    ) {
        abort_unless(
            $assignment->course->lecturer_id === $request->user()->id,
            403,
            'Anda tidak memiliki akses ke sumber daya ini.'
        );

        $assignment->update($request->validated());

        return new AssignmentResource($assignment);
    }

    public function destroy(
        Request $request,
        Assignment $assignment
    ) {
        abort_unless(
            $assignment->course->lecturer_id === $request->user()->id,
            403,
            'Anda tidak memiliki akses ke sumber daya ini.'
        );

        $assignment->delete();

        return response()->json(null, 204);
    }
}