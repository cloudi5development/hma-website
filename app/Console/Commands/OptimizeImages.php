<?php

namespace App\Console\Commands;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Course;
use App\Models\Event;
use App\Models\Hero;
use App\Models\Partner;
use App\Models\SeoPage;
use App\Models\Setting;
use App\Models\SuccessStory;
use App\Models\Testimonial;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * Re-encode the images the site already references.
 *
 * The upload path optimises everything from here on (see
 * Backend\Concerns\OptimizesImageUploads), but images stored before that — and
 * the seeded artwork under public/assets — are still full-size: the home page was
 * carrying a 917 KB success-story photo and an 865 KB event photo.
 *
 * A WebP copy is written next to the original and the database is pointed at it.
 * The original file is left on disk, so this is reversible: restore the column
 * value and the old file is still there.
 *
 *   php artisan images:optimize --dry-run
 *   php artisan images:optimize
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize
                            {--dry-run : List what would change without writing anything}
                            {--min-kb=60 : Skip files already smaller than this}';

    protected $description = 'Convert oversized site images to WebP and repoint the database at them';

    /**
     * model => [column => max long edge]. The caps come from how large each image
     * is actually rendered in the layouts; anything past that is invisible weight.
     */
    private const TARGETS = [
        SuccessStory::class => ['image' => 1200],
        Event::class        => ['image' => 1200],
        Blog::class         => ['image' => 1200],
        Course::class       => ['image' => 1000],
        Hero::class         => ['image' => 1400],
        Partner::class      => ['logo' => 480],
        Testimonial::class  => ['photo' => 400],
        Category::class     => ['icon' => 512],
        SeoPage::class      => ['og_image' => 1200, 'twitter_image' => 1200],
    ];

    /** Settings rows that hold an image path. The favicon is left alone. */
    private const SETTING_KEYS = ['site_logo' => 600, 'seo_default_og_image' => 1200];

    private int $saved = 0;

    private int $converted = 0;

    /**
     * Source paths already handled this run, mapped to their new path. Several
     * records can share one file (six testimonials share six seeded avatars), and
     * without this the same conversion would be reported — and re-encoded — once
     * per record.
     *
     * @var array<string, string>
     */
    private array $done = [];

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('This PHP build has no WebP support (GD), so there is nothing to do.');

            return self::FAILURE;
        }

        foreach (self::TARGETS as $model => $columns) {
            foreach ($columns as $column => $maxEdge) {
                $this->processModel($model, $column, $maxEdge);
            }
        }

        foreach (self::SETTING_KEYS as $key => $maxEdge) {
            $path = Setting::get($key);

            if ($path && ($new = $this->convert($path, $maxEdge))) {
                if (! $this->option('dry-run')) {
                    Setting::putMany([$key => $new]);
                }
            }
        }

        $this->newLine();
        $this->info(sprintf(
            '%s %d image(s), saving %s KB.',
            $this->option('dry-run') ? 'Would convert' : 'Converted',
            $this->converted,
            number_format($this->saved / 1024),
        ));

        return self::SUCCESS;
    }

    private function processModel(string $model, string $column, int $maxEdge): void
    {
        /** @var Model $model */
        $model::query()->whereNotNull($column)->where($column, '!=', '')->each(
            function (Model $record) use ($column, $maxEdge): void {
                $new = $this->convert($record->{$column}, $maxEdge);

                if ($new && ! $this->option('dry-run')) {
                    $record->forceFill([$column => $new])->saveQuietly();
                }
            }
        );
    }

    /**
     * Write an optimised WebP next to $relative and return its new public-relative
     * path, or null when there is nothing worth doing.
     */
    private function convert(string $relative, int $maxEdge): ?string
    {
        $absolute = public_path($relative);

        if (! is_file($absolute)) {
            return null;
        }

        // Shared by another record and already converted — reuse the result.
        if (array_key_exists($relative, $this->done)) {
            return $this->done[$relative];
        }

        $before = filesize($absolute);

        if ($before < (int) $this->option('min-kb') * 1024) {
            return null;
        }

        // A .webp source would convert onto itself, overwriting the original and
        // making this irreversible. Those are left alone — the saving on an already
        // WebP-encoded file is not worth destroying the only copy of it.
        if (strtolower(pathinfo($relative, PATHINFO_EXTENSION)) === 'webp') {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($absolute));

        if (! $image) {
            return null;    // SVG, .ico, or something GD cannot read
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest > $maxEdge) {
            $scale = $maxEdge / $longest;
            $newWidth = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $target = preg_replace('/\.[a-z0-9]+$/i', '.webp', $relative);
        $targetAbsolute = public_path($target);

        // An already-converted file whose column was never repointed, or a rerun.
        $writing = ! $this->option('dry-run');

        if ($writing) {
            imagewebp($image, $targetAbsolute, 82);
        }

        imagedestroy($image);

        $after = ($writing && is_file($targetAbsolute)) ? filesize($targetAbsolute) : (int) ($before * 0.15);

        // Converting is only worth it if the result is actually smaller.
        if ($after >= $before) {
            return null;
        }

        $this->converted++;
        $this->saved += $before - $after;
        $this->done[$relative] = $target;

        $this->line(sprintf(
            '  %-58s %6s KB -> %5s KB',
            \Illuminate\Support\Str::limit($relative, 56),
            number_format($before / 1024),
            number_format($after / 1024),
        ));

        return $target;
    }
}
