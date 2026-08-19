<?php

namespace App\Http\Requests\Backend;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $id = $this->route('course')?->id;

        return static::columnRules() + [
            'category_id'     => ['required', 'integer', 'exists:categories,id'],
            'slug'            => ['nullable', 'string', 'max:200', 'alpha_dash', Rule::unique('courses', 'slug')->ignore($id)],
            'image'           => [$creating ? 'required' : 'nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:3072'],
            // Brochure PDF. Always optional; 'mimetypes' checks the real file
            // signature, not just the extension, so a renamed .exe is rejected.
            'brochure'        => ['nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
            'remove_brochure' => ['nullable', 'boolean'],
        ];
    }

    /**
     * The rules for the plain course columns — everything that does not depend on
     * the request (no uploaded files, no "is this an edit?" slug exemption, no
     * category lookup).
     *
     * Split out so the bulk uploader can validate a spreadsheet row against the
     * very rules this form applies, instead of keeping a second copy that drifts.
     * A field added here reaches both paths at once. The four request-dependent
     * rules stay in rules() above.
     *
     * @see \App\Services\CourseBulkImportService
     */
    public static function columnRules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:180'],
            'duration'             => ['required', 'string', 'max:60'],
            // No batch start date and no mode: both belong to the batch, and
            // Courses → Schedule is where they are entered.
            'skill_level'          => ['required', Rule::in(Course::SKILL_LEVELS)],
            'rating'               => ['nullable', 'numeric', 'min:0', 'max:5'],

            'short_description'    => ['nullable', 'string', 'max:400'],
            'full_description'     => ['nullable', 'string', 'max:5000'],
            'overview'             => ['nullable', 'string', 'max:5000'],
            'learning_outcomes'    => ['nullable', 'string', 'max:5000'],
            'prerequisites'        => ['nullable', 'string', 'max:2000'],
            'certification'        => ['nullable', 'string', 'max:2000'],
            'audience'             => ['nullable', 'string', 'max:2000'],

            'sort_order'           => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'            => ['nullable', 'boolean'],
            'is_popular'           => ['nullable', 'boolean'],
            'is_continue_learning' => ['nullable', 'boolean'],
            'is_featured'          => ['nullable', 'boolean'],

            'meta_title'           => ['nullable', 'string', 'max:180'],
            'meta_description'     => ['nullable', 'string', 'max:300'],
            'meta_keywords'        => ['nullable', 'string', 'max:255'],

            // FAQ repeater — at most five rows; blank rows are dropped in the controller.
            'faqs'                 => ['nullable', 'array', 'max:' . Course::MAX_FAQS],
            'faqs.*.question'      => ['nullable', 'string', 'max:255'],
            'faqs.*.answer'        => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'            => $this->boolean('is_active'),
            'is_popular'           => $this->boolean('is_popular'),
            'is_continue_learning' => $this->boolean('is_continue_learning'),
            'is_featured'          => $this->boolean('is_featured'),
            'sort_order'           => $this->input('sort_order', 0),
            'remove_brochure'      => $this->boolean('remove_brochure'),
        ]);
    }

    public function messages(): array
    {
        return [
            'image.required'            => 'Please upload a course image.',
            'brochure.mimes'            => 'The brochure must be a PDF file.',
            'brochure.mimetypes'        => 'The brochure must be a PDF file.',
            'brochure.max'              => 'The brochure may not be larger than 10 MB.',
            'category_id.required'      => 'Please choose a category.',
            'faqs.max'                  => 'Maximum ' . Course::MAX_FAQS . ' FAQs allowed.',
        ];
    }
}
