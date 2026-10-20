<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Downscales and re-encodes avatars before they are stored.
 *
 * Avatars are displayed at ~44 px, but users routinely upload multi-megabyte
 * photos (a 381x387 PNG came through at 203 KB and a JPEG at 500 KB). Storing
 * them raw means every page that renders a header/assignee avatar pays for the
 * original bytes and Laravel's public disk serves them with no cache header
 * behind a plain file store. Resizing to a small square-ish box and encoding
 * as WebP keeps the payload in the tens of kilobytes without visible loss.
 */
class AvatarOptimizer
{
    /** Largest edge we ever store; ~256 px is comfortably sharp for a 48 px slot. */
    public const MAX_DIMENSION = 256;

    /** WebP quality for photographs. */
    public const QUALITY = 82;

    /**
     * Optimize an upload and store it, returning the stored path.
     *
     * Falls back to a plain store when the bytes cannot be decoded (GD absent,
     * corrupt file, exotic format), so the original is never silently dropped.
     */
    public function storeUploaded(UploadedFile $file, string $directory = 'avatars', string $disk = 'public'): string
    {
        $optimized = $this->optimize((string) file_get_contents($file->getRealPath()));

        if ($optimized === null) {
            return $file->store($directory, $disk);
        }

        return $this->put($optimized['binary'], $optimized['extension'], $directory, $disk);
    }

    /**
     * Re-optimize a file already on the disk, returning its new path.
     *
     * Returns null when the file is missing, is an external URL, or cannot be
     * decoded, leaving the caller's existing path untouched.
     */
    public function optimizeStored(string $path, string $disk = 'public'): ?string
    {
        if (preg_match('/^(https?:)?\/\//', $path) || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $optimized = $this->optimize(Storage::disk($disk)->get($path));

        if ($optimized === null) {
            return null;
        }

        $directory = trim(dirname($path), './');

        return $this->put($optimized['binary'], $optimized['extension'], $directory ?: 'avatars', $disk);
    }

    /**
     * @return array{extension: string, binary: string}|null
     */
    private function optimize(string $binary): ?array
    {
        if ($binary === '' || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            return null;
        }

        [$targetWidth, $targetHeight] = $this->fit(imagesx($source), imagesy($source), self::MAX_DIMENSION);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);

        // Preserve transparency so PNG avatars do not turn black on resample.
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefilledrectangle(
            $canvas,
            0,
            0,
            $targetWidth,
            $targetHeight,
            imagecolorallocatealpha($canvas, 0, 0, 0, 127)
        );

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            imagesx($source),
            imagesy($source)
        );

        [$extension, $encoded] = $this->encode($canvas);

        imagedestroy($canvas);
        imagedestroy($source);

        return ['extension' => $extension, 'binary' => $encoded];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function encode(\GdImage $image): array
    {
        ob_start();

        // WebP supports alpha and beats JPEG on photographic avatars.
        if (imagetypes() & IMG_WEBP) {
            imagewebp($image, null, self::QUALITY);

            return ['webp', (string) ob_get_clean()];
        }

        imagejpeg($image, null, self::QUALITY);

        return ['jpg', (string) ob_get_clean()];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function fit(int $width, int $height, int $max): array
    {
        if ($width <= $max && $height <= $max) {
            return [max(1, $width), max(1, $height)];
        }

        $ratio = min($max / $width, $max / $height);

        return [max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio))];
    }

    private function put(string $binary, string $extension, string $directory, string $disk): string
    {
        $directory = trim($directory, '/');
        $name = bin2hex(random_bytes(20)).'.'.$extension;
        $path = $directory !== '' ? $directory.'/'.$name : $name;

        Storage::disk($disk)->put($path, $binary);

        return $path;
    }
}
