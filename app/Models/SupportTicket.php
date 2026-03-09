<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_PENDING,
        self::STATUS_CLOSED,
    ];

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_MEDIUM,
        self::PRIORITY_HIGH,
    ];

    protected $fillable = [
        'user_id',
        'admin_id',
        'subject',
        'status',
        'priority',
        'last_message_at',
        'last_reply_by',
        'closed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at');
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }
}
