<?php

namespace App\Http\Controllers\Backend\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Downscale and re-encode images as they are uploaded through the panel.
 *
 * Uploads used to be stored exactly as they came off the admin's disk, so a photo
 * straight from a phone or a stock download landed on the site at its full size —
 * the events and success-story images on the home page were 865 KB and 917 KB of a
 * 7 MB page. Nothing in the layouts renders an image wider than about 1300px, so
 * the bytes past that were never visible.
 *
 * Anything GD cannot open (SVG, .ico) is stored untouched rather than rejected, so
 * this never blocks a legitimate upload.
 */
trait OptimizesImageUploads
{
    /** Longest edge kept. Above this the image is proportionally downscaled. */
    protected int $imageMaxEdge = 1600;

    /** WebP quality. 82 is visually transparent for photographs at these sizes. */
    protected int $imageQuality = 82;

    /**
     * Store an uploaded image on the public disk and return the /public-relative
     * path ("storage/…") the models keep.
     */
    protected function storeOptimizedImage(
        UploadedFile $file,
        string $directory,
        ?int $maxEdge = null,
        ?int $quality = null,
    ): string {
        $binary = $this->optimizedWebp($file, $maxEdge ?? $this->imageMaxEdge, $quality ?? $this->imageQuality);

        if ($binary === null) {
            return 'storage/' . $file->store($directory, 'public');
        }

        $path = $directory . '/' . Str::random(40) . '.webp';

        Storage::disk('public')->put($path, $binary);

        return 'storage/' . $path;
    }

    /**
     * The re-encoded WebP bytes, or null when this file should be stored as-is.
     *
     * GIF is deliberately excluded: GD would flatten an animated one to a single
     * frame, which is a silent loss of content rather than an optimisation.
     */
    private function optimizedWebp(UploadedFile $file, int $maxEdge, int $quality): ?string
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return null;
        }

        $contents = @file_get_contents($file->getRealPath());

        if ($contents === false) {
            return null;
        }

        $image = @imagecreatefromstring($contents);

        if (! $image) {
            return null;
        }

        $image = $this->downscale($image, $maxEdge);

        // Keep cut-outs and logos transparent rather than compositing them on black.
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        $ok = imagewebp($image, null, $quality);
        $binary = ob_get_clean();

        imagedestroy($image);

        return ($ok && $binary !== '' && $binary !== false) ? $binary : null;
    }

    /** @return \GdImage the original when it already fits, otherwise a resized copy */
    private function downscale(\GdImage $image, int $maxEdge): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest <= $maxEdge) {
            return $image;
        }

        $scale = $maxEdge / $longest;
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }
}
