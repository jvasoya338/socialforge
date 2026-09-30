<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:brands,slug'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'timezone' => ['required', 'string', 'timezone:all'],
            'default_language' => ['required', 'string', 'max:10'],
            'status' => ['required', 'in:active,inactive'],
            'approval_required' => ['nullable', 'boolean'],
            'auto_publish_approved' => ['nullable', 'boolean'],
            'require_strict_factual_check' => ['nullable', 'boolean'],
            'default_hashtags' => ['nullable', 'string', 'max:500'],
            'tone' => ['nullable', 'string', 'max:255'],
            'writing_style' => ['nullable', 'string', 'max:500'],
        ];
    }
}
