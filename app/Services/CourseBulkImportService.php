<?php

namespace App\Services;

use App\Http\Requests\Backend\CourseRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseFaq;
use App\Support\CourseImportTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Turns a spreadsheet of courses into courses — creating the ones that are new
 * and updating the ones that already exist, exactly as if each row had been
 * typed into Admin → Courses.
 *
 * IDENTITY IS THE SLUG. `courses.slug` carries the table's only unique index, it
 * is human-readable, and it is a column of the sheet, so an admin can see and
 * control it. A row whose slug already exists UPDATES that course; anything else
 * CREATES one. A blank slug is generated from the course name, the same way the
 * manual form does it, which is what makes a hand-written sheet work at all.
 *
 * Two entry points, and the split matters:
 *
 *   validateRows()  reads the rows and reports on them. Touches nothing.
 *   import()        re-checks every row, then writes.
 *
 * The second check is on purpose. The preview an admin is looking at may be
 * minutes old and another admin may have created a clashing course meanwhile, so
 * the decision to write is never taken from data carried through a browser.
 *
 * What an update never touches: the primary key, created_at, the course image
 * and the brochure. Those are not columns of the sheet and are excluded from the
 * update set, so bulk-editing a course cannot wipe artwork uploaded by hand.
 */
class CourseBulkImportService
{
    /** Rows written per batch. Keeps the statement under MySQL's packet size. */
    private const BATCH_SIZE = 200;

    /** What will happen to a row. */
    public const CREATE    = 'create';
    public const UPDATE    = 'update';
    public const ERROR     = 'error';
    public const DUPLICATE = 'duplicate';

    /**
     * Sheet heading => course column. The importer's whole notion of "which
     * field is this?" lives here, and the update set is derived from it, so a
     * column the sheet does not carry is a column an update leaves alone.
     */
    private const FIELD_MAP = [
        'course_name'               => 'name',
        'category'                  => 'category_id',
        'duration'                  => 'duration',
        'skill_level'               => 'skill_level',
        'short_description'         => 'short_description',
        'full_description'          => 'full_description',
        'course_overview'           => 'overview',
        'learning_outcomes'         => 'learning_outcomes',
        'prerequisites'             => 'prerequisites',
        'certification_details'     => 'certification',
        'audience'                  => 'audience',
        'status'                    => 'is_active',
        'order'                     => 'sort_order',
        'rating'                    => 'rating',
        'show_in_popular'           => 'is_popular',
        'show_in_continue_learning' => 'is_continue_learning',
        'featured'                  => 'is_featured',
        'meta_title'                => 'meta_title',
        'meta_keywords'             => 'meta_keywords',
        'meta_description'          => 'meta_description',
    ];

    /** name (folded) => category id. Built once per run, not per row. */
    private array $categoriesByName = [];

    /** slug => category id, so a slug may be pasted in instead of a name. */
    private array $categoriesBySlug = [];

    /** Names more than one category answers to — ambiguous, so refused. */
    private array $ambiguousCategories = [];

    /** slug => ['id' => int, 'name' => string] for every existing course. */
    private array $existingBySlug = [];

    /** folded name => slug, to catch a new course colliding with an old one. */
    private array $existingNames = [];

    /** Within-file claims, so two rows cannot take the same identity. */
    private array $claimedSlugs = [];

    private array $claimedNames = [];

    /** The headings this particular sheet carries. */
    private array $sheetColumns = [];

    /**
     * Check a sheet without writing anything.
     *
     * @param  Collection<int, array{line:int, data:array}>  $rows
     * @return array{summary: array, rows: array}
     */
    public function validateRows(Collection $rows): array
    {
        $this->primeLookups();
        $this->sheetColumns = $rows->isEmpty() ? [] : array_keys($rows->first()['data']);

        $checked = [];

        foreach ($rows as $row) {
            $checked[] = $this->check($row['data'], $row['line']);
        }

        return [
            'summary' => $this->summarise($checked),
            'rows'    => $checked,
        ];
    }

    /**
     * Write the rows that pass — creating and updating as each row requires.
     *
     * @param  Collection<int, array{line:int, data:array}>  $rows
     * @return array{summary: array, rows: array}
     */
    public function import(Collection $rows): array
    {
        $checked = $this->validateRows($rows)['rows'];

        $writable = array_values(array_filter(
            $checked,
            fn ($r) => in_array($r['status'], [self::CREATE, self::UPDATE], true),
        ));

        foreach (array_chunk($writable, self::BATCH_SIZE) as $batch) {
            $this->writeBatch($batch);
        }

        return [
            'summary' => $this->summarise($checked),
            'rows'    => $checked,
        ];
    }

