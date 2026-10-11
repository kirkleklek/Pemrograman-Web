<?php

namespace Tests\Support;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait IntegrationFixture
{
    protected array $students;

    protected array $lecturers;

    protected array $courses;

    protected array $materials;

    protected array $assignments;

    protected array $submissions;

    protected array $privateSubmissions;

    protected Course $sharedCourse;

    protected Assignment $sharedAssignment;

    protected function createIntegrationFixture(): void
    {
        Storage::fake('local');
        foreach (['A', 'B'] as $index => $label) {
            $this->students[$index] = User::factory()->create([
                'role' => 'mahasiswa', 'name' => 'Mahasiswa '.$label,
                'email' => 'mahasiswa-'.strtolower($label).'@audit.example.test',
            ]);
            $this->lecturers[$index] = User::factory()->create([
                'role' => 'dosen', 'name' => 'Dosen '.$label,
                'email' => 'dosen-'.strtolower($label).'@audit.example.test',
            ]);
            $this->courses[$index] = Course::factory()->create([
                'lecturer_id' => $this->lecturers[$index]->id, 'code' => 'AUDIT-'.$label,
                'name' => 'Mata Kuliah Privat '.$label,
            ]);
            $this->courses[$index]->students()->attach($this->students[$index], ['enrolled_at' => now()]);
            $this->materials[$index] = Material::create([
                'course_id' => $this->courses[$index]->id,
                'uploaded_by' => $this->lecturers[$index]->id,
                'title' => 'Materi privat '.$label, 'description' => 'Deskripsi rahasia '.$label,
                'type' => 'file', 'file_path' => 'materials/privat-'.$label.'.pdf',
                'original_name' => 'privat-'.$label.'.pdf', 'file_size' => 20,
                'mime_type' => 'application/pdf',
            ]);
            Storage::disk('local')->put($this->materials[$index]->file_path, 'Berkas materi '.$label);
            $this->assignments[$index] = $this->fixtureAssignment($this->courses[$index], [
                'title' => 'Tugas privat '.$label, 'instructions' => 'Instruksi rahasia '.$label,
            ]);
            $this->submissions[$index] = Submission::factory()->create([
                'assignment_id' => $this->assignments[$index]->id,
                'user_id' => $this->students[$index]->id,
                'note' => 'Catatan privat '.$label, 'original_name' => 'jawaban-privat-'.$label.'.pdf',
            ]);
            Storage::disk('local')->put($this->submissions[$index]->file_path, 'Jawaban '.$label);
        }
        // Both students share a course: a rejected read must test submission
        // ownership independently of enrollment, in both directions.
        $this->sharedCourse = Course::factory()->create([
            'lecturer_id' => $this->lecturers[0]->id, 'code' => 'AUDIT-SHARED',
        ]);
        $this->sharedCourse->students()->attach([
            $this->students[0]->id => ['enrolled_at' => now()],
            $this->students[1]->id => ['enrolled_at' => now()],
        ]);
        $this->sharedAssignment = $this->fixtureAssignment($this->sharedCourse);
        foreach ($this->students as $index => $student) {
            $this->privateSubmissions[$index] = Submission::factory()->create([
                'assignment_id' => $this->sharedAssignment->id, 'user_id' => $student->id,
                'note' => 'Jawaban pribadi shared '.$index,
                'original_name' => 'shared-pribadi-'.$index.'.pdf',
            ]);
        }
    }

    protected function fixtureAssignment(Course $course, array $attributes = []): Assignment
    {
        return Assignment::factory()->create(array_replace([
            'course_id' => $course->id, 'created_by' => $course->lecturer_id,
            'status' => 'published', 'due_at' => now()->addWeek(), 'allow_late' => false,
        ], $attributes));
    }

    protected function assignmentPayload(Course $course): array
    {
        return [
            'course_id' => $course->id, 'title' => 'Tugas audit baru',
            'instructions' => 'Kerjakan pengujian integrasi',
            'due_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'max_score' => 100, 'allow_late' => false, 'status' => 'published',
        ];
    }

    protected function materialPayload(): array
    {
        return ['title' => 'Materi audit baru', 'description' => 'Materi audit',
            'type' => 'link', 'external_url' => 'https://example.test/materi'];
    }

    protected function fixtureNotification(User $owner): string
    {
        $id = (string) Str::uuid();
        $owner->notifications()->create([
            'id' => $id, 'type' => 'AuditNotification',
            'data' => ['message' => 'Notifikasi privat '.$owner->name],
        ]);

        return $id;
    }

    protected function databaseSnapshot(): array
    {
        $snapshot = [];
        foreach (['users', 'courses', 'course_user', 'materials', 'assignments', 'submissions', 'grades', 'notifications'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
        }

        return $snapshot;
    }

    public static function directions(): array
    {
        return ['A to B' => [0, 1], 'B to A' => [1, 0]];
    }
}
