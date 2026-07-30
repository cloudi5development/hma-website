<?php

namespace Tests\Feature\Backend;

use App\Http\Controllers\Backend\Concerns\OptimizesImageUploads;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageUploadOptimizationTest extends TestCase
{
    /** Exposes the trait's protected helper so it can be exercised on its own. */
    private function optimizer(): object
    {
        return new class
        {
            use OptimizesImageUploads;

            public function store(UploadedFile $file, string $directory, ?int $maxEdge = null): string
            {
                return $this->storeOptimizedImage($file, $directory, $maxEdge);
            }
        };
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_a_large_upload_is_downscaled_and_stored_as_webp(): void
    {
        $source = UploadedFile::fake()->image('huge.png', 4000, 3000);
        $original = $source->getSize();

        $path = $this->optimizer()->store($source, 'events');

        $this->assertStringStartsWith('storage/events/', $path);
        $this->assertStringEndsWith('.webp', $path);

        $stored = substr($path, strlen('storage/'));
        Storage::disk('public')->assertExists($stored);

        $bytes = Storage::disk('public')->get($stored);
        $this->assertNotEmpty($bytes);
        $this->assertLessThan($original, strlen($bytes));

        // Long edge capped at the default 1600.
        [$width, $height] = getimagesizefromstring($bytes);
        $this->assertSame(1600, $width);
        $this->assertSame(1200, $height);
    }

    public function test_the_max_edge_can_be_tightened_per_module(): void
    {
        // Partner logos and testimonial photos render small, so they pass a cap.
        $path = $this->optimizer()->store(
            UploadedFile::fake()->image('logo.png', 1200, 600),
            'partners',
            480,
        );

        [$width] = getimagesizefromstring(Storage::disk('public')->get(substr($path, strlen('storage/'))));

        $this->assertSame(480, $width);
    }

    public function test_an_image_already_small_enough_is_not_upscaled(): void
    {
        $path = $this->optimizer()->store(UploadedFile::fake()->image('small.png', 300, 200), 'blogs');

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get(substr($path, strlen('storage/'))));

        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_a_file_gd_cannot_read_is_stored_untouched(): void
    {
        // An SVG logo must still upload — it is just kept as-is rather than rasterised.
        $svg = UploadedFile::fake()->createWithContent(
            'logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>',
        );

        $path = $this->optimizer()->store($svg, 'branding');

        $this->assertStringEndsWith('.svg', $path);
        Storage::disk('public')->assertExists(substr($path, strlen('storage/')));
    }
}
