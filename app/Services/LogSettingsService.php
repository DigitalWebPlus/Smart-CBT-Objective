<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class LogSettingsService
{
    private const CACHE_KEY = 'admin.logs.enabled';

    public function isEnabled(): bool
    {
        return (bool) Cache::get(self::CACHE_KEY, true);
    }

    public function setEnabled(bool $enabled): void
    {
        Cache::forever(self::CACHE_KEY, $enabled);
    }

    public function toggle(): bool
    {
        $enabled = ! $this->isEnabled();
        $this->setEnabled($enabled);

        return $enabled;
    }
}
