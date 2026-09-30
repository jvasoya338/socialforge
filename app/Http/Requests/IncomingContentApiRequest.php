<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IncomingContentApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->get('authenticated_brand') !== null;
    }

    public function rules(): array
    {
        return [
            'external_id' => ['required', 'string', 'max:191'],
            'type' => ['required', 'string', 'in:game,article,product,manual,video,announcement,other'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:5000'],
            'body' => ['nullable', 'string'],
            'url' => ['nullable', 'url', 'max:1024'],
            'source_url' => ['nullable', 'url', 'max:1024'],
            'image' => ['nullable', 'url', 'max:1024'],
            'video' => ['nullable', 'url', 'max:1024'],
            'published_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
            'auto_generate' => ['nullable', 'boolean'],
            'platform_ids' => ['nullable', 'array'],
            'platform_ids.*' => ['integer', 'exists:social_platforms,id'],
        ];
    }
}
