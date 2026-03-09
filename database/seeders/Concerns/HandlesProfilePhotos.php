<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait HandlesProfilePhotos
{
    protected function generateProfilePhoto(string $folder, string $label): string
    {
        $directory = public_path('uploads/' . trim($folder, '/'));

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = Str::slug($label) . '-' . Str::random(6) . '.png';
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;

        File::put($absolutePath, base64_decode(self::$placeholderPixel));

        return 'uploads/' . trim($folder, '/') . '/' . $filename;
    }

    protected static string $placeholderPixel = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGMAAQAABQABDQottAAAAABJRU5ErkJggg==';
}
