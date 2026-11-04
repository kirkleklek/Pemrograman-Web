<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\FeedbackTemplate;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data demo KampusLMS.
     *
     * Seeder ini dirancang agar aman dijalankan berulang kali:
     * - Akun dicari berdasarkan email.
     * - Template feedback dicari berdasarkan dosen dan judul.
     * - Course dicari berdasarkan kode.
     * - Relasi mahasiswa disinkronkan melalui sync().
     * - Assignment dicari berdasarkan course dan judul.
     * - Submission dicari berdasarkan assignment dan mahasiswa.
     * - Grade dicari berdasarkan submission.
     *
     * Event model dinonaktifkan selama proses seeding agar pembuatan
     * data demo tidak mengirim notifikasi ke mahasiswa.
     */
    public function run(): void
    {
        $admin = null;
        $lecturers = collect();
        $students = collect();
        $courses = collect();
        $allAssignments = collect();
        $allSubmissions = collect();
        $gradedCount = 0;

        Model::withoutEvents(function () use (
            &$admin,
            &$lecturers,
            &$students,
            &$courses,
            &$allAssignments,
            &$allSubmissions,
            &$gradedCount
        ): void {
            /* =========================================================
             * 1. USERS
             * ======================================================= */

            $password = Hash::make('password');

            // Memperbarui akun demo yang sudah ada, termasuk yang soft-deleted.
            $upsertUser = function (array $identity, array $attributes) use ($password): User {
                $user = User::withTrashed()->updateOrCreate(
                    $identity,
                    array_merge($attributes, ['password' => $password])
                );

                if ($user->trashed()) {
                    $user->restore();
                }

                return $user;
            };

            // 1 Admin.
            $admin = $upsertUser(
                ['email' => 'admin@kampuslms.test'],
                [
                    'name' => 'Admin KampusLMS',
                    'role' => 'admin',
                    'nim_nip' => null,
                ]
            );

            // 3 Dosen.
            $lecturerData = [
                [
                    'name' => 'Dosen Demo 1',
                    'email' => 'dosen@kampuslms.test',
                    'nim_nip' => '198001001',
                ],
                [
                    'name' => 'Dosen Demo 2',
                    'email' => 'dosen2@kampuslms.test',
                    'nim_nip' => '198001002',
                ],
                [
                    'name' => 'Dosen Demo 3',
                    'email' => 'dosen3@kampuslms.test',
                    'nim_nip' => '198001003',
                ],
            ];

            $lecturers = collect($lecturerData)
                ->map(fn (array $data): User => $upsertUser(
                    ['email' => $data['email']],
                    [
                        'name' => $data['name'],
                        'role' => 'dosen',
                        'nim_nip' => $data['nim_nip'],
                    ]
                ))
                ->values();

            // 1 Mahasiswa demo.
            $demoStudent = $upsertUser(
                ['email' => 'mahasiswa@kampuslms.test'],
                [
                    'name' => 'Mahasiswa Demo',
                    'role' => 'mahasiswa',
                    'nim_nip' => '202500001',
                ]
            );

            // 29 Mahasiswa lainnya dengan identitas yang konsisten.
            $otherStudents = collect(range(2, 30))->map(
                function (int $number) use ($upsertUser): User {
                    $suffix = str_pad(
                        (string) $number,
                        3,
                        '0',
                        STR_PAD_LEFT
                    );

                    return $upsertUser(
                        ['email' => "mahasiswa{$suffix}@kampuslms.test"],
                        [
                            'name' => "Mahasiswa Demo {$number}",
                            'role' => 'mahasiswa',
                            'nim_nip' => (string) (202500000 + $number),
                        ]
                    );
                }
            );

            $students = $otherStudents
                ->prepend($demoStudent)
                ->values();

            /* =========================================================
             * 2. FEEDBACK TEMPLATES
             * ======================================================= */

            $feedbackTemplates = [
                [
                    'title' => 'Jawaban Sudah Baik',
                    'content' => 'Jawaban sudah baik dan menunjukkan pemahaman yang tepat terhadap materi.',
                ],
                [
                    'title' => 'Perlu Menambahkan Contoh',
                    'content' => 'Penjelasan sudah cukup baik, tetapi perlu menambahkan contoh agar konsep lebih mudah dipahami.',
                ],
                [
                    'title' => 'Perlu Penjelasan Lebih Rinci',
                    'content' => 'Beberapa bagian jawaban perlu dijelaskan lebih rinci agar alur pemikiran mudah diikuti.',
                ],
            ];

            foreach ($lecturers as $lecturer) {
                foreach ($feedbackTemplates as $template) {
                    FeedbackTemplate::updateOrCreate(
                        [
                            'lecturer_id' => $lecturer->id,
                            'title' => $template['title'],
                        ],
                        [
                            'content' => $template['content'],
                        ]
                    );
                }
            }

            /* =========================================================
             * 3. COURSES AND ENROLLMENTS
             * ======================================================= */

            $courseNames = [
                'Pemrograman Web',
                'Basis Data',
                'Rekayasa Perangkat Lunak',
                'Jaringan Komputer',
                'Analisis dan Perancangan Sistem',
            ];

            foreach ($courseNames as $index => $courseName) {
                $courseCode = 'MK' . str_pad(
                    (string) ($index + 1),
                    5,
                    '0',
                    STR_PAD_LEFT
                );

                $course = Course::updateOrCreate(
                    ['code' => $courseCode],
                    [
                        'name' => $courseName,
                        'description' => 'Mata kuliah ' . $courseName,
                        'sks' => 3,
                        'lecturer_id' => $lecturers[$index % 3]->id,
                        'status' => 'active',
                    ]
                );

                // Pilih 15 mahasiswa secara konsisten untuk setiap course.
                $courseStudents = collect(range(0, 14))->map(
                    fn (int $offset): User => $students->get(
                        ($index * 5 + $offset) % $students->count()
                    )
                );

                // Sinkronkan pivot agar tidak membuat relasi duplikat.
                $studentPivot = $courseStudents->mapWithKeys(
                    fn (User $student): array => [
                        $student->id => ['enrolled_at' => now()],
                    ]
                )->all();

                $course->students()->sync($studentPivot);

                $courses->push([
                    'course' => $course,
                    'students' => $courseStudents,
                ]);
            }

            /* =========================================================
             * 4. ASSIGNMENTS AND SUBMISSIONS
             * ======================================================= */

            foreach ($courses as $courseData) {
                /** @var Course $course */
                $course = $courseData['course'];
                $courseStudents = $courseData['students'];

                $assignmentDefinitions = [
                    [
                        'title' => 'Tugas 1 - Materi Dasar',
                        'instructions' => 'Kerjakan tugas materi dasar sesuai instruksi dosen.',
                        'due_at' => now()->copy()->subDays(7),
                        'status' => 'published',
                    ],
                    [
                        'title' => 'Tugas 2 - Implementasi',
                        'instructions' => 'Kerjakan tugas implementasi sesuai materi perkuliahan.',
                        'due_at' => now()->copy()->addDays(7),
                        'status' => 'published',
                    ],
                    [
                        'title' => 'Tugas 3 - Project',
                        'instructions' => 'Kerjakan project sesuai ketentuan yang diberikan dosen.',
                        'due_at' => now()->copy()->addDays(14),
                        'status' => 'draft',
                    ],
                ];

                foreach ($assignmentDefinitions as $definition) {
                    $assignment = Assignment::updateOrCreate(
                        [
                            'course_id' => $course->id,
                            'title' => $definition['title'],
                        ],
                        [
                            'created_by' => $course->lecturer_id,
                            'instructions' => $definition['instructions'],
                            'due_at' => $definition['due_at'],
                            'max_score' => 100,
                            'allow_late' => true,
                            'status' => $definition['status'],
                        ]
                    );

                    $allAssignments->push($assignment);

                    // Submission hanya dibuat untuk tugas yang dipublikasikan.
                    if ($assignment->status !== 'published') {
                        continue;
                    }

                    foreach ($courseStudents as $student) {
                        // Maksimal sekitar 20% submission terlambat,
                        // dan hanya pada tugas yang deadline-nya sudah lewat.
                        $isLate = $assignment->due_at->isPast()
                            && (($student->id + $assignment->id) % 5 === 0);

                        if ($isLate) {
                            $submittedAt = $assignment->due_at->copy()->addDay();
                        } elseif ($assignment->due_at->isPast()) {
                            $submittedAt = $assignment->due_at->copy()->subDay();
                        } else {
                            $submittedAt = now()->copy()->subDays(
                                ($student->id % 6) + 1
                            );
                        }

                        Submission::updateOrCreate(
                            [
                                'assignment_id' => $assignment->id,
                                'user_id' => $student->id,
                            ],
                            [
                                'file_path' => "submissions/demo/assignment-{$assignment->id}/student-{$student->id}.pdf",
                                'original_name' => "jawaban-{$student->id}.pdf",
                                'file_size' => 250000 + (($student->id * 1000) % 4500000),
                                'mime_type' => 'application/pdf',
                                'note' => null,
                                'submitted_at' => $submittedAt,
                                'is_late' => $isLate,
                            ]
                        );
                    }
                }
            }

            // Ambil kembali submission demo berdasarkan assignment demo.
            $seededAssignmentIds = $allAssignments->pluck('id');
            $allSubmissions = Submission::query()
                ->whereIn('assignment_id', $seededAssignmentIds)
                ->whereHas('assignment.course', function ($query): void {
                    $query->whereIn('code', [
                        'MK00001',
                        'MK00002',
                        'MK00003',
                        'MK00004',
                        'MK00005',
                    ]);
                })
                ->orderBy('id')
                ->get();

            /* =========================================================
             * 5. GRADES
             * ======================================================= */

            // 60% dari submission demo mendapatkan nilai.
            $gradedCount = (int) floor(
                $allSubmissions->count() * 0.60
            );

            $submissionsToGrade = $allSubmissions->take($gradedCount);
            $gradedSubmissionIds = $submissionsToGrade->pluck('id');

            // Hapus nilai demo yang tidak lagi termasuk dalam 60% terpilih.
            $gradeCleanupQuery = Grade::query()
                ->whereIn('submission_id', $allSubmissions->pluck('id'));

            if ($gradedSubmissionIds->isNotEmpty()) {
                $gradeCleanupQuery->whereNotIn(
                    'submission_id',
                    $gradedSubmissionIds
                );
            }

            $gradeCleanupQuery->delete();

            $assignmentById = $allAssignments->keyBy('id');

            foreach ($submissionsToGrade as $submission) {
                $assignment = $assignmentById->get($submission->assignment_id);

                if (! $assignment) {
                    continue;
                }

                Grade::updateOrCreate(
                    ['submission_id' => $submission->id],
                    [
                        'graded_by' => $assignment->created_by,
                        'score' => 60 + (($submission->id * 7) % 41),
                        'feedback' => 'Jawaban sudah diperiksa. Terus tingkatkan pemahaman materi.',
                        'graded_at' => now(),
                    ]
                );
            }
        });

        /* =============================================================
         * 6. OUTPUT
         * =========================================================== */

        $this->command?->info('');
        $this->command?->info('==========================================');
        $this->command?->info('KampusLMS seeding selesai!');
        $this->command?->info('==========================================');
        $this->command?->info('Admin      : ' . $admin->email);
        $this->command?->info('Dosen      : ' . $lecturers->count());
        $this->command?->info('Mahasiswa  : ' . $students->count());
        $this->command?->info('Course     : ' . $courses->count());
        $this->command?->info('Assignment : ' . $allAssignments->count());
        $this->command?->info('Submission : ' . $allSubmissions->count());
        $this->command?->info('Graded     : ' . $gradedCount);
        $this->command?->info('==========================================');
        $this->command?->info('');
    }
}
