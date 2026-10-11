<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BulanAuditDemoSeeder extends Seeder
{
    public function run(): void
    {
        // The automatic suite and the live demo deliberately use different DBs.
        if (! app()->environment('testing') || DB::connection()->getDriverName() !== 'mysql'
            || DB::selectOne('SELECT DATABASE() AS name')->name !== 'kampuslms_uts_demo') {
            throw new \RuntimeException('Seeder only supports testing / kampuslms_uts_demo.');
        }
        $credential = getenv('BULAN_DEMO_PASSWORD');
        if (! is_string($credential) || strlen($credential) < 8) {
            throw new \RuntimeException('Set BULAN_DEMO_PASSWORD privately (at least 8 characters).');
        }
        if (User::withTrashed()->where('email', 'like', '%@audit.example.test')->exists()) {
            throw new \RuntimeException('Demo fixture already exists; reuse it or prepare a clean demo database.');
        }
        $rows = DB::transaction(function () use ($credential) {
            $students = $lecturers = $courses = $rows = [];
            $admin = User::factory()->create(['name' => 'Admin Audit', 'email' => 'admin@audit.example.test', 'role' => 'admin', 'password' => $credential]);
            $rows[] = ['admin', $admin->email, $admin->id, '-', '-', '-', '-'];
            foreach (['A', 'B'] as $index => $label) {
                $students[$index] = User::factory()->create(['name' => 'Mahasiswa '.$label, 'email' => 'mahasiswa-'.strtolower($label).'@audit.example.test', 'role' => 'mahasiswa', 'password' => $credential]);
                $lecturers[$index] = User::factory()->create(['name' => 'Dosen '.$label, 'email' => 'dosen-'.strtolower($label).'@audit.example.test', 'role' => 'dosen', 'password' => $credential]);
                $courses[$index] = Course::factory()->create(['code' => 'AUDIT-'.$label, 'name' => 'Mata Kuliah Privat '.$label, 'lecturer_id' => $lecturers[$index]->id]);
                $courses[$index]->students()->attach($students[$index]->id, ['enrolled_at' => now()]);
                $material = Material::create(['course_id' => $courses[$index]->id, 'uploaded_by' => $lecturers[$index]->id, 'title' => 'Materi privat '.$label, 'description' => 'Deskripsi privat '.$label, 'type' => 'link', 'external_url' => 'https://example.test/materi-'.$label]);
                $assignment = Assignment::factory()->create(['course_id' => $courses[$index]->id, 'created_by' => $lecturers[$index]->id, 'title' => 'Tugas privat '.$label, 'status' => 'published', 'due_at' => now()->addWeek(), 'allow_late' => false]);
                $submission = $this->submission($assignment, $students[$index], 'privat-'.$label);
                foreach ([$students[$index], $lecturers[$index]] as $user) {
                    $notification = (string) Str::uuid();
                    $user->notifications()->create(['id' => $notification, 'type' => 'AuditNotification', 'data' => ['message' => 'Notifikasi privat '.$user->name]]);
                    $rows[] = [$user->name, $user->email, $user->id, $courses[$index]->id, $material->id, $assignment->id, $submission->id.' / notification '.$notification];
                }
            }
            $shared = Course::factory()->create(['code' => 'AUDIT-SHARED', 'name' => 'Kelas Bersama Audit', 'lecturer_id' => $lecturers[0]->id]);
            $shared->students()->attach([$students[0]->id => ['enrolled_at' => now()], $students[1]->id => ['enrolled_at' => now()]]);
            $assignment = Assignment::factory()->create(['course_id' => $shared->id, 'created_by' => $lecturers[0]->id, 'title' => 'Tugas bersama audit', 'status' => 'published', 'due_at' => now()->addWeek()]);
            foreach ($students as $index => $student) {
                $submission = $this->submission($assignment, $student, 'shared-'.$index);
                $rows[] = ['Shared '.$student->name, $student->email, $student->id, $shared->id, '-', $assignment->id, $submission->id];
            }

            return $rows;
        });
        $this->command?->table(['Actor', 'Email', 'User ID', 'Course ID', 'Material ID', 'Assignment ID', 'Submission / notification'], $rows);
    }

    private function submission(Assignment $assignment, User $student, string $label): Submission
    {
        $content = 'Jawaban sintetis '.$label;
        $path = 'submissions/audit-demo-'.$label.'.txt';
        Storage::disk('local')->put($path, $content);

        return Submission::factory()->create([
            'assignment_id' => $assignment->id, 'user_id' => $student->id,
            'file_path' => $path, 'original_name' => 'audit-demo-'.$label.'.txt',
            'file_size' => strlen($content), 'note' => 'Catatan privat '.$label,
            'submitted_at' => now(), 'is_late' => false,
        ]);
    }
}
