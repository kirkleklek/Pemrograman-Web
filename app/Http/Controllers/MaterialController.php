<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    public function index(Course $course)
    {
        $user = Auth::user();

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

        $materials = $course->materials()
            ->latest()
            ->get();

        return view('materials.index', compact('course', 'materials'));
    }

    public function create(Course $course)
    {
        abort_unless(
            Auth::user()->role === 'dosen'
                && $course->lecturer_id === Auth::id(),
            403
        );

        return view('materials.create', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        abort_unless(
            Auth::user()->role === 'dosen'
                && $course->lecturer_id === Auth::id(),
            403
        );

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:file,link'],
            'file' => ['required_if:type,file', 'nullable', 'file', 'mimes:pdf,pptx'],
            'external_url' => ['required_if:type,link', 'nullable', 'url'],
        ]);

        $data = [
            'course_id' => $course->id,
            'uploaded_by' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
        ];

        if ($validated['type'] === 'file') {
            $file = $request->file('file');

            $data['file_path'] = $file->store('materials');
            $data['original_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
            $data['mime_type'] = $file->getMimeType();
        } else {
            $data['external_url'] = $validated['external_url'];
        }

        $material = Material::create($data);

        return redirect()
            ->route('dosen.courses.materials.index', $course)
            ->with('success', 'Materi berhasil ditambahkan.');
    }

    public function show(Material $material)
    {
        $user = Auth::user();
        $course = $material->course;

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

        return view('materials.show', compact('material', 'course'));
    }

    public function edit(Material $material)
    {
        $course = $material->course;

        abort_unless(
            Auth::user()->role === 'dosen'
                && $course->lecturer_id === Auth::id(),
            403
        );

        return view('materials.edit', compact('material', 'course'));
    }

    public function update(Request $request, Material $material)
    {
        $course = $material->course;

        abort_unless(
            Auth::user()->role === 'dosen'
                && $course->lecturer_id === Auth::id(),
            403
        );

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:file,link'],
            'file' => ['nullable', 'file', 'mimes:pdf,pptx'],
            'external_url' => ['required_if:type,link', 'nullable', 'url'],
        ]);

        $material->title = $validated['title'];
        $material->description = $validated['description'] ?? null;
        $material->type = $validated['type'];

        if ($validated['type'] === 'file') {
            if ($request->hasFile('file')) {
                if ($material->file_path) {
                    Storage::delete($material->file_path);
                }

                $file = $request->file('file');

                $material->file_path = $file->store('materials');
                $material->original_name = $file->getClientOriginalName();
                $material->file_size = $file->getSize();
                $material->mime_type = $file->getMimeType();
            }

            $material->external_url = null;
        } else {
            if ($material->file_path) {
                Storage::delete($material->file_path);
            }

            $material->file_path = null;
            $material->original_name = null;
            $material->file_size = null;
            $material->mime_type = null;
            $material->external_url = $validated['external_url'];
        }

        $material->save();

        return redirect()
            ->route('dosen.materials.show', $material)
            ->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Material $material)
    {
        $course = $material->course;

        abort_unless(
            Auth::user()->role === 'dosen'
                && $course->lecturer_id === Auth::id(),
            403
        );

        if ($material->file_path) {
            Storage::delete($material->file_path);
        }

        $material->delete();

        return redirect()
            ->route('dosen.courses.materials.index', $course)
            ->with('success', 'Materi berhasil dihapus.');
    }
}