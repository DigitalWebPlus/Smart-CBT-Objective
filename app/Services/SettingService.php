<?php

namespace App\Services;

use App\Models\Setting;
use Throwable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingService
{
    private const CACHE_KEY = 'app.settings';

    public function getSettings(): array
    {
        try {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return Cache::rememberForever(self::CACHE_KEY, function (): array {
                return Setting::query()->pluck('value', 'key')->toArray();
            });
        } catch (Throwable $throwable) {
            return [];
        }
    }

    public function setSettings(): void
    {
        config()->set('settings', $this->getSettings());
    }

    public function saveSettings(array $values): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::transaction(function () use ($values): void {
            foreach ($values as $key => $value) {
                Setting::query()->updateOrCreate(
                    ['key' => (string) $key],
                    ['value' => $value !== null ? (string) $value : null]
                );
            }
        });

        $this->clearCachedSettings();
        $this->setSettings();
    }

    public function clearCachedSettings(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
