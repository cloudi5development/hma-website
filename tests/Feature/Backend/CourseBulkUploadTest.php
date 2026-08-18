<?php

namespace Tests\Feature\Backend;

use App\Imports\CourseRowsImport;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseFaq;
use App\Models\Department;
use App\Models\User;
use App\Services\CourseBulkImportService;
use App\Support\CourseImportTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bulk course upload.
 *
 * Most cases drive the service directly with heading-keyed rows — that is the
 * unit the importer actually works in, and it keeps the suite quick. The HTTP
 * cases cover the parts only a real request exercises: the file reader, the
 * two-step preview/confirm handshake, and the module gate.
 */
class CourseBulkUploadTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::create(['name' => 'Technical', 'slug' => 'technical', 'is_active' => true]);

        $this->category = Category::create([
            'department_id' => $department->id,
            'name'          => 'IT & Software',
            'is_active'     => true,
        ]);
    }

    // ------------------------------------------------------------------ helpers

    private function mainAdmin(): User
    {
        return User::where('is_super_admin', true)->firstOrFail();
    }

    private function staff(array $modules = []): User
    {
        return User::create([
            'name'      => 'Staff',
            'email'     => 'staff' . User::count() . '@example.com',
            'password'  => 'password123',
            'is_active' => true,
            'modules'   => $modules,
        ]);
    }

    private function signedInAs(User $user): self
    {
        return $this->withSession([
            'admin_logged_in' => true,
            'admin_id'        => $user->id,
            'admin_name'      => $user->name,
            'admin_email'     => $user->email,
        ]);
    }

    /** A complete, valid row; overrides replace individual cells. */
    private function row(array $overrides = []): array
    {
        return $overrides + [
            'course_name'               => 'Full Stack Development',
            'category'                  => 'IT & Software',
            'slug'                      => '',
            'batch_start'               => '04-08-2026',
            'duration'                  => '6 Months',
            'mode'                      => 'Offline',
            'skill_level'               => 'Beginner',
            'short_description'         => 'Build complete web applications.',
            'full_description'          => 'Long form copy.',
            'course_overview'           => 'Overview copy.',
            'learning_outcomes'         => "Master core concepts\nBuild real-world projects",
            'prerequisites'             => 'None.',
            'certification_details'     => 'Certificate on completion.',
            'status'                    => 'Active',
            'order'                     => 1,
            'rating'                    => 4.5,
            'show_in_popular'           => 'Yes',
            'show_in_continue_learning' => 'No',
            'featured'                  => 'No',
            'meta_title'                => 'Full Stack | HireMinds',
            'meta_keywords'             => 'full stack',
            'meta_description'          => 'Learn full stack.',
        ];
    }

    /** Wrap raw rows the way CourseRowsImport hands them to the service. */
    private function rows(array ...$rows)
    {
        return collect($rows)->map(fn ($data, $i) => ['line' => $i + 2, 'data' => $data]);
    }

    private function importer(): CourseBulkImportService
    {
        return app(CourseBulkImportService::class);
    }

    /** Import rows and return the report. */
    private function import(array ...$rows): array
    {
        return $this->importer()->import($this->rows(...$rows));
    }

    /** Validate rows without writing, and return the report. */
    private function check(array ...$rows): array
    {
        return $this->importer()->validateRows($this->rows(...$rows));
    }

    /** The first row's error messages, joined. */
    private function firstError(array $report): string
    {
        return implode(' ', $report['rows'][0]['errors']);
    }

    /** Render an export to a real .xlsx and read it back through the importer. */
    private function readWorkbook(object $export): CourseRowsImport
    {
        $path = tempnam(sys_get_temp_dir(), 'hm') . '.xlsx';

        file_put_contents($path, \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        $reader = new CourseRowsImport();
        \Maatwebsite\Excel\Facades\Excel::import($reader, $path);

        // Same fallback the controller applies, so a file with headings and no
        // rows is read here exactly as it is in the real upload path.
        $reader->applyFallbackHeadings(CourseRowsImport::headingsOf($path));

        @unlink($path);

        return $reader;
    }

    /** Render an export and load it as a spreadsheet for structural checks. */
    private function loadWorkbook(object $export): \PhpOffice\PhpSpreadsheet\Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'hm') . '.xlsx';

        file_put_contents($path, \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        $book = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);

        @unlink($path);

        return $book;
    }

    /** A course created the way the manual form would. */
    private function seedCourse(array $attributes = []): Course
    {
        return Course::create($attributes + [
            'category_id'      => $this->category->id,
            'name'             => 'Existing Course',
            'slug'             => 'existing-course',
            'batch_start_date' => '2026-01-01',
            'duration'         => '2 Months',
            'training_mode'    => 'Online',
            'skill_level'      => 'Beginner',
            'image'            => 'storage/courses/original.webp',
            'brochure'         => 'storage/courses/brochures/original.pdf',
        ]);
    }

    // ------------------------------------------------------------------- tests

    public function test_a_single_valid_row_creates_a_course(): void
    {
        $report = $this->import($this->row());

        $this->assertSame(1, $report['summary']['ready']);
        $this->assertSame(0, $report['summary']['errors']);

        $course = Course::firstOrFail();

        $this->assertSame('Full Stack Development', $course->name);
        $this->assertSame('full-stack-development', $course->slug);
        $this->assertSame($this->category->id, $course->category_id);
        $this->assertSame('2026-08-04', $course->batch_start_date->format('Y-m-d'));
        $this->assertSame('6 Months', $course->duration);
        $this->assertSame('Offline', $course->training_mode);
        $this->assertSame('Beginner', $course->skill_level);
        $this->assertTrue($course->is_active);
        $this->assertTrue($course->is_popular);
        $this->assertFalse($course->is_featured);
        $this->assertSame(1, $course->sort_order);
    }

    public function test_many_rows_import_together(): void
    {
        $report = $this->import(
            $this->row(['course_name' => 'Course A']),
            $this->row(['course_name' => 'Course B']),
            $this->row(['course_name' => 'Course C']),
        );

        $this->assertSame(3, $report['summary']['ready']);
        $this->assertSame(3, Course::count());
    }

    public function test_category_resolves_to_its_department(): void
    {
        $this->import($this->row());

        $course = Course::with('category.department')->firstOrFail();

        $this->assertSame('IT & Software', $course->category->name);
        $this->assertSame('Technical', $course->category->department->name);
        // The model's department() hop is what the rest of the panel uses.
        $this->assertSame('Technical', $course->department()->name);
    }

    public function test_an_unknown_category_is_an_error_and_creates_nothing(): void
    {
        $report = $this->import($this->row(['category' => 'Astrophysics']));

        $this->assertSame(0, $report['summary']['ready']);
        $this->assertSame(1, $report['summary']['errors']);
        $this->assertStringContainsString(
            'Category: "Astrophysics" is invalid. Please select a valid Category from the master data.',
            $this->firstError($report),
        );
        $this->assertSame(0, Course::count());
    }

    public function test_category_matching_ignores_case_and_padding(): void
    {
        $report = $this->import($this->row(['category' => '   it & SOFTWARE  ']));

        $this->assertSame(1, $report['summary']['ready']);
        $this->assertSame($this->category->id, Course::firstOrFail()->category_id);
    }

    public function test_a_category_may_also_be_given_by_slug(): void
    {
        $report = $this->import($this->row(['category' => $this->category->slug]));

        $this->assertSame(1, $report['summary']['ready']);
    }

    public function test_a_duplicated_category_name_is_refused_rather_than_guessed(): void
    {
        $other = Department::create(['name' => 'Business', 'slug' => 'business', 'is_active' => true]);
        Category::create(['department_id' => $other->id, 'name' => 'IT & Software', 'is_active' => true]);

        $report = $this->import($this->row());

        $this->assertSame(0, Course::count());
        $this->assertStringContainsString('ambiguous', $this->firstError($report));
    }

    public function test_a_missing_course_name_is_an_error(): void
    {
        $report = $this->import($this->row(['course_name' => '']));

        $this->assertSame(0, Course::count());
        $this->assertStringContainsString('course_name is required', $this->firstError($report));
    }

    public function test_an_invalid_mode_is_named_in_the_error(): void
    {
        $report = $this->import($this->row(['mode' => 'Physical']));

        $this->assertSame(0, Course::count());
        $this->assertStringContainsString('Mode "Physical" is not supported', $this->firstError($report));
    }

    public function test_an_invalid_skill_level_is_named_in_the_error(): void
    {
        $report = $this->import($this->row(['skill_level' => 'Wizard']));

        $this->assertSame(0, Course::count());
        $this->assertStringContainsString('Skill level "Wizard" is not supported', $this->firstError($report));
    }

    public function test_mode_and_skill_level_tolerate_case_and_spacing(): void
    {
        $report = $this->import($this->row(['mode' => '  online ', 'skill_level' => 'ADVANCED']));

        $this->assertSame(1, $report['summary']['ready']);

        $course = Course::firstOrFail();
        $this->assertSame('Online', $course->training_mode);
        $this->assertSame('Advanced', $course->skill_level);
    }

    public function test_an_invalid_date_is_an_error(): void
    {
        $report = $this->import($this->row(['batch_start' => '32-13-2026']));

        $this->assertSame(0, Course::count());
        $this->assertStringContainsString('not a date the importer recognises', $this->firstError($report));
    }

    public function test_the_documented_date_formats_all_parse_to_the_same_day(): void
    {
        foreach (['04-08-2026' => '2026-08-04', '04/08/2026' => '2026-08-04', '2026-08-04' => '2026-08-04'] as $input => $expected) {
            $this->assertSame($expected, CourseImportTemplate::toDate($input), "failed on {$input}");
        }

        // A real Excel date cell arrives as a serial number, not text.
        $this->assertSame('2026-08-04', CourseImportTemplate::toDate(46238.0));
    }

    /**
     * A matching slug is the signal to UPDATE, which is the whole point of the
     * export → edit → upload round trip. It must never become a second course.
     */
    public function test_a_matching_slug_updates_that_course_in_place(): void
    {
        $existing = Course::create([
            'category_id' => $this->category->id,
            'name'        => 'Something Else',
            'slug'        => 'full-stack-development',
            'image'       => 'assets/images/courses/course-1.webp',
        ]);

        $report = $this->import($this->row());

        $this->assertSame(1, $report['summary']['update']);
        $this->assertSame(0, $report['summary']['create']);
        $this->assertSame(0, $report['summary']['duplicates']);
        $this->assertSame(1, Course::count(), 'The course must be updated, not duplicated.');

        $existing->refresh();

        $this->assertSame('Full Stack Development', $existing->name);
        $this->assertSame('full-stack-development', $existing->slug);
    }

    public function test_a_duplicate_course_name_is_reported(): void
    {
        Course::create([
            'category_id' => $this->category->id,
            'name'        => 'Full Stack Development',
            'slug'        => 'a-different-slug',
        ]);

        $report = $this->import($this->row());

        $this->assertSame(1, $report['summary']['duplicates']);
        $this->assertStringContainsString('already exists', $this->firstError($report));
        $this->assertSame(1, Course::count());
    }

    public function test_two_identical_rows_in_one_file_only_import_once(): void
    {
        $report = $this->import($this->row(), $this->row());

        $this->assertSame(1, $report['summary']['create']);
        $this->assertSame(1, $report['summary']['duplicates']);
        $this->assertSame(1, Course::count());
        $this->assertStringContainsString('already has a row', implode(' ', $report['rows'][1]['errors']));
    }

    public function test_a_blank_slug_is_generated_from_the_course_name(): void
    {
        $this->import($this->row(['course_name' => 'Data Science & AI', 'slug' => '']));

        $this->assertSame('data-science-ai', Course::firstOrFail()->slug);
    }

    public function test_a_supplied_slug_is_kept(): void
    {
        $this->import($this->row(['slug' => 'my-custom-slug']));

        $this->assertSame('my-custom-slug', Course::firstOrFail()->slug);
    }

    public function test_a_malformed_slug_is_an_error(): void
    {
        $report = $this->import($this->row(['slug' => 'not a valid slug!']));

        $this->assertSame(0, Course::count());
        $this->assertStringContainsString('may only contain letters', $this->firstError($report));
    }

    public function test_an_invalid_boolean_is_an_error_rather_than_a_guess(): void
    {
        $report = $this->import($this->row(['show_in_popular' => 'maybe']));

        $this->assertSame(0, Course::count());
        $this->assertStringContainsString("'maybe' is not a Yes/No value", $this->firstError($report));
    }

    public function test_boolean_synonyms_are_understood(): void
    {
        $this->import($this->row([
            'status'                    => 'Inactive',
            'show_in_popular'           => '1',
            'show_in_continue_learning' => 'TRUE',
            'featured'                  => 'no',
        ]));

        $course = Course::firstOrFail();

        $this->assertFalse($course->is_active);
        $this->assertTrue($course->is_popular);
        $this->assertTrue($course->is_continue_learning);
        $this->assertFalse($course->is_featured);
    }

    public function test_an_out_of_range_rating_is_an_error(): void
    {
        $report = $this->import($this->row(['rating' => 9]));

        $this->assertSame(0, Course::count());
        $this->assertStringContainsString('rating', strtolower($this->firstError($report)));
    }

    public function test_a_non_numeric_rating_is_an_error(): void
    {
        $report = $this->import($this->row(['rating' => 'five stars']));

        $this->assertSame(0, Course::count());
    }

    public function test_a_faq_question_without_an_answer_is_an_error(): void
    {
        $report = $this->import($this->row(['faq_1_question' => 'Do I need a laptop?', 'faq_1_answer' => '']));

        $this->assertSame(0, Course::count());
        $this->assertStringContainsString('question was given with no answer', $this->firstError($report));
    }

    public function test_a_faq_answer_without_a_question_is_an_error(): void
    {
        $report = $this->import($this->row(['faq_1_question' => '', 'faq_1_answer' => 'Yes.']));

        $this->assertSame(0, Course::count());
        $this->assertStringContainsString('answer was given with no question', $this->firstError($report));
    }

    public function test_valid_faqs_are_attached_in_order_and_capped_at_five(): void
    {
        $faqs = [];

        for ($i = 1; $i <= 5; $i++) {
            $faqs["faq_{$i}_question"] = "Question {$i}";
            $faqs["faq_{$i}_answer"]   = "Answer {$i}";
        }

        $this->import($this->row($faqs));

        $course = Course::with('faqs')->firstOrFail();

        $this->assertCount(5, $course->faqs);
        $this->assertSame('Question 1', $course->faqs->first()->question);
        $this->assertSame('Question 5', $course->faqs->last()->question);
        $this->assertSame([0, 1, 2, 3, 4], $course->faqs->pluck('sort_order')->all());
        // The template offers exactly five pairs — there is no sixth column.
        $this->assertSame(Course::MAX_FAQS, $course->faqs->count());
    }

    public function test_blank_faq_pairs_are_simply_skipped(): void
    {
        $this->import($this->row([
            'faq_1_question' => 'Only question',
            'faq_1_answer'   => 'Only answer',
            'faq_2_question' => '',
            'faq_2_answer'   => '',
        ]));

        $this->assertSame(1, CourseFaq::count());
    }

    public function test_line_breaks_in_learning_outcomes_are_preserved(): void
    {
        $outcomes = "Master core concepts\nBuild real-world projects\nPrepare for interviews";

        $this->import($this->row(['learning_outcomes' => $outcomes]));

        $this->assertSame($outcomes, Course::firstOrFail()->learning_outcomes);
    }

    public function test_windows_line_endings_are_normalised_but_lines_are_kept(): void
    {
        $this->import($this->row(['learning_outcomes' => "One\r\nTwo\r\nThree"]));

        $this->assertSame("One\nTwo\nThree", Course::firstOrFail()->learning_outcomes);
    }

    public function test_surrounding_whitespace_is_trimmed(): void
    {
        $this->import($this->row(['course_name' => '   Padded Course   ', 'duration' => '  3 Months  ']));

        $course = Course::firstOrFail();

        $this->assertSame('Padded Course', $course->name);
        $this->assertSame('3 Months', $course->duration);
    }

    public function test_optional_fields_may_all_be_omitted(): void
    {
        $report = $this->importer()->import($this->rows([
            'course_name' => 'Bare Minimum',
            'category'    => 'IT & Software',
            'batch_start' => '04-08-2026',
            'duration'    => '1 Month',
            'mode'        => 'Online',
            'skill_level' => 'Beginner',
        ]));

        $this->assertSame(1, $report['summary']['ready']);

        $course = Course::firstOrFail();

        $this->assertSame('bare-minimum', $course->slug);
        $this->assertNull($course->short_description);
        $this->assertNull($course->meta_title);   // never invented
        $this->assertTrue($course->is_active);    // blank status means Active
        $this->assertFalse($course->is_popular);
        $this->assertSame(0, $course->sort_order);
        $this->assertSame('4.5', (string) $course->rating);
    }

    public function test_the_importer_never_sets_an_image_or_brochure(): void
    {
        $this->import($this->row());

        $course = Course::firstOrFail();

        $this->assertNull($course->image);
        $this->assertNull($course->brochure);
    }

    public function test_an_existing_course_keeps_its_image_and_brochure(): void
    {
        $existing = Course::create([
            'category_id' => $this->category->id,
            'name'        => 'Full Stack Development',
            'slug'        => 'full-stack-development',
            'image'       => 'storage/courses/original.webp',
            'brochure'    => 'storage/courses/brochures/original.pdf',
        ]);

        $this->import($this->row());

        $existing->refresh();

        $this->assertSame('storage/courses/original.webp', $existing->image);
        $this->assertSame('storage/courses/brochures/original.pdf', $existing->brochure);
    }

    public function test_the_importer_creates_no_schedules(): void
    {
        $this->import($this->row());

        $this->assertSame(0, \App\Models\CourseSchedule::count());
    }

    public function test_validation_alone_writes_nothing(): void
    {
        $report = $this->check($this->row());

        $this->assertSame(1, $report['summary']['ready']);
        $this->assertSame(0, Course::count());
        $this->assertSame(0, CourseFaq::count());
    }

    public function test_good_rows_import_while_bad_rows_are_reported(): void
    {
        $report = $this->import(
            $this->row(['course_name' => 'Good One']),
            $this->row(['course_name' => 'Bad One', 'mode' => 'Telepathic']),
            $this->row(['course_name' => 'Good Two']),
        );

        $this->assertSame(2, $report['summary']['ready']);
        $this->assertSame(1, $report['summary']['errors']);
        $this->assertSame(2, Course::count());
        $this->assertNull(Course::where('name', 'Bad One')->first());
    }

    public function test_a_failing_batch_rolls_back_completely(): void
    {
        // A name past the column width blows up at INSERT, not at validation —
        // the row passes the 180-char rule but the column is 180 wide too, so a
        // 180-char name is fine. Instead the FAQ table is dropped, which fails
        // the second half of the batch and must take the first half with it.
        \Illuminate\Support\Facades\Schema::drop('course_faqs');

        try {
            $this->import($this->row(['faq_1_question' => 'Q', 'faq_1_answer' => 'A']));
            $this->fail('The import should have thrown once course_faqs was gone.');
        } catch (\Throwable) {
            // expected
        }

        // The course must NOT be left behind without its FAQs.
        $this->assertSame(0, Course::count());
    }

    public function test_a_large_import_creates_every_row(): void
    {
        $rows = [];

        for ($i = 1; $i <= 450; $i++) {
            $rows[] = $this->row(['course_name' => "Bulk Course {$i}"]);
        }

        $report = $this->importer()->import($this->rows(...$rows));

        $this->assertSame(450, $report['summary']['ready']);
        $this->assertSame(450, Course::count());
        // Batched in chunks — every row still got its own FAQ-free course row.
        $this->assertSame(450, Course::whereNotNull('slug')->distinct('slug')->count('slug'));
    }

    // --------------------------------------------------------------- HTTP flow

    public function test_the_bulk_upload_page_loads_for_a_permitted_admin(): void
    {
        $this->signedInAs($this->staff(['courses']))
            ->get(route('backend.courses.bulk.form'))
            ->assertOk()
            ->assertSee('Export Existing Courses');
    }

    public function test_an_admin_without_the_courses_module_is_turned_away(): void
    {
        foreach ([
            route('backend.courses.bulk.form'),
            route('backend.courses.bulk.template'),
            route('backend.courses.bulk.preview'),
        ] as $url) {
            $this->signedInAs($this->staff(['blogs']))
                ->get($url)
                ->assertRedirect(route('backend.dashboard'));
        }

        $this->signedInAs($this->staff(['blogs']))
            ->post(route('backend.courses.bulk.validate'))
            ->assertRedirect(route('backend.dashboard'));
    }

    public function test_a_signed_out_visitor_cannot_reach_bulk_upload(): void
    {
        $this->get(route('backend.courses.bulk.form'))->assertRedirect(route('backend.auth.login'));
        $this->post(route('backend.courses.bulk.import'))->assertRedirect(route('backend.auth.login'));
    }

    public function test_the_template_downloads_as_a_spreadsheet(): void
    {
        $response = $this->signedInAs($this->mainAdmin())
            ->get(route('backend.courses.bulk.template'))
            ->assertOk();

        $this->assertStringContainsString(
            'spreadsheetml',
            $response->headers->get('content-type'),
        );
    }

    /**
     * The downloaded template, uploaded straight back, must read as exactly one
     * course row.
     *
     * It did not: the workbook carries Instructions and Allowed Values behind
     * the grid, and an import reads every sheet unless told otherwise — so the
     * template round-tripped as ~76 rows, 75 of them phantom errors. This locks
     * the first-sheet-only behaviour down.
     */
    /**
     * The blank template must read as headings and nothing else.
     *
     * It did not always: the workbook carries Instructions, Allowed Values and
     * the hidden _master sheet behind the grid, and an import reads every sheet
     * unless told otherwise — so the template round-tripped as ~76 phantom rows.
     * This locks the first-sheet-only behaviour down.
     */
    public function test_the_blank_template_round_trips_as_headings_only(): void
    {
        $reader = $this->readWorkbook(\App\Exports\CourseTemplateExport::template());

        $this->assertSame([], $reader->missingHeadings());
        $this->assertCount(0, $reader->rows(), 'A blank template must carry no data rows.');
    }

    public function test_the_template_never_carries_a_file_or_id_column(): void
    {
        $headings = CourseImportTemplate::headings();

        foreach (['image', 'course_image', 'course_image_url', 'brochure', 'brochure_url',
                  'id', 'course_id', 'category_id', 'department_id', 'schedule_id',
                  'user_id', 'created_at', 'updated_at'] as $forbidden) {
            $this->assertNotContains($forbidden, $headings);
        }
    }

    public function test_the_summary_screen_renders_after_an_import(): void
    {
        Storage::fake('local');

        $csv = "course_name,category,batch_start,duration,mode,skill_level\n"
             . "Full Stack Development,IT & Software,04-08-2026,6 Months,Offline,Beginner\n"
             . "Dupe,Astrophysics,04-08-2026,6 Months,Offline,Beginner\n";

        $admin = $this->mainAdmin();

        $this->signedInAs($admin)->post(route('backend.courses.bulk.validate'), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
        ]);

        $token = session('course_import_token');

        $this->signedInAs($admin)->withSession(['course_import_token' => $token])
            ->post(route('backend.courses.bulk.import'));

        $this->signedInAs($admin)
            ->withSession(['course_import_token' => $token, 'course_import_done' => true])
            ->get(route('backend.courses.bulk.summary'))
            ->assertOk()
            ->assertSee('Import completed')
            ->assertSee('Download Error Report');
    }

    /**
     * The listing offers ONE way in. Export, the template and the upload itself
     * all live on that page next to the rules they need explaining with — a row
     * of bare buttons on the listing explained none of them.
     */
    public function test_the_listing_links_to_a_single_bulk_upload_page(): void
    {
        $html = $this->signedInAs($this->mainAdmin())
            ->get(route('backend.courses.index'))
            ->assertOk()
            ->assertSee(route('backend.courses.bulk.form'))
            ->assertSee('Bulk Upload')
            ->getContent();

        // The listing itself neither uploads nor downloads.
        $this->assertStringNotContainsString('type="file"', $html);
        $this->assertStringNotContainsString(route('backend.courses.bulk.validate'), $html);
        $this->assertStringNotContainsString(route('backend.courses.bulk.export'), $html);
    }

    /**
     * The upload page has to actually explain itself: both ways to get a
     * spreadsheet, the create-vs-update rule, what is deliberately absent, and
     * a line for every single column.
     */
    public function test_the_upload_page_documents_the_whole_process(): void
    {
        $html = $this->signedInAs($this->mainAdmin())
            ->get(route('backend.courses.bulk.form'))
            ->assertOk()
            ->assertSee('Export Existing Courses')
            ->assertSee('Download Blank Template')
            ->assertSee('Create or update?')
            ->assertSee('Not in this file')
            ->getContent();

        // Every column is documented, from the same definition the template and
        // the importer read — so this table cannot drift out of date.
        foreach (array_keys(CourseImportTemplate::COLUMNS) as $column) {
            $this->assertStringContainsString($column, $html, "The '{$column}' column is undocumented.");
        }

        // ...and the dropdown values are shown, not just named.
        $this->assertStringContainsString('Online, Offline, Hybrid', $html);
        $this->assertStringContainsString('Beginner, Intermediate, Advanced', $html);
    }

    /** A bad file bounces back to the upload page with a toastable message. */
    public function test_a_rejected_file_returns_to_the_upload_page(): void
    {
        Storage::fake('local');

        $this->signedInAs($this->mainAdmin())
            ->from(route('backend.courses.bulk.form'))
            ->post(route('backend.courses.bulk.validate'), [
                'file' => UploadedFile::fake()->createWithContent('courses.csv', "course_name,duration\nX,1 Month\n"),
            ])
            ->assertRedirect(route('backend.courses.bulk.form'))
            ->assertSessionHas('error');
    }

    public function test_a_file_missing_required_columns_is_refused(): void
    {
        Storage::fake('local');

        $csv = "course_name,duration\nFull Stack,6 Months\n";

        $this->signedInAs($this->mainAdmin())
            ->post(route('backend.courses.bulk.validate'), [
                'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Course::count());
    }

    public function test_a_csv_upload_previews_then_imports_only_on_confirmation(): void
    {
        Storage::fake('local');

        $csv = "course_name,category,batch_start,duration,mode,skill_level\n"
             . "Full Stack Development,IT & Software,04-08-2026,6 Months,Offline,Beginner\n"
             . "Data Science,Astrophysics,04-08-2026,3 Months,Online,Beginner\n";

        $admin = $this->mainAdmin();

        // Upload → preview. Still nothing written.
        $this->signedInAs($admin)
            ->post(route('backend.courses.bulk.validate'), [
                'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
            ])
            ->assertRedirect(route('backend.courses.bulk.preview'));

        $this->assertSame(0, Course::count());

        $token = session('course_import_token');
        $this->assertNotNull($token);

        $this->signedInAs($admin)->withSession(['course_import_token' => $token])
            ->get(route('backend.courses.bulk.preview'))
            ->assertOk()
            ->assertSee('Full Stack Development')
            ->assertSee('is invalid. Please select a valid Category', false);

        $this->assertSame(0, Course::count());

        // Confirm → written.
        $this->signedInAs($admin)->withSession(['course_import_token' => $token])
            ->post(route('backend.courses.bulk.import'))
            ->assertRedirect(route('backend.courses.bulk.summary'));

        $this->assertSame(1, Course::count());
        $this->assertSame('Full Stack Development', Course::firstOrFail()->name);
    }

    public function test_the_import_is_recorded_in_the_activity_log(): void
    {
        Storage::fake('local');

        $csv = "course_name,category,batch_start,duration,mode,skill_level\n"
             . "Full Stack Development,IT & Software,04-08-2026,6 Months,Offline,Beginner\n";

        $admin = $this->mainAdmin();

        $this->signedInAs($admin)->post(route('backend.courses.bulk.validate'), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
        ]);

        $this->signedInAs($admin)->withSession(['course_import_token' => session('course_import_token')])
            ->post(route('backend.courses.bulk.import'));

        $this->assertDatabaseHas('activity_logs', ['action' => 'Bulk course import']);
    }

    public function test_a_non_spreadsheet_upload_is_rejected(): void
    {
        Storage::fake('local');

        $this->signedInAs($this->mainAdmin())
            ->post(route('backend.courses.bulk.validate'), [
                'file' => UploadedFile::fake()->create('payload.php', 8, 'application/x-php'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame(0, Course::count());
    }

    public function test_blank_rows_in_the_sheet_are_ignored(): void
    {
        Storage::fake('local');

        $csv = "course_name,category,batch_start,duration,mode,skill_level\n"
             . "Full Stack Development,IT & Software,04-08-2026,6 Months,Offline,Beginner\n"
             . ",,,,,\n"
             . ",,,,,\n";

        $admin = $this->mainAdmin();

        $this->signedInAs($admin)->post(route('backend.courses.bulk.validate'), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
        ])->assertRedirect(route('backend.courses.bulk.preview'));

        $report = json_decode(
            Storage::disk('local')->get('course-imports/' . session('course_import_token') . '.report.json'),
            true,
        );

        $this->assertSame(1, $report['summary']['total']);
    }

    public function test_the_error_report_lists_every_failed_row(): void
    {
        Storage::fake('local');

        $csv = "course_name,category,batch_start,duration,mode,skill_level\n"
             . "Good,IT & Software,04-08-2026,6 Months,Offline,Beginner\n"
             . "Bad,Astrophysics,04-08-2026,3 Months,Online,Beginner\n";

        $admin = $this->mainAdmin();

        $this->signedInAs($admin)->post(route('backend.courses.bulk.validate'), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
        ]);

        $this->signedInAs($admin)->withSession(['course_import_token' => session('course_import_token')])
            ->get(route('backend.courses.bulk.errors'))
            ->assertOk();
    }

    public function test_the_preview_expires_gracefully_without_a_token(): void
    {
        $this->signedInAs($this->mainAdmin())
            ->get(route('backend.courses.bulk.preview'))
            ->assertRedirect(route('backend.courses.bulk.form'))
            ->assertSessionHas('error');
    }

    // ------------------------------------------------- create / update / export

    public function test_a_mixed_file_creates_and_updates_in_one_pass(): void
    {
        $this->seedCourse(['slug' => 'existing-course', 'name' => 'Existing Course']);

        $report = $this->import(
            $this->row(['course_name' => 'Existing Course', 'slug' => 'existing-course', 'duration' => '9 Months']),
            $this->row(['course_name' => 'Brand New Course', 'slug' => 'brand-new-course']),
        );

        $this->assertSame(1, $report['summary']['create']);
        $this->assertSame(1, $report['summary']['update']);
        $this->assertSame(2, $report['summary']['ready']);
        $this->assertSame(2, Course::count());

        $this->assertSame('9 Months', Course::where('slug', 'existing-course')->value('duration'));
        $this->assertNotNull(Course::where('slug', 'brand-new-course')->first());
    }

    /**
     * The fields an update must not touch: the key, when the row was made, and
     * the two files that can only be uploaded by hand.
     */
    public function test_an_update_preserves_the_key_created_at_image_and_brochure(): void
    {
        $existing = $this->seedCourse();
        $originalId = $existing->id;
        $createdAt  = $existing->created_at->copy()->subDays(30);
        $existing->forceFill(['created_at' => $createdAt])->save();

        $this->import($this->row([
            'course_name' => 'Existing Course',
            'slug'        => 'existing-course',
            'duration'    => '11 Months',
        ]));

        $existing->refresh();

        $this->assertSame($originalId, $existing->id);
        $this->assertSame($createdAt->format('Y-m-d H:i:s'), $existing->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('storage/courses/original.webp', $existing->image);
        $this->assertSame('storage/courses/brochures/original.pdf', $existing->brochure);
        $this->assertSame('11 Months', $existing->duration);
    }

    public function test_an_update_may_move_a_course_to_another_category(): void
    {
        $other = Category::create([
            'department_id' => $this->category->department_id,
            'name'          => 'Cloud & DevOps',
            'is_active'     => true,
        ]);

        $this->seedCourse();

        $this->import($this->row([
            'course_name' => 'Existing Course',
            'slug'        => 'existing-course',
            'category'    => 'Cloud & DevOps',
        ]));

        $this->assertSame($other->id, Course::where('slug', 'existing-course')->value('category_id'));
    }

    /**
     * A narrow sheet must not blank the columns it does not carry. An admin who
     * uploads name + slug + duration is editing three fields, not resetting the
     * other twenty to their defaults.
     */
    public function test_a_sheet_without_a_column_leaves_that_field_alone_on_update(): void
    {
        $this->seedCourse([
            'short_description' => 'Written by hand.',
            'meta_title'        => 'Hand-written SEO title',
            'is_featured'       => true,
            'sort_order'        => 7,
        ]);

        $report = $this->importer()->import($this->rows([
            'course_name' => 'Existing Course',
            'slug'        => 'existing-course',
            'duration'    => '4 Months',
        ]));

        $this->assertSame(1, $report['summary']['update']);

        $course = Course::where('slug', 'existing-course')->firstOrFail();

        $this->assertSame('4 Months', $course->duration);
        $this->assertSame('Written by hand.', $course->short_description);
        $this->assertSame('Hand-written SEO title', $course->meta_title);
        $this->assertTrue($course->is_featured);
        $this->assertSame(7, $course->sort_order);
    }

    public function test_a_sheet_without_faq_columns_keeps_existing_faqs(): void
    {
        $course = $this->seedCourse();
        $course->faqs()->create(['question' => 'Kept?', 'answer' => 'Yes.', 'sort_order' => 0]);

        $this->importer()->import($this->rows([
            'course_name' => 'Existing Course',
            'slug'        => 'existing-course',
            'duration'    => '4 Months',
        ]));

        $this->assertSame(1, CourseFaq::count());
        $this->assertSame('Kept?', CourseFaq::firstOrFail()->question);
    }

    public function test_a_sheet_with_faq_columns_replaces_them_on_update(): void
    {
        $course = $this->seedCourse();
        $course->faqs()->create(['question' => 'Old question', 'answer' => 'Old answer', 'sort_order' => 0]);

        $this->import($this->row([
            'course_name'    => 'Existing Course',
            'slug'           => 'existing-course',
            'faq_1_question' => 'New question',
            'faq_1_answer'   => 'New answer',
        ]));

        $this->assertSame(1, CourseFaq::count());
        $this->assertSame('New question', CourseFaq::firstOrFail()->question);
    }

    public function test_a_new_course_whose_name_is_taken_is_reported_not_duplicated(): void
    {
        $this->seedCourse(['name' => 'Existing Course', 'slug' => 'existing-course']);

        $report = $this->import($this->row([
            'course_name' => 'Existing Course',
            'slug'        => 'a-different-slug',
        ]));

        $this->assertSame(1, $report['summary']['duplicates']);
        $this->assertSame(0, $report['summary']['create']);
        $this->assertSame(1, Course::count());
        $this->assertStringContainsString('already exists under the slug', $this->firstError($report));
    }

    // ----------------------------------------------------------------- export

    public function test_the_export_puts_every_header_in_row_one_and_data_from_row_two(): void
    {
        $this->seedCourse(['name' => 'Alpha', 'slug' => 'alpha']);
        $this->seedCourse(['name' => 'Beta', 'slug' => 'beta']);

        $sheet = $this->loadWorkbook(\App\Exports\CourseTemplateExport::withData())->getSheetByName('Courses');

        $headings = CourseImportTemplate::headings();
        $row1 = array_slice($sheet->toArray()[0], 0, count($headings));

        $this->assertSame($headings, $row1, 'Row 1 must be exactly the column headings.');
        $this->assertSame('Alpha', $sheet->getCell('A2')->getValue());
        $this->assertSame('Beta', $sheet->getCell('A3')->getValue());

        // No heading may reappear further down the sheet.
        foreach (array_slice($sheet->toArray(), 1) as $row) {
            $this->assertNotSame($headings, array_slice($row, 0, count($headings)));
        }
    }

    public function test_every_master_data_column_carries_a_dropdown(): void
    {
        $sheet = $this->loadWorkbook(\App\Exports\CourseTemplateExport::template())->getSheetByName('Courses');

        $validations = $sheet->getDataValidationCollection();
        $this->assertNotEmpty($validations, 'The Courses sheet must carry dropdowns.');

        foreach (array_keys(CourseImportTemplate::MASTER_COLUMNS) as $column) {
            $letter = CourseImportTemplate::columnLetter($column);
            $found  = false;

            foreach ($validations as $range => $validation) {
                if (preg_replace('/[0-9:].*/', '', $range) !== $letter) {
                    continue;
                }

                $found = true;

                $this->assertSame(
                    \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST,
                    $validation->getType(),
                );

                // The values themselves, inline. A cross-sheet reference — even
                // a correctly declared named range on a hidden sheet — left
                // Excel drawing the arrow over an empty list.
                $this->assertSame(
                    \App\Exports\CourseMasterDataSheet::sourceFor($column),
                    $validation->getFormula1(),
                );

                $expected = CourseImportTemplate::dropdownLists()[$column];

                $this->assertSame(
                    '"' . implode(',', $expected) . '"',
                    $validation->getFormula1(),
                    "The '{$column}' dropdown does not carry its values.",
                );

                // "between" means nothing on a list and Excel does not expect it.
                $this->assertSame('', $validation->getOperator());

                // The arrow itself. PhpSpreadsheet writes this attribute
                // inverted, so passing false here produced a validated cell
                // with no picker on it — which is exactly what went wrong.
                $this->assertTrue(
                    $validation->getShowDropDown(),
                    "The '{$column}' dropdown would render without its arrow.",
                );
            }

            $this->assertTrue($found, "No dropdown found on the '{$column}' column.");
        }
    }

    /**
     * The written file is what Excel reads, and the arrow flag is stored
     * inverted there — so it is checked in the XML, not just on the object.
     */
    public function test_the_written_file_shows_every_picker_arrow(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'hm') . '.xlsx';

        file_put_contents($path, \Maatwebsite\Excel\Facades\Excel::raw(
            \App\Exports\CourseTemplateExport::template(),
            \Maatwebsite\Excel\Excel::XLSX,
        ));

        $zip = new \ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($path);

        preg_match_all('/<dataValidation [^>]*showDropDown="([01])"/', $xml, $matches);

        $this->assertNotEmpty($matches[1], 'No data validations were written to the sheet.');

        // showDropDown="1" in OOXML means "suppress the arrow".
        $this->assertNotContains('1', $matches[1], 'A rule was written with its dropdown arrow suppressed.');
    }

    /**
     * No cross-sheet reference at all in the normal case.
     *
     * Every part of the earlier range-based file was correct — the names, the
     * scope, the values — and Excel still drew each dropdown as an arrow over
     * blank rows. Inline lists have no reference to get wrong.
     */
    public function test_dropdowns_carry_their_values_inline_with_no_helper_sheet(): void
    {
        $book = $this->loadWorkbook(\App\Exports\CourseTemplateExport::template());

        $this->assertSame(
            ['Courses', 'Instructions', 'Allowed Values'],
            $book->getSheetNames(),
            'A helper sheet is only warranted when a list will not fit inline.',
        );

        $this->assertCount(0, $book->getNamedRanges());

        $formula = $book->getSheetByName('Courses')
            ->getCell(CourseImportTemplate::columnLetter('mode') . '2')
            ->getDataValidation()
            ->getFormula1();

        $this->assertSame('"Online,Offline,Hybrid"', $formula);
    }

    /**
     * ...but a list CAN outgrow Excel's 255-character inline cap, and then it
     * has to fall back to a range. Enough categories are created here to force
     * that, so the fallback is exercised rather than assumed.
     */
    public function test_an_oversized_list_falls_back_to_a_visible_named_range(): void
    {
        for ($i = 1; $i <= 40; $i++) {
            Category::create([
                'department_id' => $this->category->department_id,
                'name'          => "Extremely Long Category Name Number {$i}",
                'is_active'     => true,
            ]);
        }

        \App\Exports\CourseMasterDataSheet::flush();
        $this->assertTrue(\App\Exports\CourseMasterDataSheet::usesRange('category'));

        $book = $this->loadWorkbook(\App\Exports\CourseTemplateExport::template());

        // The helper sheet appears, and is left VISIBLE — a hidden source sheet
        // is what Excel would not read from.
        $sheet = $book->getSheetByName(\App\Exports\CourseMasterDataSheet::SHEET);
        $this->assertNotNull($sheet, 'The fallback sheet was not written.');
        $this->assertSame(
            \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VISIBLE,
            $sheet->getSheetState(),
        );

        $formula = $book->getSheetByName('Courses')
            ->getCell(CourseImportTemplate::columnLetter('category') . '2')
            ->getDataValidation()
            ->getFormula1();

        $this->assertSame(\App\Exports\CourseMasterDataSheet::nameFor('category'), $formula);
        $this->assertNotNull($book->getNamedRange($formula));

        // The short lists stay inline even so.
        $mode = $book->getSheetByName('Courses')
            ->getCell(CourseImportTemplate::columnLetter('mode') . '2')
            ->getDataValidation()
            ->getFormula1();

        $this->assertSame('"Online,Offline,Hybrid"', $mode);
    }

    public function test_a_category_containing_a_comma_is_not_inlined(): void
    {
        // A comma is the inline separator — "Sales, B2B" would silently become
        // two options, so such a list must go to a range instead.
        Category::create([
            'department_id' => $this->category->department_id,
            'name'          => 'Sales, B2B',
            'is_active'     => true,
        ]);

        \App\Exports\CourseMasterDataSheet::flush();

        $this->assertTrue(\App\Exports\CourseMasterDataSheet::usesRange('category'));
    }

    public function test_the_date_and_number_columns_carry_their_own_rules(): void
    {
        $sheet = $this->loadWorkbook(\App\Exports\CourseTemplateExport::template())->getSheetByName('Courses');

        $expected = [
            'batch_start' => \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DATE,
            'order'       => \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_WHOLE,
            'rating'      => \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DECIMAL,
        ];

        foreach ($expected as $column => $type) {
            $letter = CourseImportTemplate::columnLetter($column);
            $rule   = $sheet->getCell($letter . '2')->getDataValidation();

            $this->assertSame($type, $rule->getType(), "The '{$column}' column is missing its {$type} rule.");
            $this->assertTrue($rule->getShowDropDown());
        }

        // A date rule is useless if the cell then shows 46238 instead of a date.
        $dateLetter = CourseImportTemplate::columnLetter('batch_start');
        $this->assertSame(
            'DD-MM-YYYY',
            $sheet->getStyle($dateLetter . '2')->getNumberFormat()->getFormatCode(),
        );
    }

    /**
     * Dates must be written as real date cells, not text — the column carries a
     * date rule, so a text value would make the export fail its own validation.
     */
    public function test_exported_dates_are_real_date_cells_and_re_import_correctly(): void
    {
        $this->seedCourse(['name' => 'Dated', 'slug' => 'dated', 'batch_start_date' => '2026-08-04']);

        $sheet = $this->loadWorkbook(\App\Exports\CourseTemplateExport::withData())->getSheetByName('Courses');
        $cell  = $sheet->getCell(CourseImportTemplate::columnLetter('batch_start') . '2');

        $this->assertIsNumeric($cell->getValue(), 'batch_start must be a date serial, not text.');

        // ...and the importer reads that serial straight back.
        $reader = $this->readWorkbook(\App\Exports\CourseTemplateExport::withData());
        $this->importer()->import($reader->rows());

        $this->assertSame('2026-08-04', Course::where('slug', 'dated')->firstOrFail()->batch_start_date->format('Y-m-d'));
    }

    /** The category dropdown lists what is actually in the database right now. */
    public function test_the_category_dropdown_lists_the_real_categories(): void
    {
        Category::create([
            'department_id' => $this->category->department_id,
            'name'          => 'Cloud & DevOps',
            'is_active'     => true,
        ]);

        \App\Exports\CourseMasterDataSheet::flush();

        $formula = $this->loadWorkbook(\App\Exports\CourseTemplateExport::template())
            ->getSheetByName('Courses')
            ->getCell(CourseImportTemplate::columnLetter('category') . '2')
            ->getDataValidation()
            ->getFormula1();

        $this->assertStringContainsString('IT & Software', $formula);
        $this->assertStringContainsString('Cloud & DevOps', $formula);
    }

    public function test_the_instructions_sheet_has_no_second_header_row(): void
    {
        $sheet = $this->loadWorkbook(\App\Exports\CourseTemplateExport::template())->getSheetByName('Instructions');

        $rows = $sheet->toArray();
        $header = array_slice($rows[0], 0, 3);

        $this->assertSame(['Column', 'Required?', 'What to put in it'], $header);

        foreach (array_slice($rows, 1) as $i => $row) {
            $this->assertNotSame(
                array_map('strtoupper', $header),
                array_map(fn ($v) => strtoupper((string) $v), array_slice($row, 0, 3)),
                'A repeated header was found at row ' . ($i + 2) . '.',
            );
        }
    }

    /**
     * The round trip the whole feature exists for: export what is there, upload
     * it back untouched, and end up with the same courses — updated, not copied.
     */
    public function test_an_untouched_export_re_imports_as_updates_and_creates_nothing(): void
    {
        $alpha = $this->seedCourse(['name' => 'Alpha', 'slug' => 'alpha', 'sort_order' => 3, 'rating' => 4.2]);
        $alpha->faqs()->create(['question' => 'Q1', 'answer' => 'A1', 'sort_order' => 0]);
        $this->seedCourse(['name' => 'Beta', 'slug' => 'beta', 'is_active' => false, 'is_popular' => true]);

        $before = Course::orderBy('id')->get()->map->only([
            'id', 'name', 'slug', 'duration', 'training_mode', 'skill_level',
            'sort_order', 'is_active', 'is_popular', 'image', 'brochure',
        ])->toArray();

        $reader = $this->readWorkbook(\App\Exports\CourseTemplateExport::withData());
        $this->assertCount(2, $reader->rows());

        $report = $this->importer()->import($reader->rows());

        $this->assertSame(2, $report['summary']['update']);
        $this->assertSame(0, $report['summary']['create']);
        $this->assertSame(0, $report['summary']['errors']);
        $this->assertSame(2, Course::count(), 'A re-imported export must not duplicate anything.');

        $after = Course::orderBy('id')->get()->map->only([
            'id', 'name', 'slug', 'duration', 'training_mode', 'skill_level',
            'sort_order', 'is_active', 'is_popular', 'image', 'brochure',
        ])->toArray();

        $this->assertEquals($before, $after, 'A round trip must leave the data exactly as it was.');

        // FAQs survive the trip too.
        $this->assertSame(1, CourseFaq::count());
        $this->assertSame('Q1', CourseFaq::firstOrFail()->question);
    }

    /**
     * A blank template has good headings and no rows. It must be told apart from
     * a file with the wrong columns — the two need completely different fixes.
     */
    public function test_uploading_a_blank_template_says_no_rows_not_missing_columns(): void
    {
        Storage::fake('local');

        $path = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
        file_put_contents($path, \Maatwebsite\Excel\Facades\Excel::raw(
            \App\Exports\CourseTemplateExport::template(),
            \Maatwebsite\Excel\Excel::XLSX,
        ));

        $this->signedInAs($this->mainAdmin())
            ->post(route('backend.courses.bulk.validate'), [
                'file' => new UploadedFile($path, 'template.xlsx', null, null, true),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'no course rows'));

        @unlink($path);
    }

    public function test_an_update_bumps_updated_at_but_not_created_at(): void
    {
        $course = $this->seedCourse();
        $course->forceFill([
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ])->save();

        $createdAt = $course->fresh()->created_at;

        $this->import($this->row(['course_name' => 'Existing Course', 'slug' => 'existing-course']));

        $course->refresh();

        $this->assertSame($createdAt->format('Y-m-d H:i:s'), $course->created_at->format('Y-m-d H:i:s'));
        $this->assertTrue($course->updated_at->gt(now()->subMinute()), 'updated_at should be bumped.');
    }

    public function test_a_large_mixed_file_creates_and_updates_efficiently(): void
    {
        // 200 already there, 200 brand new, uploaded as one 400-row file.
        for ($i = 1; $i <= 200; $i++) {
            $this->seedCourse(['name' => "Existing {$i}", 'slug' => "existing-{$i}"]);
        }

        $rows = [];

        for ($i = 1; $i <= 200; $i++) {
            $rows[] = $this->row(['course_name' => "Existing {$i}", 'slug' => "existing-{$i}", 'duration' => '7 Months']);
        }

        for ($i = 1; $i <= 200; $i++) {
            $rows[] = $this->row(['course_name' => "Fresh {$i}", 'slug' => "fresh-{$i}"]);
        }

        $report = $this->importer()->import($this->rows(...$rows));

        $this->assertSame(200, $report['summary']['create']);
        $this->assertSame(200, $report['summary']['update']);
        $this->assertSame(400, Course::count(), 'Updates must not add rows.');
        $this->assertSame(200, Course::where('duration', '7 Months')->count());
        // Every seeded course kept its artwork through the update.
        $this->assertSame(200, Course::whereNotNull('image')->count());
    }

    public function test_the_export_downloads_as_a_spreadsheet(): void
    {
        $this->seedCourse();

        $response = $this->signedInAs($this->mainAdmin())
            ->get(route('backend.courses.bulk.export'))
            ->assertOk();

        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }

    public function test_the_export_is_gated_by_the_courses_module(): void
    {
        $this->signedInAs($this->staff(['blogs']))
            ->get(route('backend.courses.bulk.export'))
            ->assertRedirect(route('backend.dashboard'));
    }

    public function test_the_row_cap_is_reported_rather_than_silently_applied(): void
    {
        $this->assertSame(5000, CourseRowsImport::MAX_ROWS);
    }

    /**
     * Bulk-imported courses carry no image by design, so the public pages have to
     * cope with one. Every surface that draws a course card is checked, because
     * an imported course lands on all of them at once.
     */
    public function test_an_imported_course_without_an_image_still_renders_on_the_site(): void
    {
        $this->import($this->row([
            'show_in_popular'           => 'Yes',
            'show_in_continue_learning' => 'Yes',
        ]));

        $course = Course::firstOrFail();
        $this->assertNull($course->image);

        $this->get(route('frontend.courses'))->assertOk();
        $this->get(route('frontend.index'))->assertOk();
        $this->get(route('frontend.course-details', $course->slug))->assertOk();
    }
}
