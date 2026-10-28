<?php

namespace App\Models;

use App\Events\AssignmentPublished;
use App\Models\Concerns\DeletesStoredFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Assignment extends Model
{
    use DeletesStoredFiles;
    use HasFactory;

    protected $fillable = [
        'course_id',
        'created_by',
        'title',
        'instructions',
        'due_at',
        'max_score',
        'allow_late',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'allow_late' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Assignment $assignment): void {
            if ($assignment->status === 'published') {
                AssignmentPublished::dispatch($assignment->id);
            }
        });

        static::updated(function (Assignment $assignment): void {
            if ($assignment->status === 'published' && $assignment->wasChanged('status')) {
                AssignmentPublished::dispatch($assignment->id);
            }
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function grades(): HasManyThrough
    {
        return $this->hasManyThrough(
            Grade::class,
            Submission::class
        );
    }

    /**
     * File submission ikut dibersihkan ketika tugas dihapus,
     * termasuk ketika database melakukan cascade.
     */
    protected function storedFilePathsForDeletion(): array
    {
        return $this->submissions()
            ->whereNotNull('file_path')
            ->pluck('file_path')
            ->all();
    }
}
