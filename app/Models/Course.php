<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;
    use DeletesStoredFiles;

    protected $fillable = [
        'code',
        'name',
        'description',
        'sks',
        'lecturer_id',
        'status',
    ];

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lecturer_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('enrolled_at')
            ->withTimestamps();
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Kumpulkan semua file materi dan submission sebelum
     * mata kuliah beserta record turunannya dihapus.
     */
    protected function storedFilePathsForDeletion(): array
    {
        $materialPaths = $this->materials()
            ->whereNotNull('file_path')
            ->pluck('file_path')
            ->all();

        $assignmentIds = $this->assignments()->pluck('id');

        $submissionPaths = [];

        if ($assignmentIds->isNotEmpty()) {
            $submissionPaths = Submission::query()
                ->whereIn('assignment_id', $assignmentIds)
                ->whereNotNull('file_path')
                ->pluck('file_path')
                ->all();
        }

        return array_merge(
            $materialPaths,
            $submissionPaths
        );
    }
}