    /**
     * Write one batch inside a transaction.
     *
     * Creates and updates go together in a single upsert. `slug` is the unique
     * key, so a row whose slug exists updates that row in place — the id and
     * created_at survive because neither is in the update set, and image and
     * brochure survive because they are in neither the values nor the update set.
     *
     * Three statements per 200 courses, whatever the create/update mix, instead
     * of a save() per row. The FAQ replacement is two more, and only runs for
     * sheets that carry FAQ columns.
     */
    private function writeBatch(array $batch): void
    {
        DB::transaction(function () use ($batch) {
            $now = Carbon::now();

            $values = array_map(
                fn (array $row) => $row['attributes'] + ['created_at' => $now, 'updated_at' => $now],
                $batch,
            );

            Course::upsert($values, ['slug'], $this->updatableColumns());

            // upsert hands back no ids, and the FAQs need them. slug is unique,
            // so this maps them exactly.
            $slugs = array_column($values, 'slug');
            $ids   = Course::whereIn('slug', $slugs)->pluck('id', 'slug');

            if (! $this->sheetCarriesFaqs()) {
                // No FAQ columns in this sheet: existing questions are not the
                // admin's to lose just because they uploaded a narrower file.
                return;
            }

            // Replacing the set matches what the manual form does on save.
            $touched = array_values(array_filter(array_map(
                fn (array $row) => $ids[$row['attributes']['slug']] ?? null,
                $batch,
            )));

            if ($touched) {
                CourseFaq::whereIn('course_id', $touched)->delete();
            }

            $faqRows = [];

            foreach ($batch as $row) {
                $courseId = $ids[$row['attributes']['slug']] ?? null;

                if (! $courseId) {
                    continue;
                }

                foreach ($row['faqs'] as $order => $faq) {
                    $faqRows[] = [
                        'course_id'  => $courseId,
                        'question'   => $faq['question'],
                        'answer'     => $faq['answer'],
                        'sort_order' => $order,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if ($faqRows) {
                CourseFaq::insert($faqRows);
            }
        });
    }

    /**
     * The columns an update is allowed to change.
     *
     * Only fields whose column is actually in the uploaded sheet. Upload a file
     * with just course_name and category and that is all an update touches —
     * every other field keeps the value it already had, rather than being reset
     * to a default the admin never typed.
     *
     * slug is excluded because it is the identity being matched on, and
     * created_at because an update is not a creation.
     */
    private function updatableColumns(): array
    {
        $columns = [];

        foreach (self::FIELD_MAP as $heading => $attribute) {
            if (in_array($heading, $this->sheetColumns, true)) {
                $columns[] = $attribute;
            }
        }

        $columns[] = 'updated_at';

        return array_values(array_unique($columns));
    }

    /** Does this sheet carry the FAQ columns at all? */
    private function sheetCarriesFaqs(): bool
    {
        for ($i = 1; $i <= Course::MAX_FAQS; $i++) {
            if (in_array("faq_{$i}_question", $this->sheetColumns, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Load every lookup the run needs, up front.
     *
     * Two queries for the whole file. Asking for the category on each row is one
     * query per row, which is what makes a 1,000-row import crawl.
     */
    private function primeLookups(): void
    {
        $this->categoriesByName = [];
        $this->categoriesBySlug = [];
        $this->ambiguousCategories = [];
        $this->claimedSlugs = [];
        $this->claimedNames = [];

        foreach (Category::query()->get(['id', 'name', 'slug']) as $category) {
            $key = $this->fold($category->name);

            // Category names are not unique in the schema (only slugs are). If
            // two share a name, guessing which one the admin meant would file
            // courses under the wrong department, so the row is refused instead.
            if (array_key_exists($key, $this->categoriesByName)) {
                $this->ambiguousCategories[$key] = true;
            }

            $this->categoriesByName[$key] = $category->id;
            $this->categoriesBySlug[$this->fold($category->slug)] = $category->id;
        }

        $this->existingBySlug = [];
        $this->existingNames = [];

        // category_id rides along so an update whose sheet omits the category
        // column can keep the one it has without a lookup per row.
        foreach (Course::query()->get(['id', 'slug', 'name', 'category_id']) as $course) {
            $this->existingBySlug[$course->slug] = [
                'id'          => $course->id,
                'name'        => $course->name,
                'category_id' => $course->category_id,
            ];

            $this->existingNames[$this->fold($course->name)] = $course->slug;
        }
    }

    /**
     * Decide what happens to one row.
     *
     * @return array{line:int, status:string, action:string, course_id:?int,
     *               course_name:?string, category:?string, slug:?string,
     *               error_type:?string, errors:array, attributes:array, faqs:array}
     */
    private function check(array $data, int $line): array
    {
        $name     = CourseImportTemplate::toText($data['course_name'] ?? null);
        $category = CourseImportTemplate::toText($data['category'] ?? null);

        $result = [
            'line'        => $line,
            'status'      => self::ERROR,
            'action'      => 'Error',
            'course_id'   => null,
            'course_name' => $name,
            'category'    => $category,
            'slug'        => null,
            'error_type'  => null,
            'errors'      => [],
            'attributes'  => [],
            'faqs'        => [],
        ];

        // ---- Category → id → (implicitly) department -----------------------
        $categoryId = null;

        if ($category === null) {
            // Only an error when the sheet HAS the column; an update sheet that
            // leaves category out is not trying to change it.
            if (in_array('category', $this->sheetColumns, true) || $this->sheetColumns === []) {
                $result['errors'][] = 'Category is required.';
                $result['error_type'] = 'Missing Category';
            }
        } else {
            $key = $this->fold($category);

            if (isset($this->ambiguousCategories[$key])) {
                $result['errors'][] = "Category '{$category}' is ambiguous — more than one category has this name. Use the category slug instead.";
                $result['error_type'] = 'Ambiguous Category';
            } else {
                $categoryId = $this->categoriesByName[$key] ?? $this->categoriesBySlug[$key] ?? null;

                if (! $categoryId) {
                    $result['errors'][] = "Category: \"{$category}\" is invalid. Please select a valid Category from the master data.";
                    $result['error_type'] = 'Unknown Category';
                }
            }
        }

        // ---- Booleans, before validation, so a bad word is named ------------
        $flags = [];

        foreach ([
            'is_active'            => ['status', true],
            'is_popular'           => ['show_in_popular', false],
            'is_continue_learning' => ['show_in_continue_learning', false],
            'is_featured'          => ['featured', false],
        ] as $column => [$heading, $default]) {
            $parsed = CourseImportTemplate::toBool($data[$heading] ?? null, $default);

            if ($parsed === null) {
                $raw = trim((string) ($data[$heading] ?? ''));
                $result['errors'][] = "{$heading}: '{$raw}' is not a Yes/No value.";
                $result['error_type'] ??= 'Invalid Yes/No';
                $parsed = $default;
            }

            $flags[$column] = $parsed;
        }

        // ---- Identity ------------------------------------------------------
        $slug = CourseImportTemplate::toText($data['slug'] ?? null);

        // Blank slug is generated from the name — the same Str::slug($name) the
        // Course model's saving hook applies when the form leaves it blank.
        if ($slug === null && $name !== null) {
            $slug = Str::slug($name);
        }

        $result['slug'] = $slug;
        $existing = $slug === null ? null : ($this->existingBySlug[$slug] ?? null);

        // ---- FAQs ----------------------------------------------------------
        [$faqs, $faqErrors] = $this->readFaqs($data);

        if ($faqErrors) {
            $result['errors'] = array_merge($result['errors'], $faqErrors);
            $result['error_type'] ??= 'Invalid FAQ';
        }

        // ---- The shared column rules, straight off the manual form ----------
        $payload = [
            'name'                 => $name,
            'duration'             => CourseImportTemplate::toText($data['duration'] ?? null),
            'skill_level'          => $this->matchAllowed($data['skill_level'] ?? null, Course::SKILL_LEVELS),
            'rating'               => CourseImportTemplate::isBlank($data['rating'] ?? null) ? null : $data['rating'],
            'short_description'    => CourseImportTemplate::toText($data['short_description'] ?? null),
            'full_description'     => CourseImportTemplate::toText($data['full_description'] ?? null),
            'overview'             => CourseImportTemplate::toText($data['course_overview'] ?? null),
            'learning_outcomes'    => CourseImportTemplate::toText($data['learning_outcomes'] ?? null),
            'prerequisites'        => CourseImportTemplate::toText($data['prerequisites'] ?? null),
            'certification'        => CourseImportTemplate::toText($data['certification_details'] ?? null),
            'audience'             => CourseImportTemplate::toText($data['audience'] ?? null),
            'sort_order'           => CourseImportTemplate::isBlank($data['order'] ?? null) ? 0 : $data['order'],
            'meta_title'           => CourseImportTemplate::toText($data['meta_title'] ?? null),
            'meta_description'     => CourseImportTemplate::toText($data['meta_description'] ?? null),
            'meta_keywords'        => CourseImportTemplate::toText($data['meta_keywords'] ?? null),
            'faqs'                 => $faqs,
        ] + $flags;

        $validator = Validator::make($payload, $this->rulesFor($existing !== null), [
            'name.required'             => 'course_name is required.',
            'duration.required'         => 'duration is required.',
            'skill_level.required'      => 'skill_level is required.',
        ]);

        // The importer's column names, so an error reads like the spreadsheet
        // the admin is looking at rather than like the database.
        $validator->setAttributeNames([
            'name'             => 'course_name',
            'overview'         => 'course_overview',
            'certification'    => 'certification_details',
            'sort_order'       => 'order',
            'is_active'        => 'status',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $result['errors'][] = $this->humanise($message, $data);
            }

            $result['error_type'] ??= 'Invalid Data';
        }

        // Slug shape is checked here rather than in columnRules(), which cannot
        // carry the unique rule (the form needs to exempt the row being edited).
        if ($slug !== null && ! preg_match('/^[A-Za-z0-9_-]+$/', $slug)) {
            $result['errors'][] = "slug: '{$slug}' may only contain letters, numbers, dashes and underscores.";
            $result['error_type'] ??= 'Invalid Slug';
        } elseif ($slug !== null && strlen($slug) > 200) {
            $result['errors'][] = 'slug: may not be longer than 200 characters.';
            $result['error_type'] ??= 'Invalid Slug';
        }

        if ($result['errors']) {
            return $result;
        }

        // ---- Duplicates within this file -----------------------------------
        // Checked only once the row is otherwise sound, so an admin is not told
        // "duplicate" about a row that is broken for a more basic reason.
        $nameKey = $this->fold($name);

        if (isset($this->claimedSlugs[$slug])) {
            $result['status'] = self::DUPLICATE;
            $result['action'] = 'Duplicate';
            $result['error_type'] = 'Duplicate';
            $result['errors'][] = "This file already has a row for \"{$slug}\" (row {$this->claimedSlugs[$slug]}).";

            return $result;
        }

        if (isset($this->claimedNames[$nameKey])) {
            $result['status'] = self::DUPLICATE;
            $result['action'] = 'Duplicate';
            $result['error_type'] = 'Duplicate';
            $result['errors'][] = "Course \"{$name}\" appears more than once in this file (first seen on row {$this->claimedNames[$nameKey]}).";

            return $result;
        }

        // ---- Create or update ----------------------------------------------
        if ($existing) {
            $result['status'] = self::UPDATE;
            $result['action'] = 'Update';
            $result['course_id'] = $existing['id'];
        } else {
            // A brand-new course whose NAME is already taken by a different
            // course is almost always a slug typo — creating it would leave two
            // courses with the same title and no way to tell them apart.
            if (isset($this->existingNames[$nameKey])) {
                $result['status'] = self::DUPLICATE;
                $result['action'] = 'Duplicate';
                $result['error_type'] = 'Duplicate';
                $result['errors'][] = "Course \"{$name}\" already exists under the slug \"{$this->existingNames[$nameKey]}\". "
                    . 'Use that slug to update it, or give this row a different course name.';

                return $result;
            }

            $result['status'] = self::CREATE;
            $result['action'] = 'Create';
        }

        // Claim the identity so a later row in the same file cannot take it.
        $this->claimedSlugs[$slug] = $line;
        $this->claimedNames[$nameKey] = $line;

        $result['faqs'] = $faqs;
        $result['attributes'] = [
            'category_id'          => $categoryId ?? $this->categoryFallback($existing),
            'name'                 => $name,
            'slug'                 => $slug,
            'duration'             => $payload['duration'],
            'skill_level'          => $payload['skill_level'],
            // The column defaults to 4.5, and upsert does not apply model
            // defaults, so a blank cell is filled in explicitly.
            'rating'               => $payload['rating'] === null ? 4.5 : (float) $payload['rating'],
            'short_description'    => $payload['short_description'],
            'full_description'     => $payload['full_description'],
            'overview'             => $payload['overview'],
            'learning_outcomes'    => $payload['learning_outcomes'],
            'prerequisites'        => $payload['prerequisites'],
            'certification'        => $payload['certification'],
            'audience'             => $payload['audience'],
            'sort_order'           => (int) $payload['sort_order'],
            'is_active'            => $flags['is_active'],
            'is_popular'           => $flags['is_popular'],
            'is_continue_learning' => $flags['is_continue_learning'],
            'is_featured'          => $flags['is_featured'],
            'meta_title'           => $payload['meta_title'],
            'meta_description'     => $payload['meta_description'],
            'meta_keywords'        => $payload['meta_keywords'],
            // image and brochure are deliberately absent — not in the values and
            // not in the update set, so an import can neither set nor clear them.
        ];

        return $result;
    }

    /**
     * The rules to judge one row by.
     *
     * A create is held to the manual form's full rule set — a new course needs
     * its category, duration and level just as much when it arrives from a
     * spreadsheet.
     *
     * An update is judged only on the columns the sheet actually carries. Upload
     * a file of course_name, slug and duration and you are editing the duration
     * of existing courses; demanding a skill_level the course already has would
     * make narrow, targeted edits impossible. A column that IS present but left
     * empty still fails its required rule — that is the admin clearing a field,
     * which the form would refuse too.
     */
    private function rulesFor(bool $isUpdate): array
    {
        $rules = CourseRequest::columnRules();

        if (! $isUpdate) {
            return $rules;
        }

        $present = [];

        foreach (self::FIELD_MAP as $heading => $attribute) {
            if (in_array($heading, $this->sheetColumns, true)) {
                $present[$attribute] = true;
            }
        }

        return array_filter(
            $rules,
            function (string $field) use ($present) {
                // FAQ rules ride along with the FAQ columns.
                if (str_starts_with($field, 'faqs')) {
                    return $this->sheetCarriesFaqs();
                }

                return isset($present[$field]);
            },
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * The category to write when the sheet did not name one.
     *
     * Only reachable on an update whose sheet omits the category column — the
     * course keeps the category it already has. category_id is NOT NULL, so the
     * value still has to be supplied for the insert half of the upsert.
     */
    private function categoryFallback(?array $existing): ?int
    {
        return $existing['category_id'] ?? null;
    }

    /**
     * Read the five FAQ pairs.
     *
     * A pair with neither half filled in is simply not a question. One half on
     * its own is a mistake worth naming — an answer with no question would show
     * on the site as a blank accordion header.
     *
     * @return array{0: array<int, array{question:string, answer:string}>, 1: array<int, string>}
     */
    private function readFaqs(array $data): array
    {
        $faqs = [];
        $errors = [];

        for ($i = 1; $i <= Course::MAX_FAQS; $i++) {
            $question = CourseImportTemplate::toText($data["faq_{$i}_question"] ?? null);
            $answer   = CourseImportTemplate::toText($data["faq_{$i}_answer"] ?? null);

            if ($question === null && $answer === null) {
                continue;
            }

            if ($question === null) {
                $errors[] = "faq_{$i}: an answer was given with no question.";
                continue;
            }

            if ($answer === null) {
                $errors[] = "faq_{$i}: a question was given with no answer.";
                continue;
            }

            $faqs[] = ['question' => $question, 'answer' => $answer];
        }

        // The template only offers five pairs, so this cannot overflow — but the
        // cap is the form's rule, not the template's, so it is enforced here too.
        return [array_slice($faqs, 0, Course::MAX_FAQS), $errors];
    }

    /**
     * Match a cell against the application's allowed values, ignoring case and
     * stray spaces — "  online " is plainly Online. Anything else is returned
     * unchanged so the validator reports it verbatim, which is what the admin
     * needs to see to fix the sheet.
     */
    private function matchAllowed(mixed $value, array $allowed): ?string
    {
        $text = CourseImportTemplate::toText($value);

        if ($text === null) {
            return null;
        }

        foreach ($allowed as $option) {
            if (strcasecmp($option, $text) === 0) {
                return $option;
            }
        }

        return $text;
    }

    /** Give the enum failure the wording the spec asks for. */
    private function humanise(string $message, array $data): string
    {
        if (str_contains($message, 'skill level is invalid')) {
            return 'Skill level "' . trim((string) ($data['skill_level'] ?? '')) . '" is not supported. Use one of: '
                . implode(', ', Course::SKILL_LEVELS) . '.';
        }

        return $message;
    }

    /** Case- and whitespace-insensitive key for name matching. */
    private function fold(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    /** @param array<int, array> $checked */
    private function summarise(array $checked): array
    {
        $count = fn (string $status) => count(array_filter($checked, fn ($r) => $r['status'] === $status));

        $create = $count(self::CREATE);
        $update = $count(self::UPDATE);

        return [
            'total'      => count($checked),
            'create'     => $create,
            'update'     => $update,
            'ready'      => $create + $update,
            'errors'     => $count(self::ERROR),
            'duplicates' => $count(self::DUPLICATE),
        ];
    }
}
