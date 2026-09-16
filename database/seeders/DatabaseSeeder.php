<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*
         * ============================================================
         * 1. USERS
         * ============================================================
         */

        // 1 Admin
        $admin = User::factory()->create([
            'name' => 'Admin KampusLMS',
            'email' => 'admin@kampuslms.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'nim_nip' => null,
        ]);

        // 3 Dosen
        $lecturers = User::factory()
            ->count(3)
            ->sequence(
                [
                    'name' => 'Dosen Demo 1',
                    'email' => 'dosen@kampuslms.test',
                    'password' => Hash::make('password'),
                    'role' => 'dosen',
                    'nim_nip' => '198001001',
                ],
                [
                    'name' => 'Dosen Demo 2',
                    'email' => 'dosen2@kampuslms.test',
                    'password' => Hash::make('password'),
                    'role' => 'dosen',
                    'nim_nip' => '198001002',
                ],
                [
                    'name' => 'Dosen Demo 3',
                    'email' => 'dosen3@kampuslms.test',
                    'password' => Hash::make('password'),
                    'role' => 'dosen',
                    'nim_nip' => '198001003',
                ],
            )
            ->create();

        // 1 Mahasiswa Demo
        $demoStudent = User::factory()->create([
            'name' => 'Mahasiswa Demo',
            'email' => 'mahasiswa@kampuslms.test',
            'password' => Hash::make('password'),
            'role' => 'mahasiswa',
            'nim_nip' => '202500001',
        ]);

        // 29 Mahasiswa lainnya
        $otherStudents = User::factory()
            ->count(29)
            ->create([
                'role' => 'mahasiswa',
            ]);

        // Total mahasiswa = 30
        $students = $otherStudents->prepend($demoStudent);

        /*
         * ============================================================
         * 2. COURSES
         * ============================================================
         */

        $courseNames = [
            'Pemrograman Web',
            'Basis Data',
            'Rekayasa Perangkat Lunak',
            'Jaringan Komputer',
            'Analisis dan Perancangan Sistem',
        ];

        $courses = collect();

        foreach ($courseNames as $index => $courseName) {
            $course = Course::factory()->create([
                'code' => 'MK' . str_pad(
                    (string) ($index + 1),
                    5,
                    '0',
                    STR_PAD_LEFT
                ),
                'name' => $courseName,
                'description' => 'Mata kuliah ' . $courseName,
                'sks' => 3,
                'lecturer_id' => $lecturers[$index % 3]->id,
                'status' => 'active',
            ]);

            /*
             * Setiap course memiliki 15 mahasiswa.
             */
            $courseStudents = $students
                ->shuffle()
                ->take(15);

            /*
             * Masukkan mahasiswa ke tabel course_user.
             */
            foreach ($courseStudents as $student) {
                DB::table('course_user')->insert([
                    'course_id' => $course->id,
                    'user_id' => $student->id,
                    'enrolled_at' => now(),
                ]);
            }

            $courses->push([
                'course' => $course,
                'students' => $courseStudents,
            ]);
        }

        /*
         * ============================================================
         * 3. ASSIGNMENTS + SUBMISSIONS
         * ============================================================
         */

        $allSubmissions = collect();

        foreach ($courses as $courseData) {
            $course = $courseData['course'];
            $courseStudents = $courseData['students'];

            /*
             * Setiap course memiliki 3 assignment.
             *
             * 5 course x 3 assignment = 15 assignment
             */
            $assignments = collect();

            /*
            * Assignment 1:
            * Sudah lewat deadline
            */
            $assignments->push(
                Assignment::factory()->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                    'title' => 'Tugas 1 - Materi Dasar',
                    'due_at' => now()->subDays(7),
                    'status' => 'published',
                ])
            );

            /*
            * Assignment 2:
            * Masih aktif
            */
            $assignments->push(
                Assignment::factory()->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                    'title' => 'Tugas 2 - Implementasi',
                    'due_at' => now()->addDays(7),
                    'status' => 'published',
                ])
            );

            /*
            * Assignment 3:
            * Draft
            */
            $assignments->push(
                Assignment::factory()->create([
                    'course_id' => $course->id,
                    'created_by' => $course->lecturer_id,
                    'title' => 'Tugas 3 - Project',
                    'due_at' => now()->addDays(14),
                    'status' => 'draft',
                ])
            );

            /*
             * Hanya mahasiswa yang terdaftar di course
             * yang boleh membuat submission.
             *
             * 5 x 3 x 15 = 225 submission
             */
            foreach ($assignments as $assignment) {
                foreach ($courseStudents as $student) {
                    $isLate = fake()->boolean(20);
                    $submittedAt = $isLate
                        ? $assignment->due_at->copy()->addDays(1)
                        : $assignment->due_at->copy()->subDays(1);
                    $submission = Submission::factory()->create([
                        'assignment_id' => $assignment->id,
                        'user_id' => $student->id,
                        'submitted_at' => $submittedAt,
                        'is_late' => $isLate,
                    ]);
                    $allSubmissions->push($submission);
                }
            }
        }

        /*
         * ============================================================
         * 4. GRADES
         * ============================================================
         */

        /*
         * 60% dari 225 submission = 135 grade.
         */
        $gradedCount = (int) floor(
            $allSubmissions->count() * 0.60
        );

        $submissionsToGrade = $allSubmissions
            ->shuffle()
            ->take($gradedCount);

        foreach ($submissionsToGrade as $submission) {
            $assignment = Assignment::findOrFail(
                $submission->assignment_id
            );

            Grade::create([
                'submission_id' => $submission->id,
                'graded_by' => $assignment->created_by,
                'score' => fake()->numberBetween(60, 100),
                'feedback' => fake()->sentence(),
                'graded_at' => now(),
            ]);
        }

        /*
         * ============================================================
         * 5. OUTPUT
         * ============================================================
         */

        $this->command?->info('');
        $this->command?->info('==========================================');
        $this->command?->info('KampusLMS seeding selesai!');
        $this->command?->info('==========================================');

        $this->command?->info(
            'Admin      : ' . $admin->email
        );

        $this->command?->info(
            'Dosen      : ' . $lecturers->count()
        );

        $this->command?->info(
            'Mahasiswa  : ' . $students->count()
        );

        $this->command?->info(
            'Course     : ' . $courses->count()
        );

        $this->command?->info(
            'Assignment : ' . Assignment::count()
        );

        $this->command?->info(
            'Submission : ' . $allSubmissions->count()
        );

        $this->command?->info(
            'Graded     : ' . $gradedCount
        );

        $this->command?->info('==========================================');
        $this->command?->info('');
    }
}