<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageCredentials();
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'exists:brands,id'],
            'social_platform_id' => ['required', 'exists:social_platforms,id'],
            'account_name' => ['required', 'string', 'max:150'],
            'username' => ['nullable', 'string', 'max:120'],
            'platform_account_id' => ['nullable', 'string', 'max:150'],
            'account_type' => ['required', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
