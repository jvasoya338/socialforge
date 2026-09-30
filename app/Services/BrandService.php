<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\BrandSetting;
use App\Models\BrandVoiceSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandService
{
    public function createBrand(array $data): Brand
    {
        return DB::transaction(function () use ($data) {
            $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['name']);

            $brand = Brand::create([
                'name' => $data['name'],
                'slug' => $slug,
                'website_url' => $data['website_url'] ?? null,
                'logo' => $data['logo'] ?? null,
                'description' => $data['description'] ?? null,
                'timezone' => $data['timezone'] ?? 'UTC',
                'default_language' => $data['default_language'] ?? 'en',
                'status' => $data['status'] ?? Brand::STATUS_ACTIVE,
                'settings' => $data['settings'] ?? null,
            ]);

            BrandSetting::create([
                'brand_id' => $brand->id,
                'default_timezone' => $brand->timezone,
                'default_language' => $brand->default_language,
                'approval_required' => (bool) ($data['approval_required'] ?? true),
                'auto_publish_approved' => (bool) ($data['auto_publish_approved'] ?? false),
                'require_strict_factual_check' => (bool) ($data['require_strict_factual_check'] ?? false),
                'default_hashtags' => isset($data['default_hashtags'])
                    ? (is_array($data['default_hashtags']) ? $data['default_hashtags'] : array_values(array_filter(array_map('trim', explode(',', (string) $data['default_hashtags'])))))
                    : ['#'.Str::studly($brand->name)],
            ]);

            BrandVoiceSetting::create([
                'brand_id' => $brand->id,
                'tone' => $data['tone'] ?? 'professional, engaging',
                'writing_style' => $data['writing_style'] ?? 'clear and audience-focused',
                'audience' => $data['audience'] ?? 'General audience',
                'emoji_style' => $data['emoji_style'] ?? 'minimal',
                'cta_style' => $data['cta_style'] ?? 'subtle',
                'hashtag_style' => $data['hashtag_style'] ?? 'moderate',
                'default_language' => $brand->default_language,
                'preserve_strict_facts' => (bool) ($data['require_strict_factual_check'] ?? false),
            ]);

            ActivityLog::record(
                action: 'brand.created',
                description: "Created brand '{$brand->name}'",
                subject: $brand,
                brandId: $brand->id,
            );

            return $brand;
        });
    }

    public function updateBrand(Brand $brand, array $data): Brand
    {
        return DB::transaction(function () use ($brand, $data) {
            $brand->update([
                'name' => $data['name'] ?? $brand->name,
                'slug' => ! empty($data['slug']) ? Str::slug($data['slug']) : $brand->slug,
                'website_url' => $data['website_url'] ?? $brand->website_url,
                'description' => $data['description'] ?? $brand->description,
                'timezone' => $data['timezone'] ?? $brand->timezone,
                'default_language' => $data['default_language'] ?? $brand->default_language,
                'status' => $data['status'] ?? $brand->status,
            ]);

            $hashtags = $brand->brandSetting?->default_hashtags ?? [];
            if (array_key_exists('default_hashtags', $data)) {
                $hashtags = is_array($data['default_hashtags'])
                    ? $data['default_hashtags']
                    : array_values(array_filter(array_map('trim', explode(',', (string) $data['default_hashtags']))));
            }

            BrandSetting::query()->updateOrCreate(
                ['brand_id' => $brand->id],
                [
                    'default_timezone' => $brand->timezone,
                    'default_language' => $brand->default_language,
                    'approval_required' => isset($data['approval_required']) ? (bool) $data['approval_required'] : ($brand->brandSetting?->approval_required ?? true),
                    'auto_publish_approved' => isset($data['auto_publish_approved']) ? (bool) $data['auto_publish_approved'] : ($brand->brandSetting?->auto_publish_approved ?? false),
                    'require_strict_factual_check' => isset($data['require_strict_factual_check']) ? (bool) $data['require_strict_factual_check'] : ($brand->brandSetting?->require_strict_factual_check ?? false),
                    'default_hashtags' => $hashtags,
                ]
            );

            ActivityLog::record(
                action: 'brand.updated',
                description: "Updated brand '{$brand->name}'",
                subject: $brand,
                brandId: $brand->id,
            );

            return $brand->fresh(['brandSetting', 'brandVoiceSetting']);
        });
    }
}
