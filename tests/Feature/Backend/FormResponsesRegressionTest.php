<?php

namespace Tests\Feature\Backend;

use App\Models\Form;
use App\Models\FormResponse;
use App\Models\User;
use App\Services\FormBuilderService;
use App\Support\FormFieldType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Regressions on the responses side: the listings, their filters, and the two
 * exports — the file an admin actually takes away.
 */
class FormResponsesRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function signedIn(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession(['admin_logged_in' => true, 'admin_id' => $admin->id]);
    }

    private function form(): Form
    {
        return app(FormBuilderService::class)->save([
            'name'   => 'Enquiry',
            'status' => Form::PUBLISHED,
            'fields' => [
                ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Name', 'is_required' => 0],
                ['field_type' => FormFieldType::MOBILE, 'label' => 'Phone', 'is_required' => 0],
            ],
        ]);
    }

    private function submit(Form $form, array $answers): void
    {
        $this->post(route('frontend.form.submit', $form->slug), $answers)->assertSessionHasNoErrors();
    }

    /** The rows of an .xlsx download, cell by cell, with their stored types. */
    private function xlsx($response): array
    {
        $path = $response->baseResponse->getFile()->getPathname();
        $rows = [];

        foreach (IOFactory::load($path)->getActiveSheet()->getRowIterator() as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = [$cell->getValue(), $cell->getDataType()];
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    /* =============================== EXPORTS =============================== */

    /**
     * chunkById kept the listing's newest-first order while paging on "id >
     * last id", so every chunk after the first re-read the newest rows. 1,200
     * responses exported as 999 rows — 499 of them twice, the oldest 700 gone.
     */
    public function test_an_export_of_more_than_one_chunk_has_every_response_once(): void
    {
        $form  = $this->form();
        $field = $form->fields->firstWhere('field_key', 'name');
        $now   = now();

        for ($n = 1; $n <= 1100; $n++) {
            $id = DB::table('form_responses')->insertGetId([
                'form_id' => $form->id, 'status' => 'New', 'submitted_at' => $now->copy()->subMinutes(1100 - $n),
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('form_response_values')->insert([
                'response_id' => $id, 'field_id' => $field->id, 'field_key' => 'name', 'field_label' => 'Name',
                'field_type' => FormFieldType::SHORT_TEXT, 'value' => sprintf('Person %04d', $n), 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $csv = $this->signedIn()->get(route('backend.forms.responses.export', $form))->streamedContent();
        preg_match_all('/Person \d{4}/', $csv, $m);

        $this->assertCount(1100, $m[0]);
        $this->assertCount(1100, array_unique($m[0]));
        $this->assertSame('Person 1100', $m[0][0], 'newest first');
        $this->assertSame('Person 0001', end($m[0]));
    }

    /**
     * Every answer is typed by the public. =HYPERLINK(...) in the file ran as
     * a formula when the admin opened it; the CSV had no byte-order mark, so
     * Excel garbled anything outside ASCII.
     */
    public function test_the_csv_is_safe_to_open_and_reads_as_utf8(): void
    {
        $form = $this->form();
        $this->submit($form, ['name' => '=HYPERLINK("http://evil.example","x")', 'phone' => '+91 98765 43210']);
        $this->submit($form, ['name' => 'Prénom Ñandú ₹', 'phone' => '9876543210']);
        // A phone number typed into a TEXT answer keeps its "+".
        $this->submit($form, ['name' => '+91 98765 43210']);

        $csv = $this->signedIn()->get(route('backend.forms.responses.export', $form))->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertDoesNotMatchRegularExpression('/(^|,)"?=HYPERLINK/m', $csv);
        $this->assertStringContainsString('+91 98765 43210', $csv, 'a phone number is not something to neutralise');
        $this->assertStringNotContainsString("'+91", $csv);
        $this->assertStringContainsString('9876543210', $csv, 'the mobile answer, as its ten digits');
        $this->assertStringContainsString('Prénom Ñandú ₹', $csv);
    }

    /** The .xlsx stored =… answers as live formulas, and "+91…" as a mangled number. */
    public function test_the_xlsx_stores_answers_as_text(): void
    {
        $form = $this->form();
        $this->submit($form, ['name' => '=1+1', 'phone' => '9876543210']);
        $this->submit($form, ['name' => '+919876543210']);

        $rows = $this->xlsx($this->signedIn()->get(route('backend.forms.responses.export-excel', $form)));

        // Newest first: the "+91…" text answer, then the formula-looking one.
        [$id, $plus] = $rows[1];
        [, $formula, $phone] = $rows[2];

        $this->assertSame(['=1+1', DataType::TYPE_STRING], $formula);
        $this->assertSame(['+919876543210', DataType::TYPE_STRING], $plus);
        $this->assertSame('9876543210', (string) $phone[0]);
        $this->assertSame(DataType::TYPE_NUMERIC, $id[1], 'a plain number is still a number');
    }

    /** A question removed while it held answers is soft-deleted so those answers survive — the export dropped them. */
    public function test_the_export_keeps_answers_to_a_removed_question(): void
    {
        $form = $this->form();
        $this->submit($form, ['name' => 'Arun', 'phone' => '9876543210']);

        // Remove "Phone" through the builder.
        app(FormBuilderService::class)->save([
            'name' => 'Enquiry', 'status' => Form::PUBLISHED,
            'fields' => [['id' => $form->fields->firstWhere('label', 'Name')->id, 'field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Name']],
        ], $form);

        $csv = $this->signedIn()->get(route('backend.forms.responses.export', $form))->streamedContent();

        $this->assertStringContainsString('Phone (removed)', $csv);
        $this->assertStringContainsString('9876543210', $csv);

        $this->signedIn()->get(route('backend.forms.responses.show', [$form, FormResponse::first()]))
            ->assertSee('(field since removed)');
    }

    /* =============================== FILTERS =============================== */

    /** ?q[]=x or ?status[]=x on a hand-edited URL was a server error on three screens. */
    public function test_array_filters_are_read_as_no_filter(): void
    {
        $form = $this->form();

        foreach (['q[]=x', 'status[]=x', 'date[]=x', 'per_page[]=5'] as $qs) {
            $this->signedIn()->get(route('backend.forms.responses.index', $form) . '?' . $qs)->assertOk();
            $this->signedIn()->get(route('backend.forms.all-responses') . '?' . $qs)->assertOk();
            $this->signedIn()->get(route('backend.forms.index') . '?' . $qs)->assertOk();
            $this->signedIn()->get(route('backend.forms.responses.export', $form) . '?' . $qs)->assertOk();
        }
    }

    /** The toolbar always sends q= and status=, empty — that is not a filter. */
    public function test_delete_filtered_appears_only_when_something_is_filtered(): void
    {
        $form = $this->form();
        $this->submit($form, ['name' => 'Arun']);

        $this->signedIn()->get(route('backend.forms.responses.index', $form) . '?q=&status=&per_page=25')
            ->assertSee('Clear All')->assertDontSee('Delete Filtered');

        $this->signedIn()->get(route('backend.forms.responses.index', $form) . '?q=Arun')
            ->assertSee('Delete Filtered');

        $this->signedIn()->get(route('backend.forms.all-responses') . '?q=&status=')
            ->assertSee('Clear All')->assertDontSee('Delete Filtered');
    }

    /** "Export This Form" on the all-responses screen exported everything, whatever was filtered. */
    public function test_export_this_form_carries_the_filters_on_screen(): void
    {
        $form = $this->form();
        $this->submit($form, ['name' => 'Arun']);

        $html = $this->signedIn()->get(route('backend.forms.all-responses') . '?form=' . $form->id . '&q=Arun&status=New')->getContent();

        preg_match('#href="([^"]*export-excel[^"]*)"#', $html, $m);
        $link = html_entity_decode($m[1] ?? '');

        $this->assertStringContainsString('q=Arun', $link);
        $this->assertStringContainsString('status=New', $link);
    }

    /** Filtering by day kept the search and status the toolbar had set. */
    public function test_the_date_filter_keeps_the_search(): void
    {
        $form = $this->form();

        $this->signedIn()->get(route('backend.forms.responses.index', $form) . '?q=Arun&status=New')
            ->assertSee('<input type="hidden" name="q" value="Arun">', false)
            ->assertSee('<input type="hidden" name="status" value="New">', false);
    }
}
