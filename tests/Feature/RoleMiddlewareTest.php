<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_user_management(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }

    public function test_dosen_cannot_access_admin_user_management(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $this->actingAs($dosen)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_mahasiswa_cannot_access_admin_user_management(): void
    {
        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $this->actingAs($mahasiswa)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }
}
