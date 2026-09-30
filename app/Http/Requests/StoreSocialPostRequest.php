<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canEditContent();
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'exists:brands,id'],
            'content_id' => ['nullable', 'exists:contents,id'],
            'social_platform_id' => ['required', 'exists:social_platforms,id'],
            'social_account_id' => ['nullable', 'exists:social_accounts,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'cta' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'url', 'max:1024'],
            'hashtags' => ['nullable', 'string', 'max:500'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
