<?php

namespace Tests\Feature\Backend;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The batch start date and the training mode moved off the course and onto the
 * batch (2026-08-19).
 *
 * Three things have to hold for that move to be finished, and they are what this
 * covers: the course form no longer asks for either field, the schedule form
 * does ask for the mode, and every website surface that used to print the
 * course's own copy now reads the soonest upcoming batch instead.
 */
class ScheduleBatchFieldsTest extends TestCase
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

    private function course(array $attributes = []): Course
    {
        return Course::create($attributes + [
            'category_id' => $this->category->id,
            'name'        => 'Full Stack Development',
            'slug'        => 'full-stack-development',
            'duration'    => '6 Months',
            'skill_level' => 'Beginner',
            'is_active'   => true,
        ]);
    }

    private function batch(Course $course, array $attributes = []): CourseSchedule
    {
        return $course->schedules()->create($attributes + [
            'start_date'    => Carbon::today()->addDays(30)->toDateString(),
            'end_date'      => Carbon::today()->addDays(120)->toDateString(),
            'training_mode' => 'Hybrid',
            'is_active'     => true,
            'show_fee'      => true,
        ]);
    }

    // ------------------------------------------------------------ admin forms

    public function test_the_course_form_no_longer_asks_for_a_batch_date_or_a_mode(): void
    {
        $html = $this->signedIn($this->admin())
            ->get(route('backend.courses.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('name="batch_start_date"', $html);
        $this->assertStringNotContainsString('name="training_mode"', $html);
    }

    public function test_a_course_saves_without_a_batch_date_or_a_mode(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $this->signedIn($this->admin())
            ->post(route('backend.courses.store'), [
                'category_id' => $this->category->id,
                'name'        => 'Data Science',
                'duration'    => '3 Months',
                'skill_level' => 'Beginner',
                'image'       => \Illuminate\Http\UploadedFile::fake()->image('course.jpg', 600, 400),
            ])
            ->assertRedirect(route('backend.courses.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Data Science', Course::firstOrFail()->name);
    }

    public function test_the_schedule_form_asks_for_the_mode(): void
    {
        $html = $this->signedIn($this->admin())
            ->get(route('backend.schedules.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="training_mode"', $html);

        foreach (CourseSchedule::TRAINING_MODES as $mode) {
            $this->assertStringContainsString('value="' . $mode . '"', $html);
        }
    }

    public function test_a_batch_stores_its_mode_and_refuses_to_save_without_one(): void
    {
        $course = $this->course();

        $payload = [
            'course_id'     => $course->id,
            'start_date'    => Carbon::today()->addDays(10)->toDateString(),
            'end_date'      => Carbon::today()->addDays(80)->toDateString(),
            'training_mode' => 'Online',
        ];

        $this->signedIn($this->admin())
            ->post(route('backend.schedules.store'), $payload)
            ->assertRedirect(route('backend.schedules.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Online', CourseSchedule::firstOrFail()->training_mode);

        // The mode is the only place the site can learn it, so it is required
        // rather than optional like the batch's other extras.
        $this->signedIn($this->admin())
            ->post(route('backend.schedules.store'), ['training_mode' => ''] + $payload)
            ->assertSessionHasErrors('training_mode');

        // ...and an invented one is refused rather than stored verbatim.
        $this->signedIn($this->admin())
            ->post(route('backend.schedules.store'), ['training_mode' => 'Telepathic'] + $payload)
            ->assertSessionHasErrors('training_mode');
    }

    // -------------------------------------------------------------- the model

    public function test_a_course_reads_its_dates_and_mode_off_the_soonest_upcoming_batch(): void
    {
        $course = $this->course();

        // Deliberately created out of order, and with a finished batch in the
        // mix, so "soonest upcoming" is doing real work rather than "first row".
        $this->batch($course, [
            'start_date'    => Carbon::today()->addDays(90)->toDateString(),
            'end_date'      => Carbon::today()->addDays(180)->toDateString(),
            'training_mode' => 'Online',
        ]);
        $this->batch($course, [
            'start_date'    => Carbon::today()->subDays(200)->toDateString(),
            'end_date'      => Carbon::today()->subDays(100)->toDateString(),
            'training_mode' => 'Offline',
        ]);
        $soonest = $this->batch($course, [
            'start_date'    => Carbon::today()->addDays(20)->toDateString(),
            'end_date'      => Carbon::today()->addDays(110)->toDateString(),
            'training_mode' => 'Hybrid',
        ]);

        $course->refresh();

        $this->assertSame('Hybrid', $course->training_mode);
        $this->assertSame($soonest->start_date->toDateString(), $course->batch_start_iso);
        $this->assertSame(
            $soonest->start_date_label . ' – ' . $soonest->end_date_label,
            $course->batch_range_label,
        );
    }

    public function test_a_course_with_nothing_scheduled_has_no_dates_or_mode(): void
    {
        $course = $this->course();

        // A batch the site would not list must not speak for the course either.
        $this->batch($course, [
            'start_date' => Carbon::today()->addDays(30)->toDateString(),
            'end_date'   => Carbon::today()->addDays(60)->toDateString(),
            'is_active'  => false,
        ]);

        $course->refresh();

        $this->assertNull($course->training_mode);
        $this->assertNull($course->batch_start_iso);
        $this->assertNull($course->batch_range_label);
    }

    // ------------------------------------------------------------ the website

    public function test_the_course_page_prints_the_next_batch_dates_and_mode(): void
    {
        $course  = $this->course();
        $soonest = $this->batch($course, ['training_mode' => 'Hybrid']);

        $html = $this->get(route('frontend.course-details', $course->slug))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($soonest->start_date_label, $html);
        $this->assertStringContainsString($soonest->end_date_label, $html);
        $this->assertStringContainsString('Hybrid', $html);
    }

    public function test_the_course_page_drops_the_date_line_when_nothing_is_scheduled(): void
    {
        $course = $this->course();

        $this->get(route('frontend.course-details', $course->slug))
            ->assertOk()
            // The hero's calendar item, and the "Mode" stat, are both left out
            // rather than rendered empty.
            ->assertDontSee('assets/images/blog/calendar.png', false)
            ->assertDontSee('>Mode<', false);
    }

    public function test_the_listing_card_shows_the_next_batch_mode(): void
    {
        $course = $this->course();
        $this->batch($course, ['training_mode' => 'Offline']);

        $this->get(route('frontend.courses'))
            ->assertOk()
            ->assertSee('Offline');
    }

    public function test_the_schedule_table_shows_the_batch_mode(): void
    {
        $course = $this->course();
        $this->batch($course, ['training_mode' => 'Online', 'duration' => '4 Months']);

        $this->get(route('frontend.schedules'))
            ->assertOk()
            ->assertSee('4 Months')
            ->assertSee('Online');
    }
}
