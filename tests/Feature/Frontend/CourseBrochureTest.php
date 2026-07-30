<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CourseBrochureTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession(['admin_logged_in' => true, 'admin_id' => $admin->id]);
    }

    private function category(): Category
    {
        $department = Department::create(['name' => 'IT', 'slug' => 'it', 'is_active' => true]);

        return Category::create([
            'department_id' => $department->id,
            'name'          => 'Programming',
            'slug'          => 'programming',
            'is_active'     => true,
        ]);
    }

    /** A file with a real %PDF signature, so the mimetypes rule passes. */
    private function pdf(string $name = 'brochure.pdf', int $kilobytes = 40): UploadedFile
    {
        $body = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"
            . str_repeat('0', max(0, $kilobytes * 1024 - 32))
            . "\n%%EOF\n";

        return UploadedFile::fake()->createWithContent($name, $body);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'category_id'      => $this->category()->id,
            'name'             => 'IT Software Fundamentals',
            'batch_start_date' => '2026-09-01',
            'duration'         => '6 Months',
            'training_mode'    => 'Offline',
            'skill_level'      => 'Beginner',
            'is_active'        => 1,
            'image'            => UploadedFile::fake()->image('course.png', 900, 600),
        ], $overrides);
    }

    public function test_the_admin_can_upload_a_brochure_and_visitors_download_it(): void
    {
        Storage::fake('public');

        $this->asAdmin()
            ->post(route('backend.courses.store'), $this->payload(['brochure' => $this->pdf()]))
            ->assertRedirect(route('backend.courses.index'));

        $course = Course::firstOrFail();

        $this->assertNotNull($course->brochure);
        $this->assertStringStartsWith('storage/courses/brochures/', $course->brochure);
        $this->assertStringEndsWith('.pdf', $course->brochure);
        Storage::disk('public')->assertExists(substr($course->brochure, strlen('storage/')));

        // The details page offers it, and the download works.
        $this->get(route('frontend.course-details', $course->slug))
            ->assertOk()
            ->assertSee(route('frontend.course-brochure', $course->slug), false)
            ->assertSee('Brochure');

        $response = $this->get(route('frontend.course-brochure', $course->slug));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'attachment; filename=it-software-fundamentals-brochure.pdf',
            $response->headers->get('content-disposition'),
        );
    }

    public function test_without_a_brochure_the_button_stays_but_is_inert(): void
    {
        Storage::fake('public');

        $this->asAdmin()->post(route('backend.courses.store'), $this->payload());

        $course = Course::firstOrFail();

        $this->assertNull($course->brochure);
        $this->assertFalse($course->has_brochure);

        $html = $this->get(route('frontend.course-details', $course->slug))->assertOk()->getContent();

        // The button keeps its place next to "Enroll Now" — the hero is a pair of
        // buttons by design — but it is not a link and cannot be clicked.
        $this->assertStringContainsString('>Brochure<', $html);
        $this->assertStringContainsString('hm-cd-btn--ghost is-disabled', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertStringNotContainsString(route('frontend.course-brochure', $course->slug), $html);

        // And the route itself is not reachable by hand.
        $this->get(route('frontend.course-brochure', $course->slug))->assertNotFound();
    }

    public function test_with_a_brochure_the_button_becomes_a_live_download_link(): void
    {
        Storage::fake('public');

        $this->asAdmin()->post(route('backend.courses.store'), $this->payload(['brochure' => $this->pdf()]));

        $course = Course::firstOrFail();
        $html = $this->get(route('frontend.course-details', $course->slug))->assertOk()->getContent();

        $this->assertStringContainsString('>Brochure<', $html);
        $this->assertStringContainsString(route('frontend.course-brochure', $course->slug), $html);
        $this->assertStringNotContainsString('is-disabled', $html);
    }

    public function test_a_non_pdf_upload_is_rejected(): void
    {
        Storage::fake('public');

        $this->asAdmin()
            ->post(route('backend.courses.store'), $this->payload([
                'brochure' => UploadedFile::fake()->image('not-a-pdf.png', 400, 400),
            ]))
            ->assertSessionHasErrors('brochure');

        $this->assertSame(0, Course::count());
    }

    // Note: there is no test for "a .exe renamed to .pdf". The rules carry
    // mimetypes:application/pdf, which Symfony resolves by sniffing the file's
    // real content — but UploadedFile::fake() reports a mime type guessed from the
    // filename, so a fake can never exercise that path. A test here would pass
    // regardless of whether the rule were present, which is worse than no test.

    public function test_an_oversized_brochure_is_rejected(): void
    {
        Storage::fake('public');

        $this->asAdmin()
            ->post(route('backend.courses.store'), $this->payload([
                'brochure' => $this->pdf('huge.pdf', 11 * 1024),   // 11 MB, cap is 10
            ]))
            ->assertSessionHasErrors('brochure');

        $this->assertSame(0, Course::count());
    }

    public function test_uploading_again_replaces_the_old_file(): void
    {
        Storage::fake('public');

        $this->asAdmin()->post(route('backend.courses.store'), $this->payload(['brochure' => $this->pdf('first.pdf')]));

        $course = Course::firstOrFail();
        $first = $course->brochure;

        $this->asAdmin()
            ->put(route('backend.courses.update', $course), [
                'category_id'      => $course->category_id,
                'name'             => $course->name,
                'batch_start_date' => '2026-09-01',
                'duration'         => '6 Months',
                'training_mode'    => 'Offline',
                'skill_level'      => 'Beginner',
                'is_active'        => 1,
                'brochure'         => $this->pdf('second.pdf'),
            ])
            ->assertRedirect(route('backend.courses.index'));

        $course->refresh();

        $this->assertNotSame($first, $course->brochure);
        Storage::disk('public')->assertMissing(substr($first, strlen('storage/')));
        Storage::disk('public')->assertExists(substr($course->brochure, strlen('storage/')));
    }

    public function test_ticking_remove_clears_the_brochure(): void
    {
        Storage::fake('public');

        $this->asAdmin()->post(route('backend.courses.store'), $this->payload(['brochure' => $this->pdf()]));

        $course = Course::firstOrFail();
        $stored = $course->brochure;

        $this->asAdmin()
            ->put(route('backend.courses.update', $course), [
                'category_id'      => $course->category_id,
                'name'             => $course->name,
                'batch_start_date' => '2026-09-01',
                'duration'         => '6 Months',
                'training_mode'    => 'Offline',
                'skill_level'      => 'Beginner',
                'is_active'        => 1,
                'remove_brochure'  => 1,
            ])
            ->assertRedirect(route('backend.courses.index'));

        $this->assertNull($course->fresh()->brochure);
        Storage::disk('public')->assertMissing(substr($stored, strlen('storage/')));
    }

    public function test_an_edit_that_touches_neither_field_keeps_the_brochure(): void
    {
        Storage::fake('public');

        $this->asAdmin()->post(route('backend.courses.store'), $this->payload(['brochure' => $this->pdf()]));

        $course = Course::firstOrFail();
        $stored = $course->brochure;

        $this->asAdmin()
            ->put(route('backend.courses.update', $course), [
                'category_id'      => $course->category_id,
                'name'             => 'Renamed Course',
                'batch_start_date' => '2026-09-01',
                'duration'         => '6 Months',
                'training_mode'    => 'Offline',
                'skill_level'      => 'Beginner',
                'is_active'        => 1,
            ])
            ->assertRedirect(route('backend.courses.index'));

        $this->assertSame($stored, $course->fresh()->brochure);
    }

    public function test_deleting_the_course_removes_the_uploaded_pdf(): void
    {
        Storage::fake('public');

        $this->asAdmin()->post(route('backend.courses.store'), $this->payload(['brochure' => $this->pdf()]));

        $course = Course::firstOrFail();
        $stored = $course->brochure;

        $this->asAdmin()
            ->delete(route('backend.courses.destroy', $course))
            ->assertRedirect(route('backend.courses.index'));

        Storage::disk('public')->assertMissing(substr($stored, strlen('storage/')));
    }

    public function test_an_inactive_course_does_not_serve_its_brochure(): void
    {
        Storage::fake('public');

        $this->asAdmin()->post(route('backend.courses.store'), $this->payload(['brochure' => $this->pdf()]));

        $course = Course::firstOrFail();
        $course->forceFill(['is_active' => false])->save();

        $this->get(route('frontend.course-brochure', $course->slug))->assertNotFound();
    }
}
