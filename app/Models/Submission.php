<?php

namespace App\Models;

use App\Models\Concerns\DeletesStoredFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    use HasFactory;
    use DeletesStoredFiles;

    protected $fillable = [
        'assignment_id',
        'user_id',
        'file_path',
        'original_name',
        'file_size',
        'mime_type',
        'note',
        'submitted_at',
        'is_late',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'is_late' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function grade(): HasOne
    {
        return $this->hasOne(Grade::class);
    }
}
