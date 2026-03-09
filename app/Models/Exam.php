<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Exam extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    public const TYPE_OBJECTIVE = 'objective';

    public const TYPES = [
        self::TYPE_OBJECTIVE,
    ];

    protected $fillable = [
        'admin_id',
        'exam_type',
        'title',
        'description',
        'starts_at',
        'ends_at',
        'duration_minutes',
        'status',
        'total_marks',
        'settings',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'duration_minutes' => 'integer',
        'total_marks' => 'decimal:2',
        'settings' => 'array',
    ];

    protected $attributes = [
        'exam_type' => self::TYPE_OBJECTIVE,
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ExamQuestion::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'exam_subject_assignments')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'exam_department_assignments')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function scopeForDepartments(Builder $query, array $departmentIds): Builder
    {
        $ids = array_values(array_unique(array_map(
            static fn ($id) => (int) $id,
            array_filter($departmentIds, static fn ($id) => $id !== null && $id !== '')
        )));

        if (empty($ids)) {
            return $query;
        }

        return $query->whereHas('departments', fn ($builder) => $builder->whereIn('departments.id', $ids));
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function allowsReview(): bool
    {
        return (bool) data_get($this->settings, 'allow_review', false);
    }

    public function allowsResultView(): bool
    {
        return (bool) data_get($this->settings, 'allow_result_view', false);
    }

    public function refreshTotalMarks(): self
    {
        $total = (float) $this->questions()->sum('marks');

        $this->forceFill([
            'total_marks' => $total,
        ])->save();

        return $this->refresh();
    }
}
