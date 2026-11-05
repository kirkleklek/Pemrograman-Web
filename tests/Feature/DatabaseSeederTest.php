<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_private_files_for_all_submissions(): void
    {
        Storage::fake('local');

        $this->seed();

        $this->assertSame(
            5,
            Assignment::where('status', 'draft')->count()
        );

        $this->assertSame(
            10,
            Assignment::where('status', 'published')->count()
        );

        $this->assertSame(
            150,
            Submission::count()
        );

        $draftSubmissions = Submission::query()
            ->whereHas('assignment', function ($query) {
                $query->where('status', 'draft');
            })
            ->count();

        $this->assertSame(0, $draftSubmissions);

        $this->assertSame(
            90,
            Grade::count()
        );

        foreach (Submission::all() as $submission) {
            Storage::disk('local')->assertExists(
                $submission->file_path
            );

            $content = Storage::disk('local')->get(
                $submission->file_path
            );

            $this->assertStringStartsWith(
                '%PDF-1.4',
                $content
            );

            $this->assertSame(
                strlen($content),
                (int) $submission->file_size
            );
        }
    }
}
