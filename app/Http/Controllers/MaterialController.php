<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Material;
use App\Rules\MaxTextBytes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    public function index(Course $course)
    {
        Gate::authorize('viewAny', [Material::class, $course]);

        $materials = $course->materials()
            ->latest()
            ->get();

        return view('materials.index', compact('course', 'materials'));
    }

    public function create(Course $course)
    {
        Gate::authorize('create', [Material::class, $course]);

        return view('materials.create', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        Gate::authorize('create', [Material::class, $course]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', new MaxTextBytes],
            'type' => ['required', 'in:file,link'],
            'file' => ['required_if:type,file', 'nullable', 'file', 'mimes:pdf,pptx'],
            'external_url' => ['required_if:type,link', 'nullable', 'url', 'max:255'],
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
        Gate::authorize('view', $material);

        $course = $material->course;

        return view('materials.show', compact('material', 'course'));
    }

    public function edit(Material $material)
    {
        $course = $material->course;

        Gate::authorize('update', $material);

        return view('materials.edit', compact('material', 'course'));
    }

    public function update(Request $request, Material $material)
    {
        $course = $material->course;

        Gate::authorize('update', $material);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', new MaxTextBytes],
            'type' => ['required', 'in:file,link'],
            'file' => [
                Rule::requiredIf($request->input('type') === 'file' && ! $material->file_path),
                'nullable',
                'file',
                'mimes:pdf,pptx',
            ],
            'external_url' => ['required_if:type,link', 'nullable', 'url', 'max:255'],
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

        Gate::authorize('delete', $material);

        if ($material->file_path) {
            Storage::delete($material->file_path);
        }

        $material->delete();

        return redirect()
            ->route('dosen.courses.materials.index', $course)
            ->with('success', 'Materi berhasil dihapus.');
    }
}
