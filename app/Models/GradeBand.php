<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class GradeBand extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter',
        'min_percentage',
        'remark',
    ];

    protected $casts = [
        'min_percentage' => 'integer',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByDesc('min_percentage');
    }
}
