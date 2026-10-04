<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaterialResource;
use App\Models\Course;
use Illuminate\Http\Request;

class MaterialController extends Controller
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

        $materials = $course->materials()
            ->with('uploader')
            ->orderBy('id')
            ->paginate(15);

        return MaterialResource::collection(
            $materials->getCollection()
        )->additional([
            'meta' => [
                'current_page' => $materials->currentPage(),
                'last_page' => $materials->lastPage(),
                'total' => $materials->total(),
            ],
        ]);
    }
}