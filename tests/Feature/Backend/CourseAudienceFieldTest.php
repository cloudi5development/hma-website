<?php

namespace Tests\Feature\Backend;

use App\Models\Category;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The Audience field, end to end on the manual path: the form renders it, a
 * create and an update both persist it, and the public course page prints it
 * only when a course actually has one. The bulk-upload half is covered in
 * CourseBulkUploadTest.
 */
class CourseAudienceFieldTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::create(['name' => 'Tech', 'slug' => 'tech']);
        $this->category = Category::create([
            'department_id' => $department->id,
            'name'          => 'IT & Software',
            'slug'          => 'it-software',
        ]);
    }

    private function admin(): User
    {
        return User::where('is_super_admin', true)->firstOrFail();
    }

    private function signedIn(User $user): self
    {
        return $this->withSession([
            'admin_logged_in' => true,
            'admin_id'        => $user->id,
            'admin_name'      => $user->name,
            'admin_email'     => $user->email,
        ]);
    }

    public function test_the_create_form_shows_the_audience_field(): void
    {
        $this->signedIn($this->admin())
            ->get(route('backend.courses.create'))
            ->assertOk()
            ->assertSee('name="audience"', false)
            ->assertSee('Audience');
    }

    public function test_store_and_update_persist_the_audience(): void
    {
        Storage::fake('public');

        $admin = $this->admin();

        $payload = [
            'category_id'      => $this->category->id,
            'name'             => 'Full Stack Development',
            'duration'         => '6 Months',
            'skill_level'      => 'Beginner',
            'audience'         => "Freshers\nWorking professionals",
            'image'            => UploadedFile::fake()->image('course.jpg', 600, 400),
        ];

        $this->signedIn($admin)
            ->post(route('backend.courses.store'), $payload)
            ->assertRedirect(route('backend.courses.index'))
            ->assertSessionHasNoErrors();

        $course = Course::firstOrFail();
        $this->assertSame("Freshers\nWorking professionals", $course->audience);

        // Edit form shows what was stored, and the update writes the new value.
        $this->signedIn($admin)
            ->get(route('backend.courses.edit', $course))
            ->assertOk()
            ->assertSee('Working professionals', false);

        $this->signedIn($admin)->put(route('backend.courses.update', $course), [
            'category_id'      => $this->category->id,
            'name'             => 'Full Stack Development',
            'duration'         => '6 Months',
            'skill_level'      => 'Beginner',
            'audience'         => 'Graduates only.',
        ])->assertRedirect(route('backend.courses.index'))->assertSessionHasNoErrors();

        $this->assertSame('Graduates only.', $course->fresh()->audience);
    }

    public function test_the_details_page_shows_the_audience_only_when_set(): void
    {
        $course = Course::create([
            'category_id'      => $this->category->id,
            'name'             => 'Quiet Course',
            'slug'             => 'quiet-course',
            'duration'         => '6 Months',
            'skill_level'      => 'Beginner',
        ]);

        $this->get(route('frontend.course-details', $course->slug))
            ->assertOk()
            ->assertDontSee('hm-cd-aud__list', false);

        $course->update(['audience' => "Freshers\nCareer switchers"]);

        $this->get(route('frontend.course-details', $course->slug))
            ->assertOk()
            ->assertSee('Who This Course Is For')
            ->assertSee('Career switchers', false);

        // Two lines become two chips; a prose sentence stays one.
        $this->assertSame(
            2,
            substr_count(
                $this->get(route('frontend.course-details', $course->slug))->getContent(),
                'hm-cd-aud__chip',
            ),
        );

        $course->update(['audience' => 'Anyone curious about the web, whatever their background.']);

        $this->assertSame(
            1,
            substr_count(
                $this->get(route('frontend.course-details', $course->slug))->getContent(),
                'hm-cd-aud__chip',
            ),
        );
    }
}
