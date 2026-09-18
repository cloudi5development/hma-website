<?php

namespace Tests\Feature\Backend;

use App\Models\Form;
use App\Models\FormField;
use App\Models\User;
use App\Services\FormBuilderService;
use App\Support\FormFieldType;
use App\Support\FormImportSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;
use ZipArchive;

/**
 * Bulk upload for the Form Builder.
 *
 * Every test here uses a real spreadsheet, written by PhpSpreadsheet and read
 * back through the real endpoint, because the ways a spreadsheet goes wrong —
 * numbers read as 3.0, a formula in a cell, a renamed text file — only exist in
 * real files.
 *
 * A sheet only ever adds QUESTIONS, into the section Bulk Upload was opened
 * from. Pages and sections are made by hand on the builder.
 *
 * The last group is the one that matters most: it takes a preview's questions,
 * posts them through the builder's ordinary save exactly as the screen does,
 * and checks that what lands in the database is what a hand-built question
 * would be.
 */
class FormBulkUploadTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession(['admin_logged_in' => true, 'admin_id' => $admin->id]);
    }

    /**
     * One data row, in the order of every column the reader understands:
     * Order, Label, Type, Required, Placeholder, Description, Options, Correct Answer.
     */
    private function row(
        mixed $order, string $label, string $type, string $required = 'Yes', string $options = '',
        string $answer = '', string $placeholder = '', string $description = '',
    ): array {
        return [$order, $label, $type, $required, $placeholder, $description, $options, $answer];
    }

    /** A real workbook on disk, handed over as an upload. */
    private function sheet(array $rows, ?array $headings = null, string $ext = 'xlsx', string $title = 'Form Fields'): UploadedFile
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->setTitle($title)->fromArray(
            array_merge([$headings ?? array_values(FormImportSheet::COLUMNS)], $rows),
            null,
            'A1',
            true,
        );

        $path = tempnam(sys_get_temp_dir(), 'fbu') . '.' . $ext;
        IOFactory::createWriter($book, $ext === 'xls' ? 'Xls' : 'Xlsx')->save($path);

        return new UploadedFile($path, 'questions.' . $ext, null, null, true);
    }

    private function preview(UploadedFile $file, array $context = []): TestResponse
    {
        return $this->asAdmin()->post(route('backend.forms.bulk-preview'), [
            'file'    => $file,
            'context' => json_encode($context + ['form_type' => Form::STANDARD, 'questions' => 0]),
        ], ['Accept' => 'application/json']);
    }

    /** The errors listed against the row with this Excel row number. */
    private function errorsFor(TestResponse $response, int $excelRow): array
    {
        foreach ($response->json('rows') as $row) {
            if ($row['row'] === $excelRow) {
                return $row['errors'];
            }
        }

        $this->fail("No row {$excelRow} in the preview.");
    }

    /** The template for a form type, saved to disk and opened. */
    private function template(bool $quiz = false): string
    {
        $response = $this->asAdmin()
            ->get(route('backend.forms.bulk-template', $quiz ? ['type' => Form::QUIZ] : []))
            ->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
        file_put_contents($path, file_get_contents($response->baseResponse->getFile()->getPathname()));

        return $path;
    }

    /* ============================== TEMPLATES ==============================
       One per form type. Neither has Page or Section: those are made by hand. */

    public function test_the_standard_template_has_label_and_placeholder_and_no_page_or_section(): void
    {
        $book = IOFactory::load($this->template());

        $this->assertSame([FormImportSheet::SHEET, 'Instructions', 'Field Types'], $book->getSheetNames());

        $this->assertSame(
            ['Order', 'Label', 'Type', 'Required', 'Placeholder', 'Description', 'Options', null],
            $book->getSheetByName(FormImportSheet::SHEET)->rangeToArray('A1:H1')[0],
        );

        // Every type in the registry is listed on the Field Types sheet.
        $listed = array_column($book->getSheetByName('Field Types')->toArray(), 0);
        foreach (FormFieldType::TYPES as $spec) {
            $this->assertContains($spec['label'], $listed);
        }
    }

    public function test_the_quiz_template_asks_for_the_question_and_its_answer_and_nothing_a_quiz_does_not_show(): void
    {
        $book = IOFactory::load($this->template(quiz: true));

        $this->assertSame([FormImportSheet::QUIZ_SHEET, 'Instructions', 'Field Types'], $book->getSheetNames());

        $headings = $book->getSheetByName(FormImportSheet::QUIZ_SHEET)->rangeToArray('A1:H1')[0];

        $this->assertSame(['Order', 'Question', 'Type', 'Required', 'Options', 'Correct Answer', null, null], $headings);
        $this->assertNotContains('Label', $headings);

        // A quiz draws neither a placeholder nor a description, so its template
        // has nowhere to type one — which is how "Choose one" got under every
        // imported question before.
        $this->assertNotContains('Placeholder', $headings);
        $this->assertNotContains('Description', $headings);

        // Its sample questions are multiple choice, each with its answer.
        $sample = $book->getSheetByName(FormImportSheet::QUIZ_SHEET)->rangeToArray('A2:F2')[0];
        $this->assertSame('Multiple choice', $sample[2]);
        $this->assertNotEmpty($sample[5]);
    }

    public function test_neither_template_has_page_or_section_columns(): void
    {
        foreach ([false, true] as $quiz) {
            $sheet = IOFactory::load($this->template($quiz))->getSheet(0);
            $row   = $sheet->rangeToArray('A1:Z1')[0];

            $this->assertNotContains('Page', $row);
            $this->assertNotContains('Section', $row);
        }
    }

    public function test_the_template_dropdowns_are_inline_lists_with_the_arrow_showing(): void
    {
        foreach ([false, true] as $quiz) {
            $zip = new ZipArchive();
            $zip->open($this->template($quiz));
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();

            // Inline, never a range on another sheet: Excel was seen drawing an
            // empty list from a valid range while building the course template.
            $this->assertStringContainsString('"' . implode(',', FormImportSheet::typeLabels()) . '"', html_entity_decode($xml));
            $this->assertStringContainsString('"Yes,No"', html_entity_decode($xml));

            // showDropDown="1" in the file HIDES the arrow. It must never be written.
            $this->assertStringNotContainsString('showDropDown="1"', $xml);
        }
    }

    public function test_each_templates_own_sample_rows_import_cleanly(): void
    {
        foreach ([Form::STANDARD, Form::QUIZ] as $type) {
            $quiz = $type === Form::QUIZ;

            $this->preview(new UploadedFile($this->template($quiz), 'template.xlsx', null, null, true), ['form_type' => $type])
                ->assertOk()
                ->assertJsonPath('summary.total', count(FormImportSheet::sampleRows($quiz)))
                ->assertJsonPath('summary.invalid', 0)
                ->assertJsonPath('summary.importable', true);
        }
    }

    /** No second type system: every label, its snake_case spelling and every key resolve. */
    public function test_every_registry_type_is_accepted_by_label_alias_and_key(): void
    {
        foreach (FormFieldType::TYPES as $key => $spec) {
            $this->assertSame($key, FormImportSheet::typeFor($spec['label']), $spec['label']);
            $this->assertSame($key, FormImportSheet::typeFor(strtoupper($spec['label'])), $spec['label'] . ' in capitals');
            $this->assertSame($key, FormImportSheet::typeFor(FormImportSheet::typeAlias($key)), FormImportSheet::typeAlias($key));
            $this->assertSame($key, FormImportSheet::typeFor($key), $key);
        }

        // The spellings from the specification, verbatim.
        foreach ([
            'short_answer' => FormFieldType::SHORT_TEXT, 'paragraph' => FormFieldType::LONG_TEXT,
            'mobile_number' => FormFieldType::MOBILE, 'multiple_choice' => FormFieldType::RADIO,
            'checkboxes' => FormFieldType::CHECKBOX, 'drop_down' => FormFieldType::DROPDOWN,
            'yes_no' => FormFieldType::YES_NO, 'file_upload' => FormFieldType::FILE,
            'multiple_choice_grid' => FormFieldType::MC_GRID, 'tick_box_grid' => FormFieldType::TICK_GRID,
            'hidden_field' => FormFieldType::HIDDEN, 'date_time' => FormFieldType::DATETIME,
        ] as $spelling => $key) {
            $this->assertSame($key, FormImportSheet::typeFor($spelling), $spelling);
        }

        $this->assertNull(FormImportSheet::typeFor('invalid_type'));

        // Capitals, which is how people type into spreadsheets. Str::snake alone
        // read every capital as a new word and matched nothing.
        $this->assertSame(FormFieldType::EMAIL, FormImportSheet::typeFor('EMAIL'));
        $this->assertSame(FormFieldType::RADIO, FormImportSheet::typeFor('MULTIPLE_CHOICE'));
        $this->assertSame('correct_answer', FormImportSheet::columnFor('CORRECT ANSWER'));
        $this->assertSame('label', FormImportSheet::columnFor('Question'));
    }

    /* =============================== READING =============================== */

    public function test_basic_choice_file_and_date_fields_are_read_in_order(): void
    {
        // Written out of order on purpose: the Order column decides.
        $response = $this->preview($this->sheet([
            $this->row(3, 'Phone', 'Mobile number', 'No', placeholder: 'e.g. 98765 43210'),
            $this->row(1, 'Full Name', 'short_answer', description: 'As on your certificate'),
            $this->row(6, 'Resume', 'File upload'),
            $this->row(2, 'Email', 'Email'),
            $this->row(5, 'Skills', 'Checkboxes', 'No', 'PHP | Laravel | React'),
            $this->row(4, 'Course', 'Drop-down', 'Yes', 'Python | Java'),
            $this->row(7, 'Start Date', 'Date'),
            $this->row(8, 'Slot', 'time'),
            $this->row(9, 'Interview', 'Date & time'),
        ]))->assertOk()->assertJsonPath('summary.importable', true);

        $fields = $response->json('fields');

        $this->assertSame(
            ['Full Name', 'Email', 'Phone', 'Course', 'Skills', 'Resume', 'Start Date', 'Slot', 'Interview'],
            array_column($fields, 'label'),
        );

        $this->assertSame(
            [FormFieldType::SHORT_TEXT, FormFieldType::EMAIL, FormFieldType::MOBILE, FormFieldType::DROPDOWN,
             FormFieldType::CHECKBOX, FormFieldType::FILE, FormFieldType::DATE, FormFieldType::TIME, FormFieldType::DATETIME],
            array_column($fields, 'field_type'),
        );

        $this->assertSame(['PHP', 'Laravel', 'React'], $fields[4]['options']);
        $this->assertFalse($fields[2]['is_required']);
        $this->assertSame('e.g. 98765 43210', $fields[2]['placeholder']);
        $this->assertSame('As on your certificate', $fields[0]['help_text']);
    }

    /** A quiz sheet calls its question column Question, and it is read as the label. */
    public function test_a_quiz_sheet_is_read_by_its_question_column(): void
    {
        $response = $this->preview($this->sheet([
            [1, 'What is Laravel?', 'Multiple choice', 'Yes', 'PHP framework | Database | OS', 'PHP framework'],
            [2, '', 'Multiple choice', 'Yes', 'A | B', 'A'],
        ], FormImportSheet::columns(true), title: FormImportSheet::QUIZ_SHEET), ['form_type' => Form::QUIZ])->assertOk();

        $this->assertSame('What is Laravel?', $response->json('rows.0.label'));
        $this->assertSame('PHP framework', $response->json('rows.0.correct_answer'));

        // And the error names the column by the word on the admin's own sheet.
        $this->assertContains('Question is required.', $this->errorsFor($response, 3));
    }

    /** An older download still carrying Page and Section columns imports; the columns are just not used. */
    public function test_a_sheet_that_still_has_page_and_section_columns_is_read_without_them(): void
    {
        $response = $this->preview($this->sheet(
            [[1, 'Personal Details', 'Basic Info', 'Full Name', 'Short answer', 'Yes']],
            ['Order', 'Page', 'Section', 'Label', 'Type', 'Required'],
        ))->assertOk()->assertJsonPath('summary.importable', true);

        $this->assertSame('Full Name', $response->json('fields.0.label'));
        $this->assertArrayNotHasKey('page', $response->json('fields.0'));
        $this->assertArrayNotHasKey('section', $response->json('fields.0'));
    }

    public function test_yes_no_makes_its_own_options(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'Relocate?', 'Yes / No'),
            $this->row(2, 'Graduate?', 'yes_no', 'Yes', 'Maybe | Never'),
        ]))->assertOk()->assertJsonPath('summary.importable', true);

        // The builder adds Yes and No itself, exactly as when the type is picked
        // by hand, so none are sent.
        $this->assertSame([], $response->json('fields.0.options'));

        $graduate = collect($response->json('rows'))->firstWhere('label', 'Graduate?');
        $this->assertNotEmpty($graduate['warnings'], 'options given for Yes / No are reported as unused');
    }

    public function test_grids_read_their_rows_and_columns(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'Rate us', 'Multiple-choice grid', 'Yes', 'Rows: Product | Support ; Columns: Poor | Good | Excellent'),
            $this->row(2, 'Broken grid', 'Tick box grid', 'Yes', 'Product | Support'),
        ]))->assertOk();

        $rate = collect($response->json('rows'))->firstWhere('label', 'Rate us');
        $this->assertSame(['Product', 'Support'], $rate['grid_rows']);
        $this->assertSame(['Poor', 'Good', 'Excellent'], $rate['grid_columns']);

        $this->assertStringContainsString('needs rows and columns', implode(' ', $this->errorsFor($response, 3)));
    }

    public function test_an_xls_file_is_read_as_well_as_xlsx(): void
    {
        $this->preview($this->sheet([$this->row(1, 'Full Name', 'Short answer')], ext: 'xls'))
            ->assertOk()
            ->assertJsonPath('summary.valid', 1);
    }

    /* ============================ MULTIPLE CHOICE ========================== */

    public function test_a_multiple_choice_correct_answer_is_carried_through(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'What is Python?', 'multiple_choice', 'Yes', 'Programming Language | Database | OS | Browser', 'Programming Language'),
            $this->row(2, 'Which symbol starts a comment?', 'multiple_choice', 'Yes', '# | // | <!-- --> | **', '#'),
            $this->row(3, 'Which is a valid variable name?', 'multiple_choice', 'Yes', 'my_name | 123name | my-name | class', 'my_name'),
        ]), ['form_type' => Form::QUIZ])->assertOk()->assertJsonPath('summary.importable', true);

        $this->assertSame(['Programming Language', '#', 'my_name'], array_column($response->json('fields'), 'correct_answer'));
    }

    public function test_a_correct_answer_that_is_not_an_option_blocks_the_import(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'Which is a framework?', 'Multiple choice', 'Yes', 'PHP | Laravel | React | Java', 'Laravel'),
            $this->row(2, 'Which is a language?', 'Multiple choice', 'Yes', 'PHP | Laravel | React | Java', 'Python'),
        ]), ['form_type' => Form::QUIZ])->assertOk();

        $this->assertContains('Correct Answer “Python” does not exist in the provided options.', $this->errorsFor($response, 3));

        $response->assertJsonPath('summary.importable', false)->assertJsonPath('fields', []);
    }

    public function test_only_one_correct_answer_is_allowed(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'Pick', 'Multiple choice', 'Yes', 'PHP | Laravel', 'PHP | Laravel'),
        ]), ['form_type' => Form::QUIZ])->assertOk();

        $this->assertContains('Only one Correct Answer is allowed for a Multiple choice question.', $this->errorsFor($response, 2));
    }

    public function test_a_correct_answer_on_any_other_type_is_refused(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'Skills', 'Checkboxes', 'Yes', 'PHP | Laravel', 'PHP'),
        ]), ['form_type' => Form::QUIZ])->assertOk();

        $this->assertStringContainsString('only applies to Multiple choice', implode(' ', $this->errorsFor($response, 2)));
    }

    public function test_a_quiz_needs_a_correct_answer_and_a_standard_form_does_not(): void
    {
        $file = fn () => $this->sheet([$this->row(1, 'Gender', 'Multiple choice', 'Yes', 'Male | Female | Other')]);

        $this->preview($file(), ['form_type' => Form::STANDARD])->assertOk()->assertJsonPath('summary.importable', true);

        $quiz = $this->preview($file(), ['form_type' => Form::QUIZ])->assertOk()->assertJsonPath('summary.importable', false);
        $this->assertContains('Correct Answer is required — this form is a Quiz.', $this->errorsFor($quiz, 2));
    }

    /* ============================== ROW ERRORS ============================= */

    /* ============================ THE OPTIONS CELL ========================== */

    /** Choices typed one per line (Alt+Enter) used to arrive as ONE option with spaces in it. */
    public function test_options_on_separate_lines_are_separate_options(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'Choose your age', 'Drop-down', 'Yes', "18-25\n26-35\n36-45"),
            $this->row(2, 'Rate us', 'Multiple-choice grid', 'No', "Rows: Product | Support\nColumns: Good | Bad"),
        ]))->assertOk()->assertJsonPath('summary.importable', true);

        $response->assertJsonPath('fields.0.options', ['18-25', '26-35', '36-45']);
        $response->assertJsonPath('fields.1.rows', ['Product', 'Support']);
        $response->assertJsonPath('fields.1.columns', ['Good', 'Bad']);
    }

    /** Commas are not the separator — said before import, not guessed at. */
    public function test_options_separated_by_commas_are_flagged(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'Choose your age', 'Drop-down', 'Yes', '18-25, 26-35, 36-45'),
            $this->row(2, 'Consent', 'Checkboxes', 'Yes', 'I agree to the Terms, Privacy Policy'),
        ]))->assertOk();

        $this->assertStringContainsString('separate them with “|” instead of commas', implode(' ', $response->json('rows.0.warnings')));
        $response->assertJsonPath('rows.0.options', ['18-25, 26-35, 36-45']);   // not split behind the admin's back
    }

    /**
     * In the standard template Options is the last column, and choices typed
     * "in the next box" land in Placeholder or Description. A "|" list there on
     * a question that needs choices IS its choices: used, with a note.
     */
    public function test_choices_typed_into_placeholder_or_description_are_used_as_the_options(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'Choose your age', 'Drop-down', 'Yes', '', '', '18-25 | 26-35 | 36-45'),
            $this->row(2, 'Gender', 'Multiple choice', 'Yes', '', '', '', 'Male | Female | Other'),
            $this->row(3, 'Name', 'Short answer', 'No', '', '', 'First | Last'),   // not a choice question: left alone
        ]))->assertOk()->assertJsonPath('summary.importable', true)->assertJsonPath('summary.invalid', 0);

        $response->assertJsonPath('fields.0.options', ['18-25', '26-35', '36-45']);
        $response->assertJsonPath('fields.0.placeholder', '');
        $response->assertJsonPath('fields.1.options', ['Male', 'Female', 'Other']);
        $response->assertJsonPath('fields.1.help_text', '');
        $response->assertJsonPath('fields.2.placeholder', 'First | Last');

        $this->assertContains('The choices were in the Placeholder column, so they were used as the options.', $response->json('rows.0.warnings'));
        $this->assertContains('The choices were in the Description column, so they were used as the options.', $response->json('rows.1.warnings'));
    }

    /** An empty Options cell with nowhere else to take choices from is still refused, and says so plainly. */
    public function test_a_choice_question_with_no_choices_anywhere_is_refused(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'Pick one', 'Drop-down', 'Yes'),
            $this->row(2, 'Pick again', 'Multiple choice', 'Yes', '', 'A | B'),   // in Correct Answer: which one is right is a guess
        ]))->assertOk()->assertJsonPath('summary.importable', false);

        $this->assertStringContainsString('The Options cell is empty.', implode(' ', $this->errorsFor($response, 2)));
        $this->assertContains('The choices for this Multiple choice question are in the Correct Answer column — move them to the Options column.', $this->errorsFor($response, 3));
    }

    public function test_each_kind_of_bad_row_is_named_against_its_row(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'Fine', 'Short answer'),                          // row 2
            $this->row(2, 'Bad type', 'invalid_type'),                      // row 3
            $this->row(3, '', 'Short answer'),                              // row 4
            $this->row(4, 'No options', 'Checkboxes'),                      // row 5
            $this->row(5, 'No options either', 'Drop-down'),                // row 6
            $this->row(6, 'Nor this', 'Multiple choice'),                   // row 7
            $this->row('six', 'Bad order', 'Short answer'),                 // row 8
            $this->row(8, 'Twice', 'Checkboxes', 'Yes', 'PHP | php | Go'),  // row 9
        ]))->assertOk();

        $this->assertSame([], $this->errorsFor($response, 2));
        $this->assertContains('Unsupported field type “invalid_type”. Choose one from the Type list.', $this->errorsFor($response, 3));
        $this->assertContains('Label is required.', $this->errorsFor($response, 4));
        $this->assertStringContainsString('Add the choices for this Checkboxes question', implode(' ', $this->errorsFor($response, 5)));
        $this->assertStringContainsString('Add the choices for this Drop-down question', implode(' ', $this->errorsFor($response, 6)));
        $this->assertStringContainsString('Add the choices for this Multiple choice question', implode(' ', $this->errorsFor($response, 7)));
        $this->assertStringContainsString('Order must be a whole number', implode(' ', $this->errorsFor($response, 8)));
        $this->assertContains('Option “php” is listed more than once.', $this->errorsFor($response, 9));

        $response->assertJsonPath('summary.total', 8)
            ->assertJsonPath('summary.valid', 1)
            ->assertJsonPath('summary.invalid', 7)
            ->assertJsonPath('summary.importable', false)
            ->assertJsonPath('fields', []);
    }

    /** Yes and No only — not the near-misses a spreadsheet invites. */
    public function test_required_must_be_yes_or_no(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'A', 'Short answer', 'Yes'),
            $this->row(2, 'B', 'Short answer', 'no'),
            $this->row(3, 'C', 'Short answer', 'Y'),
            $this->row(4, 'D', 'Short answer', 'TRUE'),
            $this->row(5, 'E', 'Short answer', 'Required'),
            $this->row(6, 'F', 'Short answer', '1'),
            $this->row(7, 'G', 'Short answer', ''),
        ]))->assertOk();

        $this->assertSame([], $this->errorsFor($response, 2));
        $this->assertSame([], $this->errorsFor($response, 3));

        foreach ([4 => 'Y', 5 => 'TRUE', 6 => 'Required', 7 => '1'] as $excelRow => $value) {
            $this->assertContains("Required must be Yes or No (found “{$value}”).", $this->errorsFor($response, $excelRow));
        }

        $this->assertContains('Required must be Yes or No.', $this->errorsFor($response, 8));
    }

    public function test_duplicate_orders_name_both_rows(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, 'A', 'Short answer'),
            $this->row(2, 'B', 'Short answer'),
            $this->row(2, 'C', 'Short answer'),
            $this->row(4, 'D', 'Short answer'),
        ]))->assertOk();

        $this->assertContains('Duplicate Order value 2 found in rows 3 and 4.', $this->errorsFor($response, 3));
        $this->assertContains('Duplicate Order value 2 found in rows 3 and 4.', $this->errorsFor($response, 4));
        $response->assertJsonPath('summary.importable', false);
    }

    public function test_a_formula_is_refused_rather_than_calculated(): void
    {
        $response = $this->preview($this->sheet([
            $this->row(1, '=HYPERLINK("http://evil.example","Click")', 'Short answer'),
        ]))->assertOk();

        $this->assertContains('Label holds a formula. Type the text itself instead.', $this->errorsFor($response, 2));
    }

    /* ============================= FILE ERRORS ============================= */

    public function test_a_file_that_is_not_a_spreadsheet_is_refused_plainly(): void
    {
        $this->preview(UploadedFile::fake()->createWithContent('questions.xlsx', "Order,Label\n1,Name"))
            ->assertStatus(422)
            ->assertJsonPath('message', 'That file is not a readable Excel spreadsheet. Save it as .xlsx or .xls and upload it again.');
    }

    public function test_only_excel_extensions_are_accepted(): void
    {
        $this->preview(UploadedFile::fake()->createWithContent('questions.csv', "Order,Label\n1,Name"))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Upload an Excel file — .xlsx or .xls.');
    }

    public function test_an_empty_sheet_is_refused(): void
    {
        $this->preview($this->sheet([]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'The sheet has no questions in it. Add one question per row, under the headings.');
    }

    public function test_missing_headings_are_named_as_the_form_types_template_names_them(): void
    {
        $this->preview($this->sheet([[1, 'Name']], ['Order', 'Something']))
            ->assertStatus(422)
            ->assertJsonPath('message', 'The sheet is missing the “Label”, “Type” and “Required” columns. Download a fresh template and keep its headings as they are.');

        $this->preview($this->sheet([[1, 'Name']], ['Order', 'Something']), ['form_type' => Form::QUIZ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'The sheet is missing the “Question”, “Type” and “Required” columns. Download a fresh template and keep its headings as they are.');
    }

    /* ================================ NO LIMITS ============================
       The module sets none: not on rows, text length, options, or how full the
       form already is. The numbers below are past every cap it used to have
       (200 rows, 190-character labels, 60 options). */

    public function test_a_sheet_of_any_size_is_read_whole_with_long_text_and_many_options(): void
    {
        $long    = trim(str_repeat('Explain the difference between these two approaches. ', 20));   // ~1,100 characters
        $options = implode(' | ', array_map(fn ($i) => "Choice {$i}", range(1, 150)));

        $rows = [];
        for ($i = 1; $i <= 320; $i++) {
            $rows[] = $this->row($i, "Question {$i}", 'Short answer', 'No');
        }
        $rows[] = $this->row(321, $long, 'Multiple choice', 'Yes', $options, 'Choice 150');

        $response = $this->preview($this->sheet($rows), ['form_type' => Form::QUIZ])
            ->assertOk()
            ->assertJsonPath('summary.total', 321)
            ->assertJsonPath('summary.invalid', 0)
            ->assertJsonPath('summary.importable', true)
            ->assertJsonPath('errors', []);

        $fields = $response->json('fields');

        $this->assertCount(321, $fields);
        $this->assertSame('Question 320', $fields[319]['label']);
        $this->assertSame($long, $fields[320]['label']);
        $this->assertCount(150, $fields[320]['options']);
        $this->assertSame('Choice 150', $fields[320]['correct_answer']);
    }

    public function test_an_import_is_never_blocked_by_how_many_questions_the_form_already_has(): void
    {
        $this->preview($this->sheet([
            $this->row(1, 'One', 'Short answer'),
            $this->row(2, 'Two', 'Short answer'),
        ]), ['questions' => 5000])
            ->assertOk()
            ->assertJsonPath('summary.importable', true)
            ->assertJsonPath('errors', []);
    }

    public function test_a_file_the_server_itself_refused_is_explained_as_a_server_limit(): void
    {
        // What PHP hands over when the file was bigger than upload_max_filesize.
        $file = new UploadedFile(tempnam(sys_get_temp_dir(), 'big'), 'questions.xlsx', null, UPLOAD_ERR_INI_SIZE, true);

        $response = $this->preview($file)->assertStatus(422);

        $this->assertStringStartsWith('That file is larger than this server accepts (', $response->json('message'));
    }

    public function test_the_preview_and_template_are_behind_the_admin_guard(): void
    {
        $this->get(route('backend.forms.bulk-template'))->assertRedirect(route('backend.auth.login'));
        $this->get(route('backend.forms.bulk-template', ['type' => Form::QUIZ]))->assertRedirect(route('backend.auth.login'));
        $this->post(route('backend.forms.bulk-preview'), ['file' => $this->sheet([$this->row(1, 'A', 'Short answer')])])
            ->assertRedirect(route('backend.auth.login'));
    }

    /* ======================== THROUGH THE ORDINARY SAVE ====================
       A preview's questions, added the way the builder screen adds them — to the
       end of the section Bulk Upload was opened from — then saved by the
       builder's own request and service. What lands in the database must be
       what a question built by hand would be. */

    /**
     * The pairs the screen would post as builder_payload for one page holding
     * one section: the questions already in it, then the imported ones.
     */
    private function payloadFor(array $existing, array $fields, array $page = ['ref' => 'p0', 'id' => '', 'title' => ''],
        array $section = ['ref' => 's0', 'id' => '', 'title' => '']): array
    {
        $pairs = [
            ["pages[{$page['ref']}][id]", (string) $page['id']],
            ["pages[{$page['ref']}][title]", $page['title']],
            ["sections[{$section['ref']}][id]", (string) $section['id']],
            ["sections[{$section['ref']}][page_ref]", $page['ref']],
            ["sections[{$section['ref']}][title]", $section['title']],
        ];

        $place = fn (string $key) => [
            ["fields[{$key}][page_ref]", $page['ref']],
            ["fields[{$key}][section_ref]", $section['ref']],
        ];

        foreach ($existing as $key => $field) {
            array_push($pairs, ...$field, ...$place($key));
        }

        foreach ($fields as $n => $f) {
            $key  = 'nf' . $n;
            $rows = [
                ["fields[{$key}][id]", ''],
                ["fields[{$key}][label]", $f['label']],
                ["fields[{$key}][field_type]", $f['field_type']],
                ["fields[{$key}][is_required]", '0'],
                ["fields[{$key}][placeholder]", (string) $f['placeholder']],
                ["fields[{$key}][help_text]", (string) $f['help_text']],
            ];

            if ($f['is_required']) {
                $rows[] = ["fields[{$key}][is_required]", '1'];   // the ticked box after the hidden 0
            }

            $options = $f['field_type'] === FormFieldType::YES_NO ? ['Yes', 'No'] : $f['options'];
            foreach ($options as $i => $label) {
                $rows[] = ["fields[{$key}][options][o{$i}][label]", $label];
                $rows[] = ["fields[{$key}][options][o{$i}][value]", ''];
            }

            foreach (['rows', 'columns'] as $list) {
                foreach ($f[$list] as $i => $label) {
                    $rows[] = ["fields[{$key}][{$list}][g{$i}][label]", $label];
                }
            }

            $rows[] = ["fields[{$key}][correct_answer]", (string) $f['correct_answer']];

            array_push($pairs, ...$rows, ...$place($key));
        }

        return $pairs;
    }

    public function test_imported_questions_save_exactly_like_hand_built_ones_in_the_section_they_were_uploaded_to(): void
    {
        // Pages and sections made by hand first.
        $form = app(FormBuilderService::class)->save([
            'name' => 'Skills Quiz', 'status' => Form::PUBLISHED,
            'structure_type' => Form::PAGES_SECTIONS, 'form_type' => Form::QUIZ,
            'pages'    => ['p' => ['title' => 'Technical']],
            'sections' => ['s' => ['page_ref' => 'p', 'title' => 'Skills']],
            'fields'   => [],
        ]);

        $page    = $form->pages->first();
        $section = $form->sections->first();

        $preview = $this->preview($this->sheet([
            $this->row(1, 'Full Name', 'Short answer', 'Yes', placeholder: 'Enter your name', description: 'As on your ID'),
            $this->row(2, 'Primary Skill', 'Multiple choice', 'Yes', 'PHP | Laravel | React', 'Laravel'),
            $this->row(3, 'Known', 'Checkboxes', 'No', 'PHP | Laravel'),
            $this->row(4, 'Relocate?', 'Yes / No'),
            $this->row(5, 'Rate us', 'Tick box grid', 'No', 'Rows: Product | Support ; Columns: Poor | Good'),
        ]), ['form_type' => Form::QUIZ])->assertOk()->assertJsonPath('summary.importable', true);

        $pairs = $this->payloadFor(
            [],
            $preview->json('fields'),
            ['ref' => 'p' . $page->id, 'id' => $page->id, 'title' => 'Technical'],
            ['ref' => 's' . $section->id, 'id' => $section->id, 'title' => 'Skills'],
        );

        $this->asAdmin()->put(route('backend.forms.update', $form), [
            'name'            => 'Skills Quiz',
            'status'          => Form::PUBLISHED,
            'structure_type'  => Form::PAGES_SECTIONS,
            'form_type'       => Form::QUIZ,
            'builder_payload' => json_encode($pairs),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $form = $form->fresh(['fields.allOptions', 'pages', 'sections']);

        // Nothing new was made around them.
        $this->assertSame(['Technical'], $form->pages->pluck('title')->all());
        $this->assertSame(['Skills'], $form->sections->pluck('title')->all());

        $this->assertSame(['Full Name', 'Primary Skill', 'Known', 'Relocate?', 'Rate us'], $form->fields->pluck('label')->all());
        $this->assertSame([$section->id], $form->fields->pluck('form_section_id')->unique()->values()->all());

        $name = $form->fields[0];
        $this->assertTrue($name->is_required);
        $this->assertSame('Enter your name', $name->placeholder);
        $this->assertSame('As on your ID', $name->help_text);

        $skill = $form->fields[1];
        $this->assertSame(FormFieldType::RADIO, $skill->field_type);
        $this->assertSame(['PHP', 'Laravel', 'React'], $skill->options->pluck('label')->all());
        $this->assertSame('Laravel', $skill->correctAnswer());

        $this->assertFalse($form->fields[2]->is_required);
        $this->assertSame(['Yes', 'No'], $form->fields[3]->options->pluck('label')->all());
        $this->assertSame(['Product', 'Support'], $form->fields[4]->rows->pluck('label')->all());
        $this->assertSame(['Poor', 'Good'], $form->fields[4]->columns->pluck('label')->all());

        // And it renders and is answerable like any other question — with the
        // correct answer nowhere on the page.
        $this->get(route('frontend.form.show', $form->slug))->assertOk()
            ->assertSee('Primary Skill')
            ->assertSee('name="primary_skill"', false)
            ->assertDontSee('correct_answer', false)
            ->assertDontSee('data-correct', false);
    }

    public function test_imported_questions_are_added_after_the_existing_ones(): void
    {
        $form = app(FormBuilderService::class)->save([
            'name' => 'Enquiry', 'status' => Form::PUBLISHED, 'structure_type' => Form::PLAIN,
            'fields' => [
                ['label' => 'Name', 'field_type' => FormFieldType::SHORT_TEXT],
                ['label' => 'Email', 'field_type' => FormFieldType::EMAIL],
            ],
        ]);

        $preview = $this->preview($this->sheet([
            $this->row(3, 'Message', 'Paragraph'),
            $this->row(1, 'Phone', 'Mobile number'),
            $this->row(2, 'Address', 'Paragraph'),
        ]), ['questions' => 2])->assertOk()->assertJsonPath('summary.importable', true);

        // The existing two, as the screen holds them, then the imported three.
        $existing = $form->fields->mapWithKeys(fn (FormField $f) => ["f{$f->id}" => [
            ["fields[f{$f->id}][id]", (string) $f->id],
            ["fields[f{$f->id}][label]", $f->label],
            ["fields[f{$f->id}][field_type]", $f->field_type],
        ]])->all();

        $this->asAdmin()->put(route('backend.forms.update', $form), [
            'name' => 'Enquiry', 'status' => Form::PUBLISHED, 'structure_type' => Form::PLAIN,
            'builder_payload' => json_encode($this->payloadFor($existing, $preview->json('fields'))),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame(['Name', 'Email', 'Phone', 'Address', 'Message'], $form->fresh()->fields->pluck('label')->all());
    }

    /* =============================== TRANSPORT =============================
       Why the builder posts one JSON input. PHP drops every input past
       max_input_vars (1000 here) without an error, so a 50-question quiz with
       four options each used to lose its last questions on save. */

    public function test_a_large_quiz_survives_the_save_intact_and_in_order(): void
    {
        $pairs = [];

        for ($q = 1; $q <= 80; $q++) {
            $key = "nf{$q}";
            $pairs[] = ["fields[{$key}][id]", ''];
            $pairs[] = ["fields[{$key}][label]", "Question {$q}"];
            $pairs[] = ["fields[{$key}][field_type]", FormFieldType::RADIO];
            $pairs[] = ["fields[{$key}][is_required]", '0'];
            $pairs[] = ["fields[{$key}][is_required]", '1'];

            // Numeric keys deliberately out of order: posted order is the order,
            // which a JavaScript object would have sorted away.
            foreach ([3, 0, 2, 1, 5, 4] as $position => $o) {
                $pairs[] = ["fields[{$key}][options][{$o}][label]", "Q{$q} option {$position}"];
                $pairs[] = ["fields[{$key}][options][{$o}][value]", ''];
            }

            $pairs[] = ["fields[{$key}][correct_answer]", "Q{$q} option 2"];
            $pairs[] = ["fields[{$key}][file_types][]", 'pdf'];
        }

        $this->assertGreaterThan(1000, count($pairs), 'this payload would exceed max_input_vars as separate inputs');

        $this->asAdmin()->post(route('backend.forms.store'), [
            'name' => 'Big Quiz', 'status' => Form::PUBLISHED, 'form_type' => Form::QUIZ,
            'builder_payload' => json_encode($pairs),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $form = Form::where('name', 'Big Quiz')->with('fields.options')->firstOrFail();

        $this->assertCount(80, $form->fields);
        $this->assertSame('Question 80', $form->fields->last()->label);
        $this->assertTrue($form->fields->last()->is_required, 'a repeated name keeps the last value, as PHP does');

        $this->assertSame(
            ['Q1 option 0', 'Q1 option 1', 'Q1 option 2', 'Q1 option 3', 'Q1 option 4', 'Q1 option 5'],
            $form->fields->first()->options->pluck('label')->all(),
        );

        $this->assertSame('Q80 option 2', $form->fields->last()->correctAnswer());
    }

    /**
     * Past every cap the builder used to have — 200 questions, 60 options, 20
     * pages, 40 sections, 190-character text — and nothing is cut off.
     */
    public function test_a_form_of_any_size_saves_whole(): void
    {
        $long  = str_repeat('A very long question that keeps going. ', 40);   // ~1,600 characters
        $pairs = [];

        for ($p = 1; $p <= 25; $p++) {
            $pairs[] = ["pages[p{$p}][id]", ''];
            $pairs[] = ["pages[p{$p}][title]", "Page {$p}"];
        }

        // 45 sections, all on the first page.
        for ($s = 1; $s <= 45; $s++) {
            $pairs[] = ["sections[s{$s}][id]", ''];
            $pairs[] = ["sections[s{$s}][page_ref]", 'p1'];
            $pairs[] = ["sections[s{$s}][title]", "Section {$s}"];
        }

        for ($q = 1; $q <= 230; $q++) {
            $key = "nf{$q}";
            array_push($pairs,
                ["fields[{$key}][id]", ''],
                ["fields[{$key}][label]", $q === 230 ? $long : "Question {$q}"],
                ["fields[{$key}][field_type]", $q === 230 ? FormFieldType::RADIO : FormFieldType::SHORT_TEXT],
                ["fields[{$key}][is_required]", '0'],
                ["fields[{$key}][page_ref]", 'p1'],
                ["fields[{$key}][section_ref]", 's' . (($q % 45) + 1)],
            );
        }

        for ($o = 1; $o <= 80; $o++) {
            $pairs[] = ["fields[nf230][options][o{$o}][label]", "Option {$o} " . str_repeat('x', 250)];
            $pairs[] = ["fields[nf230][options][o{$o}][value]", ''];
        }

        $name = str_repeat('Annual Hiring Assessment ', 20);   // ~500 characters

        $this->asAdmin()->post(route('backend.forms.store'), [
            'name'            => $name,
            'status'          => Form::PUBLISHED,
            'structure_type'  => Form::PAGES_SECTIONS,
            'builder_payload' => json_encode($pairs),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $form = Form::with(['fields.options', 'pages', 'sections'])->latest('id')->firstOrFail();

        $this->assertSame(trim($name), $form->name);
        $this->assertCount(25, $form->pages);
        $this->assertCount(45, $form->sections);
        $this->assertCount(230, $form->fields);
        $this->assertSame(trim($long), $form->fields->last()->label);
        $this->assertCount(80, $form->fields->last()->options);
        $this->assertSame('Option 80 ' . str_repeat('x', 250), $form->fields->last()->options->last()->label);
    }

    public function test_an_unreadable_payload_saves_nothing_and_says_so(): void
    {
        $this->asAdmin()->post(route('backend.forms.store'), [
            'name' => 'Broken', 'status' => Form::PUBLISHED, 'builder_payload' => '{not json',
        ])->assertSessionHasErrors('fields');

        $this->assertSame(0, Form::where('name', 'Broken')->count());
    }

    /* ============================ CORRECT ANSWER ===========================
       The same rule for a question typed into the builder. */

    public function test_a_hand_entered_correct_answer_must_be_one_of_the_options(): void
    {
        $this->asAdmin()->post(route('backend.forms.store'), [
            'name' => 'Quiz', 'status' => Form::PUBLISHED, 'form_type' => Form::QUIZ,
            'fields' => [[
                'label' => 'Pick', 'field_type' => FormFieldType::RADIO,
                'options' => [['label' => 'PHP'], ['label' => 'Laravel']],
                'correct_answer' => 'Python',
            ]],
        ])->assertSessionHasErrors('fields.0.correct_answer');

        $this->assertSame(0, Form::count());
    }

    public function test_switching_a_quiz_to_standard_keeps_its_answers(): void
    {
        $data = fn (string $type) => [
            'name' => 'Quiz', 'status' => Form::PUBLISHED, 'form_type' => $type,
            'fields' => [[
                'label' => 'Pick', 'field_type' => FormFieldType::RADIO,
                'options' => [['label' => 'PHP'], ['label' => 'Laravel']],
                'correct_answer' => 'Laravel',
            ]],
        ];

        // The picker is hidden on a standard form but still posts its value, so
        // the save that switches the type carries the answer with it.
        $form = app(FormBuilderService::class)->save($data(Form::QUIZ));
        $form = app(FormBuilderService::class)->save($data(Form::STANDARD), $form);

        $this->assertFalse($form->isQuiz());
        $this->assertSame('Laravel', $form->fields->first()->correctAnswer());
    }

    public function test_duplicating_a_quiz_keeps_it_a_quiz_with_its_answers(): void
    {
        $form = app(FormBuilderService::class)->save([
            'name' => 'Quiz', 'status' => Form::PUBLISHED, 'form_type' => Form::QUIZ,
            'fields' => [[
                'label' => 'Pick', 'field_type' => FormFieldType::RADIO,
                'options' => [['label' => 'PHP'], ['label' => 'Laravel']], 'correct_answer' => 'PHP',
            ]],
        ]);

        $copy = app(FormBuilderService::class)->duplicate($form);

        $this->assertTrue($copy->isQuiz());
        $this->assertSame('PHP', $copy->fields()->first()->correctAnswer());
    }

    /* =============================== THE SCREEN ============================ */

    public function test_the_builder_offers_bulk_upload_in_every_section_with_a_template_for_each_form_type(): void
    {
        $form = app(FormBuilderService::class)->save([
            'name' => 'Admission', 'status' => Form::PUBLISHED, 'structure_type' => Form::SECTIONS,
            'sections' => ['a' => ['title' => 'One'], 'b' => ['title' => 'Two']],
            'fields'   => [['label' => 'Name', 'field_type' => FormFieldType::SHORT_TEXT, 'section_ref' => 'a']],
        ]);

        $html = $this->asAdmin()->get(route('backend.forms.edit', $form))->assertOk()
            ->assertSee('Bulk Upload Fields')
            ->assertSee('Download Excel Template')
            ->assertSee('value="quiz"', false)
            ->assertSee('value="standard"', false)
            // Both templates are one click away; the script points the link at
            // whichever form type is picked.
            ->assertSee('data-standard="' . route('backend.forms.bulk-template') . '"', false)
            ->assertSee('data-quiz="' . e(route('backend.forms.bulk-template', ['type' => Form::QUIZ])) . '"', false)
            ->getContent();

        // Beside Add Question in each of the two sections (and once more in the
        // section template the script clones).
        $markup = substr($html, 0, strpos($html, '<template'));
        $this->assertSame(2, substr_count($markup, 'data-bulk-open'));

        $this->asAdmin()->get(route('backend.forms.create'))->assertOk()->assertSee('data-bulk-open', false);
    }
}
