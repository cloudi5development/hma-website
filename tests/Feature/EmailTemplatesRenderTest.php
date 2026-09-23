<?php

namespace Tests\Feature;

use App\Mail\AdminSubmissionNotification;
use App\Mail\ContactEnquiryThankYou;
use App\Mail\CourseEnquiryThankYou;
use App\Mail\EventRegistrationThankYou;
use App\Mail\FormResponseNotification;
use App\Models\ContactEnquiry;
use App\Models\CourseEnquiry;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Form;
use App\Models\FormResponse;
use App\Services\FormBuilderService;
use App\Support\FormFieldType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every email renders to finished text.
 *
 * A Blade directive written tight against a word - "status@if (...)" - is not
 * compiled at all, so the "@if" and its condition were posted to the reader as
 * literal text. Nothing in the templates catches that: they render without
 * error, just wrongly. These tests read the finished email and refuse any
 * uncompiled Blade in it.
 */
class EmailTemplatesRenderTest extends TestCase
{
    use RefreshDatabase;

    /** Blade that should never survive into a sent email. */
    private function assertFullyRendered(string $html, string $which): void
    {
        foreach (['@if', '@endif', '@foreach', '@endforeach', '@php', '@endphp',
                  '@unless', '@isset', '@section', '@yield', '{{', '}}', '{!!'] as $leak) {
            $this->assertStringNotContainsString(
                $leak, $html, "{$which} printed uncompiled Blade: {$leak}",
            );
        }
    }

    private function contactEnquiry(): ContactEnquiry
    {
        return ContactEnquiry::create([
            'name' => 'Asha Kumar', 'email' => 'asha@example.com', 'phone' => '9876543210',
            'looking_for' => 'Course details', 'message' => 'Call me back.', 'status' => 'New',
        ]);
    }

    /** The one that broke: the files clause is a condition inside a sentence. */
    public function test_the_form_response_email_renders_its_files_clause(): void
    {
        $form = app(FormBuilderService::class)->save([
            'name' => 'Job Application', 'status' => Form::PUBLISHED,
            'submit_label' => 'Apply', 'success_message' => 'Thanks.',
            'fields' => [
                ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Full Name', 'is_required' => 1],
                ['field_type' => FormFieldType::FILE, 'label' => 'Resume', 'is_required' => 0],
            ],
        ]);

        $response = $form->responses()->create(['status' => 'New', 'submitted_at' => now()]);
        $response->values()->create([
            'field_id' => $form->fields->first()->id, 'field_key' => 'full_name',
            'field_label' => 'Full Name', 'field_type' => FormFieldType::SHORT_TEXT,
            'value' => 'Arun Kumar',
        ]);

        $html = (new FormResponseNotification($form, $response->load(FormResponse::WITH_ANSWERS)))->render();

        $this->assertFullyRendered($html, 'The form response email');
        $this->assertStringContainsString('to change its status.', $html);
        $this->assertStringNotContainsString('isFile', $html);
    }

    /** With a file answered, the sentence gains its clause rather than a directive. */
    public function test_the_files_clause_appears_when_a_file_was_uploaded(): void
    {
        $form = app(FormBuilderService::class)->save([
            'name' => 'Job Application', 'status' => Form::PUBLISHED,
            'submit_label' => 'Apply', 'success_message' => 'Thanks.',
            'fields' => [['field_type' => FormFieldType::FILE, 'label' => 'Resume', 'is_required' => 0]],
        ]);

        $response = $form->responses()->create(['status' => 'New', 'submitted_at' => now()]);
        $response->values()->create([
            'field_id' => $form->fields->first()->id, 'field_key' => 'resume',
            'field_label' => 'Resume', 'field_type' => FormFieldType::FILE,
            'value' => 'form-uploads/resume.pdf',
        ]);

        $html = (new FormResponseNotification($form, $response->load(FormResponse::WITH_ANSWERS)))->render();

        $this->assertFullyRendered($html, 'The form response email');
        $this->assertStringContainsString('or download the files attached to it.', $html);
    }

    public function test_the_admin_submission_email_renders(): void
    {
        $enquiry = $this->contactEnquiry();

        $html = (new AdminSubmissionNotification(
            $enquiry, 'New Contact Enquiry', 'New Contact Enquiry — Asha Kumar', url('/admin'),
        ))->render();

        $this->assertFullyRendered($html, 'The admin submission email');
        $this->assertStringContainsString('Asha Kumar', $html);
    }

    public function test_the_visitor_thank_you_emails_render(): void
    {
        $contact = $this->contactEnquiry();

        $course = CourseEnquiry::create([
            'course_name' => 'Full Stack Development', 'name' => 'Ravi', 'email' => 'ravi@example.com',
            'phone' => '9123456780', 'status' => 'New',
        ]);

        $event = Event::create([
            'speaker' => 'R F', 'title' => 'AI Bootcamp', 'type' => 'Live Event',
            'tone' => 'purple', 'image' => 'x.webp', 'is_active' => true,
        ]);
        $registration = EventRegistration::create([
            'event_id' => $event->id, 'event_title' => $event->title, 'name' => 'Alex',
            'email' => 'alex@example.com', 'phone' => '9876543210', 'agreed_terms' => true, 'status' => 'New',
        ]);

        $this->assertFullyRendered((new ContactEnquiryThankYou($contact))->render(), 'The contact thank-you');
        $this->assertFullyRendered((new CourseEnquiryThankYou($course))->render(), 'The course thank-you');
        $this->assertFullyRendered((new EventRegistrationThankYou($registration))->render(), 'The event thank-you');
    }

    /**
     * The templates themselves: a directive tight against a word or digit is
     * silently not compiled, so it must not be written that way anywhere.
     */
    public function test_no_email_template_glues_a_directive_to_a_word(): void
    {
        $directives = 'if|else|elseif|endif|foreach|endforeach|forelse|endforelse|unless|endunless'
                    . '|isset|endisset|empty|endempty|php|endphp|section|endsection|yield|include'
                    . '|switch|case|endswitch|extends|push|endpush';

        foreach (glob(resource_path('views/emails/*.blade.php')) as $template) {
            $source = file_get_contents($template);

            // A comment may describe the trap; code may not commit it.
            $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source);
            $source = preg_replace('#//.*$#m', '', $source);

            $this->assertDoesNotMatchRegularExpression(
                '/[A-Za-z0-9]@(' . $directives . ')\b/',
                $source,
                basename($template) . ' has a Blade directive glued to a word - it will not compile.',
            );
        }
    }
}
