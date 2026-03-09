<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamAttempt extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_GRADED = 'graded';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_RETAKE = 'retake';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_IN_PROGRESS,
        self::STATUS_SUBMITTED,
        self::STATUS_GRADED,
        self::STATUS_PUBLISHED,
        self::STATUS_CANCELED,
        self::STATUS_RETAKE,
    ];

    protected $fillable = [
        'exam_id',
        'user_id',
        'status',
        'started_at',
        'submitted_at',
        'login_count',
        'login_ips',
        'auto_score',
        'manual_score',
        'total_score',
        'percentage',
        'subject_scores',
        'grade_letter',
        'grade_remark',
        'feedback',
        'graded_by',
        'graded_at',
        'score',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'decimal:2',
        'auto_score' => 'decimal:2',
        'manual_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'subject_scores' => 'array',
        'login_count' => 'integer',
        'login_ips' => 'array',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ObjectiveResponse::class);
    }

    public function objectiveResponses(): HasMany
    {
        return $this->hasMany(ObjectiveResponse::class);
    }

}
