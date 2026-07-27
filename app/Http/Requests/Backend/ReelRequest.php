<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class ReelRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind the admin.auth middleware.
        return true;
    }

    public function rules(): array
    {
        // On create the video is required; on update it is optional (keep existing).
        $creating = $this->isMethod('post');

        return [
            'title'         => ['required', 'string', 'max:160'],
            'video'         => [$creating ? 'required' : 'nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:40000'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'sort_order'    => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active'  => $this->boolean('is_active'),
            'sort_order' => $this->input('sort_order', 0),
        ]);
    }

    public function messages(): array
    {
        return [
            'video.required'  => 'Please upload the reel video.',
            'video.mimetypes' => 'The video must be an MP4, WebM, OGG or MOV file.',
            'video.max'       => 'The video may not be larger than 40 MB.',
            'instagram_url.url' => 'Enter a full link, e.g. https://www.instagram.com/reel/…',
        ];
    }
}
