<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Grade;
use App\Models\Submission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_does_not_create_submissions_for_draft_assignments(): void
    {
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
    }
}