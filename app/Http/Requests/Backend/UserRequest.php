<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $id = $this->route('user')?->id;

        return [
            'name'      => ['required', 'string', 'max:120'],
            'email'     => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($id)],
            // Optional when editing — leaving it blank keeps the current password.
            'password'  => [$creating ? 'required' : 'nullable', 'string', 'min:8', 'max:72', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'        => 'That email address already has an account.',
            'password.confirmed'  => 'The two passwords do not match.',
            'password.min'        => 'Use at least 8 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email'     => trim((string) $this->input('email')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
