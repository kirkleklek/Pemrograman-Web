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
use RuntimeException;
use Throwable;

class MaterialController extends Controller
{
    public function index(Course $course)
    {
        Gate::authorize('viewAny', [Material::class, $course]);

        $materials = $course->materials()
            ->latest()
            ->paginate(15)
            ->withQueryString();

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
            'file' => [
                'required_if:type,file',
                'nullable',
                'file',
                'mimes:pdf,pptx,docx',
                'max:20480',
            ],
            'external_url' => [
                'required_if:type,link',
                'nullable',
                'url',
                'max:255',
            ],
        ]);

        $data = [
            'course_id' => $course->id,
            'uploaded_by' => Auth::id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
        ];

        $storedPath = null;

        try {
            if ($validated['type'] === 'file') {
                $file = $request->file('file');

                $storedPath = $file->store('materials', 'local');

                if (! $storedPath) {
                    throw new RuntimeException(
                        'Berkas materi gagal disimpan.'
                    );
                }

                $data['file_path'] = $storedPath;
                $data['original_name'] = $file->getClientOriginalName();
                $data['file_size'] = $file->getSize();
                $data['mime_type'] = $file->getMimeType();
            } else {
                $data['external_url'] = $validated['external_url'];
            }

            // 
            Material::create($data);
        } catch (Throwable $exception) {
            // Hindari file yatim jika penyimpanan record gagal.
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }

        $routePrefix = Auth::user()->role === 'admin'
            ? 'admin'
            : 'dosen';

        return redirect()
            ->route("{$routePrefix}.courses.materials.index", $course)
            ->with('success', 'Materi berhasil ditambahkan.');
    }

    public function show(Material $material)
    {
        Gate::authorize('view', $material);

        $course = $material->course;

        return view('materials.show', compact('material', 'course'));
    }

    public function download(Material $material)
    {
        Gate::authorize('view', $material);

        abort_unless(
            $material->type === 'file' && filled($material->file_path),
            404
        );

        $disk = Storage::disk('local');

        abort_unless($disk->exists($material->file_path), 404);

        return $disk->download(
            $material->file_path,
            $material->original_name ?: basename($material->file_path)
        );
    }

    public function edit(Material $material)
    {
        Gate::authorize('update', $material);

        $course = $material->course;

        return view('materials.edit', compact('material', 'course'));
    }

    public function update(Request $request, Material $material)
    {
        Gate::authorize('update', $material);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', new MaxTextBytes],
            'type' => ['required', 'in:file,link'],
            'file' => [
                Rule::requiredIf(
                    $request->input('type') === 'file'
                    && ! $material->file_path
                ),
                'nullable',
                'file',
                'mimes:pdf,pptx,docx',
                'max:20480',
            ],
            'external_url' => [
                'required_if:type,link',
                'nullable',
                'url',
                'max:255',
            ],
        ]);

        $oldPath = $material->file_path;
        $newPath = null;

        $material->title = $validated['title'];
        $material->description = $validated['description'] ?? null;
        $material->type = $validated['type'];

        try {
            if ($validated['type'] === 'file') {
                if ($request->hasFile('file')) {
                    $file = $request->file('file');

                    $newPath = $file->store('materials', 'local');

                    if (! $newPath) {
                        throw new RuntimeException(
                            'Berkas materi gagal disimpan.'
                        );
                    }

                    $material->file_path = $newPath;
                    $material->original_name =
                        $file->getClientOriginalName();
                    $material->file_size = $file->getSize();
                    $material->mime_type = $file->getMimeType();
                }

                $material->external_url = null;
            } else {
                $material->file_path = null;
                $material->original_name = null;
                $material->file_size = null;
                $material->mime_type = null;
                $material->external_url = $validated['external_url'];
            }

            $material->save();
        } catch (Throwable $exception) {
            // Record lama tetap menunjuk ke file lama jika save gagal.
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }

            throw $exception;
        }

        // Hapus file lama hanya setelah perubahan record berhasil.
        if ($oldPath && $oldPath !== $material->file_path) {
            Storage::disk('local')->delete($oldPath);
        }

        $routePrefix = Auth::user()->role === 'admin'
            ? 'admin'
            : 'dosen';

        return redirect()
            ->route("{$routePrefix}.materials.show", $material)
            ->with('success', 'Materi berhasil diperbarui.');
    }

    public function destroy(Material $material)
    {
        Gate::authorize('delete', $material);

        $course = $material->course;

        // Event model menghapus file setelah record berhasil dihapus.
        $material->delete();

        $routePrefix = Auth::user()->role === 'admin'
            ? 'admin'
            : 'dosen';

        return redirect()
            ->route("{$routePrefix}.courses.materials.index", $course)
            ->with('success', 'Materi berhasil dihapus.');
    }
}
