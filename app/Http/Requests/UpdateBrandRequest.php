<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        $brandId = $this->route('brand')?->id ?? $this->route('brand');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('brands', 'slug')->ignore($brandId)],
            'website_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'timezone' => ['required', 'string', 'timezone:all'],
            'default_language' => ['required', 'string', 'max:10'],
            'status' => ['required', 'in:active,inactive'],
            'approval_required' => ['nullable', 'boolean'],
            'auto_publish_approved' => ['nullable', 'boolean'],
            'require_strict_factual_check' => ['nullable', 'boolean'],
            'default_hashtags' => ['nullable', 'string', 'max:500'],
        ];
    }
}
