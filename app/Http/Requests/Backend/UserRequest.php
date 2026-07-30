<?php

namespace App\Http\Requests\Backend;

use App\Support\AdminModules;
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
            // Module access. Only keys in the registry are accepted, which also
            // keeps super-admin-only modules (Users) out of reach — they are not
            // in AdminModules::keys().
            'modules'   => ['array'],
            'modules.*' => ['string', Rule::in(AdminModules::keys())],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'        => 'That email address already has an account.',
            'password.confirmed'  => 'The two passwords do not match.',
            'password.min'        => 'Use at least 8 characters.',
            'modules.*.in'        => 'One of the selected modules does not exist.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email'     => trim((string) $this->input('email')),
            'is_active' => $this->boolean('is_active'),
            // No boxes ticked posts nothing at all; normalise that to an empty
            // list so saving actually clears a user's previous access.
            'modules'   => array_values(array_filter((array) $this->input('modules', []), 'is_string')),
        ]);
    }
}
