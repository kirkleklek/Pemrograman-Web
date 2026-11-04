<?php

namespace App\Models;

use App\Events\GradeGiven;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'graded_by',
        'score',
        'feedback',
        'graded_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'graded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(
            fn (Grade $grade) => GradeGiven::dispatch($grade->id)
        );

        static::updated(function (Grade $grade): void {
            if ($grade->wasChanged(['score', 'feedback'])) {
                GradeGiven::dispatch($grade->id);
            }
        });
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
