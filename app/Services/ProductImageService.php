<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProductImageService
{
    public const MAX_UPLOAD_KB = 5120;
    private const MAX_OUTPUT_BYTES = 1_000_000;
    private const MAX_DIMENSION = 1600;

    public function convertAndStore(UploadedFile $file): string
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('Ekstensi GD dengan dukungan WebP belum aktif di server.');
        }

        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $source) {
            throw new RuntimeException('Gambar tidak dapat dibaca.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width < 1 || $height < 1 || $width * $height > 40_000_000) {
            imagedestroy($source);
            throw new RuntimeException('Dimensi gambar terlalu besar.');
        }

        $scale = min(1, self::MAX_DIMENSION / max($width, $height));
        $target = imagecreatetruecolor(max(1, (int) ($width * $scale)), max(1, (int) ($height * $scale)));
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);
        imagedestroy($source);

        $temp = tempnam(sys_get_temp_dir(), 'product-webp-');
        try {
            for ($quality = 82; $quality >= 50; $quality -= 8) {
                imagewebp($target, $temp, $quality);
                if (filesize($temp) <= self::MAX_OUTPUT_BYTES) break;
                $new = imagecreatetruecolor(max(1, (int) (imagesx($target) * 0.8)), max(1, (int) (imagesy($target) * 0.8)));
                imagealphablending($new, false); imagesavealpha($new, true);
                imagecopyresampled($new, $target, 0, 0, 0, 0, imagesx($new), imagesy($new), imagesx($target), imagesy($target));
                imagedestroy($target); $target = $new;
            }
            if (filesize($temp) > self::MAX_OUTPUT_BYTES) throw new RuntimeException('Ukuran hasil WebP masih terlalu besar. Gunakan gambar yang lebih sederhana.');
            $path = 'products/'.Str::uuid().'.webp';
            Storage::disk('public')->put($path, file_get_contents($temp));
            return $path;
        } finally {
            imagedestroy($target);
            @unlink($temp);
        }
    }
}
