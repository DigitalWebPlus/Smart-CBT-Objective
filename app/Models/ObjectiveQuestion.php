<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Subject;

class ObjectiveQuestion extends Model
{
    use HasFactory;

    protected $table = 'bank_questions';

    public const TYPE_MSA = 'msa';
    public const TYPE_MMA = 'mma';
    public const TYPE_TOF = 'tof';

    protected $fillable = [
        'subject_id',
        'question_text',
        'question_type',
        'image_path',
        'marks',
        'is_active',
        'explanation',
        'metadata',
    ];

    protected $casts = [
        'marks' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ObjectiveOption::class, 'objective_question_id')->orderBy('display_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
