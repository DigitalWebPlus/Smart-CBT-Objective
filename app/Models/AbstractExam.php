<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Subject;
use App\Models\Admin;

abstract class AbstractExam extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'id',
        'subject_id',
        'admin_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'duration_minutes',
        'status',
        'total_marks',
        'settings',
        'published_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'published_at' => 'datetime',
        'deleted_at' => 'datetime',
        'settings' => 'array',
        'total_marks' => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    protected $keyType = 'int';

    public $incrementing = false;

    abstract protected function subjectsPivotTable(): string;

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, $this->subjectsPivotTable(), 'exam_id', 'subject_id')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
