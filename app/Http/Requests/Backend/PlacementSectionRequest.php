<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class PlacementSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the admin.auth / admin.module middleware.
        return true;
    }

    public function rules(): array
    {
        return [
            'eyebrow'   => ['nullable', 'string', 'max:120'],
            'title'     => ['required', 'string', 'max:200'],
            'lead'      => ['nullable', 'string', 'max:1000'],
            'note'      => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],

            // Buttons. A link may be a path ("/contact-us"), an anchor on this
            // page ("#placement-cta") or a full URL, so it is checked for shape
            // rather than against a list of routes — but never "javascript:",
            // which is what the pattern below rules out.
            'primary_label'   => ['nullable', 'string', 'max:60'],
            'primary_url'     => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/|#|mailto:|tel:)/i'],
            'secondary_label' => ['nullable', 'string', 'max:60'],
            'secondary_url'   => ['nullable', 'string', 'max:255', 'regex:/^(https?:\/\/|\/|#|mailto:|tel:)/i'],

            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],

            /* ---- The repeater lists, keyed by group. Rows with no title are
                    dropped by the controller, so every box stays nullable. ---- */
            'items'              => ['nullable', 'array'],
            'items.*'            => ['nullable', 'array'],
            'items.*.*.title'    => ['nullable', 'string', 'max:200'],
            'items.*.*.subtitle' => ['nullable', 'string', 'max:80'],
            'items.*.*.text'     => ['nullable', 'string', 'max:2000'],
            'items.*.*.is_active' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title'       => 'heading',
            'lead'        => 'intro text',
            'note'        => 'extra line',
            'primary_url' => 'button link',
            'secondary_url' => 'second button link',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'        => 'Give this section a heading — it is what the page prints at the top of the block.',
            'primary_url.regex'     => 'The button link must start with http://, https://, /, #, mailto: or tel:',
            'secondary_url.regex'   => 'The second button link must start with http://, https://, /, #, mailto: or tel:',
        ];
    }
}
