<?php

namespace Tests\Feature;

use App\Livewire\Dosen\FeedbackTemplateManager;
use App\Models\FeedbackTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FeedbackTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_dosen_dapat_membuat_template_umpan_balik(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        Livewire::actingAs($dosen)
            ->test(FeedbackTemplateManager::class)
            ->set('title', 'Jawaban Sudah Baik')
            ->set('content', 'Jawaban sudah sesuai dengan materi.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Jawaban Sudah Baik')
            ->assertSee('Jawaban sudah sesuai dengan materi.');

        $this->assertDatabaseHas('feedback_templates', [
            'lecturer_id' => $dosen->id,
            'title' => 'Jawaban Sudah Baik',
            'content' => 'Jawaban sudah sesuai dengan materi.',
        ]);
    }

    public function test_dosen_dapat_mengedit_template_miliknya(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $template = FeedbackTemplate::create([
            'lecturer_id' => $dosen->id,
            'title' => 'Template Lama',
            'content' => 'Isi lama.',
        ]);

        Livewire::actingAs($dosen)
            ->test(FeedbackTemplateManager::class)
            ->call('edit', $template->id)
            ->assertSet('title', 'Template Lama')
            ->assertSet('content', 'Isi lama.')
            ->set('title', 'Template Baru')
            ->set('content', 'Isi yang sudah diperbarui.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('feedback_templates', [
            'id' => $template->id,
            'lecturer_id' => $dosen->id,
            'title' => 'Template Baru',
            'content' => 'Isi yang sudah diperbarui.',
        ]);

        $this->assertDatabaseCount('feedback_templates', 1);
    }

    public function test_dosen_dapat_menghapus_template_miliknya(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $template = FeedbackTemplate::create([
            'lecturer_id' => $dosen->id,
            'title' => 'Template Hapus',
            'content' => 'Template untuk pengujian hapus.',
        ]);

        Livewire::actingAs($dosen)
            ->test(FeedbackTemplateManager::class)
            ->call('delete', $template->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('feedback_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_dosen_tidak_dapat_mengedit_template_dosen_lain(): void
    {
        $pemilik = User::factory()->create([
            'role' => 'dosen',
        ]);

        $dosenLain = User::factory()->create([
            'role' => 'dosen',
        ]);

        $template = FeedbackTemplate::create([
            'lecturer_id' => $pemilik->id,
            'title' => 'Template Rahasia',
            'content' => 'Isi milik dosen pemilik.',
        ]);

        try {
            Livewire::actingAs($dosenLain)
                ->test(FeedbackTemplateManager::class)
                ->call('edit', $template->id);

            $this->fail(
                'Dosen lain tidak boleh mengedit template ini.'
            );
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(
                FeedbackTemplate::class,
                $exception->getModel()
            );
        }

        $this->assertDatabaseHas('feedback_templates', [
            'id' => $template->id,
            'lecturer_id' => $pemilik->id,
            'title' => 'Template Rahasia',
            'content' => 'Isi milik dosen pemilik.',
        ]);
    }

    public function test_dosen_tidak_dapat_menghapus_template_dosen_lain(): void
    {
        $pemilik = User::factory()->create([
            'role' => 'dosen',
        ]);

        $dosenLain = User::factory()->create([
            'role' => 'dosen',
        ]);

        $template = FeedbackTemplate::create([
            'lecturer_id' => $pemilik->id,
            'title' => 'Template Rahasia',
            'content' => 'Isi milik dosen pemilik.',
        ]);

        try {
            Livewire::actingAs($dosenLain)
                ->test(FeedbackTemplateManager::class)
                ->call('delete', $template->id);

            $this->fail(
                'Dosen lain tidak boleh menghapus template ini.'
            );
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(
                FeedbackTemplate::class,
                $exception->getModel()
            );
        }

        $this->assertDatabaseHas('feedback_templates', [
            'id' => $template->id,
            'lecturer_id' => $pemilik->id,
            'title' => 'Template Rahasia',
            'content' => 'Isi milik dosen pemilik.',
        ]);
    }

    public function test_template_hanya_menampilkan_data_milik_dosen_login(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        $dosenLain = User::factory()->create([
            'role' => 'dosen',
        ]);

        FeedbackTemplate::create([
            'lecturer_id' => $dosen->id,
            'title' => 'Template Saya',
            'content' => 'Isi milik saya.',
        ]);

        FeedbackTemplate::create([
            'lecturer_id' => $dosenLain->id,
            'title' => 'Template Dosen Lain',
            'content' => 'Isi yang tidak boleh terlihat.',
        ]);

        Livewire::actingAs($dosen)
            ->test(FeedbackTemplateManager::class)
            ->assertSee('Template Saya')
            ->assertDontSee('Template Dosen Lain')
            ->assertDontSee('Isi yang tidak boleh terlihat.');
    }

    public function test_judul_dan_isi_template_wajib_diisi(): void
    {
        $dosen = User::factory()->create([
            'role' => 'dosen',
        ]);

        Livewire::actingAs($dosen)
            ->test(FeedbackTemplateManager::class)
            ->set('title', '')
            ->set('content', '')
            ->call('save')
            ->assertHasErrors([
                'title' => 'required',
                'content' => 'required',
            ]);

        $this->assertDatabaseCount('feedback_templates', 0);
    }

    public function test_pengguna_non_dosen_ditolak_mengakses_halaman_template(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $mahasiswa = User::factory()->create([
            'role' => 'mahasiswa',
        ]);

        $this->actingAs($admin)
            ->get(route('dosen.feedback-templates.index'))
            ->assertForbidden();

        $this->actingAs($mahasiswa)
            ->get(route('dosen.feedback-templates.index'))
            ->assertForbidden();
    }
}
