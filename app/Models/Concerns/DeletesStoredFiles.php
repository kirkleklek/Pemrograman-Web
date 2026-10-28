<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait DeletesStoredFiles
{
    protected array $pendingStoredFilePaths = [];

    protected static function bootDeletesStoredFiles(): void
    {
        static::deleting(function (Model $model): void {
            $paths = $model->storedFilePathsForDeletion();

            $model->pendingStoredFilePaths = array_values(
                array_unique(
                    array_filter(
                        $paths,
                        static fn ($path): bool =>
                            is_string($path) && $path !== ''
                    )
                )
            );
        });

        static::deleted(function (Model $model): void {
            $paths = $model->pendingStoredFilePaths;

            if ($paths === []) {
                return;
            }

            // Hapus setelah transaksi database berhasil di-commit.
            DB::afterCommit(function () use ($paths): void {
                Storage::disk('local')->delete($paths);
            });
        });
    }

    /**
     * Secara default, hapus file milik record itu sendiri.
     * Model induk dapat mengubah metode ini untuk mengumpulkan
     * file milik record anak yang dihapus melalui cascade.
     */
    protected function storedFilePathsForDeletion(): array
    {
        $path = $this->getAttribute('file_path');

        return is_string($path) && $path !== ''
            ? [$path]
            : [];
    }
}