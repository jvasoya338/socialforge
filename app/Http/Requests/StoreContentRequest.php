<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canEditContent();
    }

    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'exists:brands,id'],
            'content_source_id' => ['nullable', 'exists:content_sources,id'],
            'external_id' => ['nullable', 'string', 'max:191'],
            'type' => ['required', 'string', 'in:game,article,product,manual,video,announcement,other'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string'],
            'source_url' => ['nullable', 'url', 'max:1024'],
            'featured_image' => ['nullable', 'url', 'max:1024'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
