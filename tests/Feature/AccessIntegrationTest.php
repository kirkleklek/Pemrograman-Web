<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\IntegrationFixture;
use Tests\TestCase;

class AccessIntegrationTest extends TestCase
{
    use IntegrationFixture, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createIntegrationFixture();
    }

    public static function protectedApiRoutes(): array
    {
        return [
            'logout' => ['POST', '/auth/logout'], 'me' => ['GET', '/me'],
            'courses' => ['GET', '/courses'], 'course detail' => ['GET', '/courses/{course}'],
            'materials' => ['GET', '/courses/{course}/materials'],
            'assignments list' => ['GET', '/courses/{course}/assignments'],
            'assignment create' => ['POST', '/assignments'],
            'assignment put' => ['PUT', '/assignments/{assignment}'],
            'assignment patch' => ['PATCH', '/assignments/{assignment}'],
            'assignment delete' => ['DELETE', '/assignments/{assignment}'],
            'submission list' => ['GET', '/assignments/{assignment}/submissions'],
            'submission create' => ['POST', '/assignments/{assignment}/submissions'],
            'grade' => ['PUT', '/submissions/{submission}/grade'],
            'notifications' => ['GET', '/notifications'],
            'notification read' => ['POST', '/notifications/{notification}/read'],
        ];
    }

    #[DataProvider('protectedApiRoutes')]
    public function test_guest_api_requests_are_rejected_without_data_or_writes(string $method, string $path): void
    {
        $notification = $this->fixtureNotification($this->students[0]);
        $path = strtr($path, [
            '{course}' => $this->courses[0]->id, '{assignment}' => $this->assignments[0]->id,
            '{submission}' => $this->submissions[0]->id, '{notification}' => $notification,
        ]);
        $before = $this->databaseSnapshot();
        $this->json($method, '/api/v1'.$path, $this->assignmentPayload($this->courses[0]))
            ->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
        $this->assertSame($before, $this->databaseSnapshot());
    }

    public static function protectedWebRoutes(): array
    {
        return [
            'dashboard' => ['/dashboard'], 'admin users' => ['/admin/users'],
            'admin courses' => ['/admin/courses'],
            'lecturer material' => ['/dosen/materials/{material}'],
            'lecturer assignment' => ['/dosen/assignments/{assignment}'],
            'student material' => ['/mahasiswa/materials/{material}'],
            'student assignment' => ['/mahasiswa/assignments/{assignment}'],
            'student submission' => ['/mahasiswa/submissions/{submission}'],
        ];
    }

    #[DataProvider('protectedWebRoutes')]
    public function test_guest_web_requests_redirect_to_login_without_data(string $path): void
    {
        $this->assertModelExists($this->materials[0]);
        $this->assertModelExists($this->assignments[0]);
        $this->assertModelExists($this->submissions[0]);
        $path = strtr($path, [
            '{material}' => $this->materials[0]->id,
            '{assignment}' => $this->assignments[0]->id,
            '{submission}' => $this->submissions[0]->id,
        ]);
        $before = $this->databaseSnapshot();
        $this->get($path)->assertRedirect(route('login'))->assertDontSee($this->materials[0]->title);
        $this->assertSame($before, $this->databaseSnapshot());
    }

    public static function missingResources(): array
    {
        return [
            'course' => ['GET', '/api/v1/courses/999999999', 'dosen'],
            'materials list' => ['GET', '/api/v1/courses/999999999/materials', 'dosen'],
            'assignments list' => ['GET', '/api/v1/courses/999999999/assignments', 'dosen'],
            'assignment put' => ['PUT', '/api/v1/assignments/999999999', 'dosen'],
            'assignment patch' => ['PATCH', '/api/v1/assignments/999999999', 'dosen'],
            'assignment delete' => ['DELETE', '/api/v1/assignments/999999999', 'dosen'],
            'submission list' => ['GET', '/api/v1/assignments/999999999/submissions', 'dosen'],
            'submission create' => ['POST', '/api/v1/assignments/999999999/submissions', 'mahasiswa'],
            'grade' => ['PUT', '/api/v1/submissions/999999999/grade', 'dosen'],
            'notification' => ['POST', '/api/v1/notifications/00000000-0000-0000-0000-000000000000/read', 'mahasiswa'],
            'web material' => ['GET', '/dosen/materials/999999999', 'dosen'],
            'web assignment' => ['PUT', '/dosen/assignments/999999999', 'dosen'],
            'web student submission' => ['GET', '/mahasiswa/submissions/999999999', 'mahasiswa'],
            'web lecturer submission' => ['GET', '/dosen/submissions/999999999', 'dosen'],
        ];
    }

    #[DataProvider('missingResources')]
    public function test_missing_bound_resource_returns_404_without_writes(string $method, string $path, string $role): void
    {
        $actor = $role === 'dosen' ? $this->lecturers[0] : $this->students[0];
        $this->actingAs($actor);
        Sanctum::actingAs($actor);
        $before = $this->databaseSnapshot();
        $this->json($method, $path, ['score' => 80, 'title' => 'Tidak tersimpan'])
            ->assertNotFound()->assertDontSee($this->submissions[0]->note);
        $this->assertSame($before, $this->databaseSnapshot());
    }

    public function test_admin_read_permissions_do_not_grant_assignment_or_grading_writes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        Sanctum::actingAs($admin);
        $before = $this->databaseSnapshot();
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/courses')->assertOk();
        $this->getJson('/api/v1/courses')->assertOk()->assertJsonPath('meta.total', 3);
        $this->getJson('/api/v1/courses/'.$this->courses[0]->id)->assertOk();
        $this->getJson('/api/v1/courses/'.$this->courses[0]->id.'/materials')->assertOk();
        $this->postJson('/api/v1/assignments', $this->assignmentPayload($this->courses[0]))->assertForbidden();
        $this->patchJson('/api/v1/assignments/'.$this->assignments[0]->id, ['title' => 'Terlarang'])->assertForbidden();
        $this->deleteJson('/api/v1/assignments/'.$this->assignments[0]->id)->assertForbidden();
        $this->putJson('/api/v1/submissions/'.$this->submissions[0]->id.'/grade', ['score' => 80])->assertForbidden();
        $this->get('/dosen/materials/'.$this->materials[0]->id)->assertForbidden();
        $this->assertSame($before, $this->databaseSnapshot());
    }
}
