<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileImageService
{
    public static function upload(
        ?UploadedFile $file,
        string $folder,
        ?string $existingPath = null,
        ?string $baseName = null
    ): ?string {
        if (! $file) {
            return $existingPath;
        }

        $destination = public_path('uploads/' . trim($folder, '/'));

        if (! File::exists($destination)) {
            File::makeDirectory($destination, 0755, true);
        }

        if ($existingPath) {
            $existingAbsolutePath = public_path($existingPath);
            if (File::exists($existingAbsolutePath)) {
                File::delete($existingAbsolutePath);
            }
        }

        $filename = $baseName
            ? self::buildNamedFilename($file, $baseName)
            : Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();
        $file->move($destination, $filename);

        return 'uploads/' . trim($folder, '/') . '/' . $filename;
    }

    public static function delete(?string $path): void
    {
        if (! $path) {
            return;
        }

        $absolutePath = public_path($path);

        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    private static function buildNamedFilename(UploadedFile $file, string $baseName): string
    {
        $slug = Str::slug($baseName, '_');
        $slug = $slug !== '' ? $slug : 'candidate';
        $unique = Str::lower(Str::random(10));

        return $slug . '_' . $unique . '.' . $file->getClientOriginalExtension();
    }
}
