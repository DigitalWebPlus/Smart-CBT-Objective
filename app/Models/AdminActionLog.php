<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActionLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'admin_id',
        'user_id',
        'action',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
