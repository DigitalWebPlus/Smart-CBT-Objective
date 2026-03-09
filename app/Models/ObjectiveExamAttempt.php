<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/** @mixin \Illuminate\Database\Eloquent\Model */

class ObjectiveExamAttempt extends Model
{
    use HasFactory;

    protected $table = 'exam_objective_attempts';

    protected $fillable = [
        'id',
        'exam_id',
        'candidate_id',
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
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'login_count' => 'integer',
        'auto_score' => 'decimal:2',
        'manual_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'subject_scores' => 'array',
        'login_ips' => 'array',
    ];

    public $incrementing = false;

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }
}
