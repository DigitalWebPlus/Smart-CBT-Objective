<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectiveExamQuestion extends Model
{
    use HasFactory;

    protected $table = 'exam_objective_question_assignments';

    protected $fillable = [
        'id',
        'exam_id',
        'objective_question_id',
        'marks',
        'display_order',
        'settings',
    ];

    protected $casts = [
        'marks' => 'decimal:2',
        'display_order' => 'integer',
        'settings' => 'array',
    ];

    public $incrementing = false;

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ObjectiveQuestion::class, 'objective_question_id');
    }
}
