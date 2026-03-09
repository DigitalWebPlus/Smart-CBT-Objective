<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'objective_question_id',
        'marks',
        'display_order',
    ];

    protected $casts = [
        'marks' => 'decimal:2',
        'display_order' => 'integer',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ObjectiveQuestion::class, 'objective_question_id');
    }
}
