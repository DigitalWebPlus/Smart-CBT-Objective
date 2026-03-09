<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_BANNED = 'banned';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
        self::STATUS_SUSPENDED,
        self::STATUS_BANNED,
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'registration_number',
        'phone',
        'address',
        'status',
        'password',
        'photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)->withTimestamps();
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function actionLogs(): HasMany
    {
        return $this->hasMany(AdminActionLog::class, 'user_id');
    }
}
