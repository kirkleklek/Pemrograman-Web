<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_user_management(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->count(3)->create(['role' => 'mahasiswa']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertViewIs('users.index');
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(
            route('admin.users.store'),
            [
                'name' => 'Mahasiswa Baru',
                'email' => 'mahasiswa.baru@example.com',
                'nim_nip' => '20260001',
                'role' => 'mahasiswa',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]
        );

        $user = User::where('email', 'mahasiswa.baru@example.com')
            ->firstOrFail();

        $response->assertRedirect(route('admin.users.show', $user));

        $this->assertDatabaseHas('users', [
            'email' => 'mahasiswa.baru@example.com',
            'name' => 'Mahasiswa Baru',
            'nim_nip' => '20260001',
            'role' => 'mahasiswa',
        ]);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'mahasiswa']);

        $response = $this->actingAs($admin)->put(
            route('admin.users.update', $user),
            [
                'name' => 'Nama Diperbarui',
                'email' => 'nama.diperbarui@example.com',
                'nim_nip' => '20260002',
                'role' => 'dosen',
            ]
        );

        $response->assertRedirect(route('admin.users.show', $user));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Diperbarui',
            'email' => 'nama.diperbarui@example.com',
            'nim_nip' => '20260002',
            'role' => 'dosen',
        ]);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'mahasiswa']);

        $response = $this->actingAs($admin)->delete(
            route('admin.users.destroy', $user)
        );

        $response->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_user_creation_rejects_invalid_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(
            route('admin.users.store'),
            [
                'name' => 'Pengguna Tidak Valid',
                'email' => 'invalid.role@example.com',
                'nim_nip' => '20260003',
                'role' => 'superadmin',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]
        );

        $response->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', [
            'email' => 'invalid.role@example.com',
        ]);
    }

    public function test_user_update_rejects_invalid_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create(['role' => 'mahasiswa']);

        $response = $this->actingAs($admin)->put(
            route('admin.users.update', $target),
            [
                'name' => $target->name,
                'email' => $target->email,
                'nim_nip' => $target->nim_nip,
                'role' => 'superadmin',
            ]
        );

        $response->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'role' => 'mahasiswa',
        ]);
    }

    public function test_admin_can_search_users_by_name_or_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'role' => 'mahasiswa',
        ]);

        User::factory()->create([
            'name' => 'Siti Aminah',
            'email' => 'siti@example.com',
            'role' => 'dosen',
        ]);

        $responseByName = $this->actingAs($admin)->get(
            route('admin.users.index', ['q' => 'Budi'])
        );

        $responseByName->assertOk();
        $responseByName->assertSee('Budi Santoso');
        $responseByName->assertDontSee('Siti Aminah');

        $responseByEmail = $this->actingAs($admin)->get(
            route('admin.users.index', ['q' => 'siti@example.com'])
        );

        $responseByEmail->assertOk();
        $responseByEmail->assertSee('Siti Aminah');
        $responseByEmail->assertDontSee('Budi Santoso');
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create([
            'name' => 'Mahasiswa Filter',
            'email' => 'filter.mahasiswa@example.com',
            'role' => 'mahasiswa',
        ]);

        User::factory()->create([
            'name' => 'Dosen Filter',
            'email' => 'filter.dosen@example.com',
            'role' => 'dosen',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.users.index', ['role' => 'dosen'])
        );

        $response->assertOk();
        $response->assertSee('Dosen Filter');
        $response->assertDontSee('Mahasiswa Filter');
    }

    public function test_duplicate_email_is_rejected_when_creating_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create([
            'email' => 'existing.email@example.com',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.users.store'),
            [
                'name' => 'Pengguna Baru',
                'email' => 'existing.email@example.com',
                'nim_nip' => 'NIM-NEW-001',
                'role' => 'mahasiswa',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]
        );

        $response->assertSessionHasErrors('email');
    }

    public function test_duplicate_nim_nip_is_rejected_when_creating_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create(['nim_nip' => 'NIM-DUPLICATE-001']);

        $response = $this->actingAs($admin)->post(
            route('admin.users.store'),
            [
                'name' => 'Pengguna Baru',
                'email' => 'nim.duplicate.test@example.com',
                'nim_nip' => 'NIM-DUPLICATE-001',
                'role' => 'mahasiswa',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]
        );

        $response->assertSessionHasErrors('nim_nip');
    }

    public function test_duplicate_email_is_rejected_when_updating_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create(['email' => 'other.user@example.com']);

        $target = User::factory()->create([
            'email' => 'target.user@example.com',
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.users.update', $target),
            [
                'name' => $target->name,
                'email' => 'other.user@example.com',
                'nim_nip' => $target->nim_nip,
                'role' => $target->role,
            ]
        );

        $response->assertSessionHasErrors('email');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'email' => 'target.user@example.com',
        ]);
    }

    public function test_duplicate_nim_nip_is_rejected_when_updating_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        User::factory()->create(['nim_nip' => 'NIM-OTHER-001']);

        $target = User::factory()->create([
            'nim_nip' => 'NIM-TARGET-001',
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.users.update', $target),
            [
                'name' => $target->name,
                'email' => $target->email,
                'nim_nip' => 'NIM-OTHER-001',
                'role' => $target->role,
            ]
        );

        $response->assertSessionHasErrors('nim_nip');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'nim_nip' => 'NIM-TARGET-001',
        ]);
    }

    public function test_user_can_keep_own_email_and_nim_nip_when_updating(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $target = User::factory()->create([
            'email' => 'target.unique@example.com',
            'nim_nip' => 'NIM-SELF-001',
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.users.update', $target),
            [
                'name' => $target->name,
                'email' => 'target.unique@example.com',
                'nim_nip' => 'NIM-SELF-001',
                'role' => $target->role,
            ]
        );

        $response->assertRedirect(route('admin.users.show', $target));

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'email' => 'target.unique@example.com',
            'nim_nip' => 'NIM-SELF-001',
        ]);
    }

    public function test_non_admin_cannot_change_user_role_through_admin_endpoint(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $target = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($mahasiswa)->put(
            route('admin.users.update', $target),
            [
                'name' => $target->name,
                'email' => $target->email,
                'nim_nip' => $target->nim_nip,
                'role' => 'admin',
            ]
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'role' => 'dosen',
        ]);
    }

    public function test_manipulated_sensitive_fields_are_ignored_when_updating_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $target = User::factory()->create([
            'name' => 'Pengguna Target',
            'email' => 'target.sensitive@example.com',
            'role' => 'mahasiswa',
        ]);

        $other = User::factory()->create([
            'name' => 'Pengguna Lain',
            'email' => 'other.sensitive@example.com',
            'role' => 'dosen',
        ]);

        $originalId = $target->id;
        $originalRememberToken = $target->remember_token;
        $originalVerifiedAt = $target->email_verified_at;

        $response = $this->actingAs($admin)->put(
            route('admin.users.update', $target),
            [
                'name' => 'Nama Target Diperbarui',
                'email' => 'target.sensitive@example.com',
                'nim_nip' => $target->nim_nip,
                'role' => 'mahasiswa',
                'id' => $other->id,
                'remember_token' => 'attacker-controlled-token',
                'email_verified_at' => '2020-01-01 00:00:00',
            ]
        );

        $response->assertRedirect(route('admin.users.show', $target));

        $this->assertDatabaseHas('users', [
            'id' => $originalId,
            'name' => 'Nama Target Diperbarui',
            'email' => 'target.sensitive@example.com',
            'role' => 'mahasiswa',
            'remember_token' => $originalRememberToken,
            'email_verified_at' => $originalVerifiedAt,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $other->id,
            'name' => 'Pengguna Lain',
            'email' => 'other.sensitive@example.com',
            'role' => 'dosen',
        ]);
    }

    public function test_manipulated_sensitive_fields_are_ignored_when_creating_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $other = User::factory()->create([
            'email' => 'other.create@example.com',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.users.store'),
            [
                'name' => 'Pengguna Baru',
                'email' => 'new.sensitive@example.com',
                'nim_nip' => 'NIM-NEW-SENSITIVE',
                'role' => 'mahasiswa',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'id' => $other->id,
                'remember_token' => 'attacker-controlled-token',
                'email_verified_at' => '2020-01-01 00:00:00',
            ]
        );

        $response->assertRedirect();

        $created = User::where(
            'email',
            'new.sensitive@example.com'
        )->firstOrFail();

        $this->assertNotSame($other->id, $created->id);
        $this->assertNull($created->remember_token);
        $this->assertNull($created->email_verified_at);
    }

    public function test_non_admin_cannot_delete_user(): void
    {
        $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
        $target = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($mahasiswa)->delete(
            route('admin.users.destroy', $target)
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_cannot_delete_lecturer_who_still_teaches_course(): void
{
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $lecturer = User::factory()->create([
        'role' => 'dosen',
    ]);

    $course = Course::factory()->create([
        'lecturer_id' => $lecturer->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $lecturer))
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('users', [
        'id' => $lecturer->id,
        'deleted_at' => null,
    ]);

    $this->assertDatabaseHas('courses', [
        'id' => $course->id,
        'lecturer_id' => $lecturer->id,
    ]);
}

    public function test_admin_cannot_change_role_of_lecturer_who_still_teaches_course(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $lecturer = User::factory()->create([
            'role' => 'dosen',
        ]);

        $course = Course::factory()->create([
            'lecturer_id' => $lecturer->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $lecturer), [
                'name' => $lecturer->name,
                'email' => $lecturer->email,
                'nim_nip' => $lecturer->nim_nip,
                'role' => 'mahasiswa',
            ])
            ->assertRedirect(route('admin.users.edit', $lecturer))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseHas('users', [
            'id' => $lecturer->id,
            'role' => 'dosen',
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'lecturer_id' => $lecturer->id,
        ]);
    }
}