<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObjectiveOption extends Model
{
    use HasFactory;

    protected $table = 'bank_options';

    protected $fillable = [
        'objective_question_id',
        'label',
        'description',
        'is_correct',
        'display_order',
        'image_path',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'display_order' => 'integer',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(ObjectiveQuestion::class, 'objective_question_id');
    }
}
