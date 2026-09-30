<?php

namespace App\Services\Content;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Content;
use App\Models\ContentMedia;
use App\Models\ContentSource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContentImportService
{
    /**
     * Import or update a content item for a brand with duplicate protection on [brand_id, external_id].
     *
     * @return array{content: Content, created: bool}
     */
    public function importContent(Brand $brand, array $data, ?ContentSource $source = null): array
    {
        return DB::transaction(function () use ($brand, $data, $source) {
            $externalId = isset($data['external_id']) && $data['external_id'] !== ''
                ? (string) $data['external_id']
                : null;

            $existing = null;
            if ($externalId !== null) {
                $existing = Content::query()
                    ->where('brand_id', $brand->id)
                    ->where('external_id', $externalId)
                    ->first();
            }

            $publishedAt = ! empty($data['published_at'])
                ? Carbon::parse($data['published_at'])->utc()
                : now();

            $attributes = [
                'brand_id' => $brand->id,
                'content_source_id' => $source?->id ?? ($data['content_source_id'] ?? null),
                'external_id' => $externalId,
                'type' => $data['type'] ?? Content::TYPE_ARTICLE,
                'title' => $data['title'],
                'slug' => $data['slug'] ?? Str::slug($data['title']),
                'summary' => $data['summary'] ?? ($data['description'] ?? null),
                'body' => $data['body'] ?? ($data['description'] ?? null),
                'source_url' => $data['source_url'] ?? ($data['url'] ?? null),
                'featured_image' => $data['featured_image'] ?? ($data['image'] ?? null),
                'published_at' => $publishedAt,
                'metadata' => $data['metadata'] ?? null,
                'status' => $existing ? $existing->status : ($data['status'] ?? Content::STATUS_READY),
            ];

            if ($existing) {
                $existing->update($attributes);
                $content = $existing->fresh();
                $created = false;
            } else {
                $content = Content::create($attributes);
                $created = true;
            }

            if (! empty($data['image'])) {
                ContentMedia::query()->firstOrCreate(
                    [
                        'content_id' => $content->id,
                        'path' => $data['image'],
                    ],
                    [
                        'brand_id' => $brand->id,
                        'type' => ContentMedia::TYPE_IMAGE,
                        'url' => $data['image'],
                        'sort_order' => 0,
                    ]
                );
            }

            if (! empty($data['video'])) {
                ContentMedia::query()->firstOrCreate(
                    [
                        'content_id' => $content->id,
                        'path' => $data['video'],
                    ],
                    [
                        'brand_id' => $brand->id,
                        'type' => ContentMedia::TYPE_VIDEO,
                        'url' => $data['video'],
                        'sort_order' => 1,
                    ]
                );
            }

            if ($source) {
                $source->update(['last_synced_at' => now()]);
            }

            ActivityLog::record(
                action: $created ? 'content.imported' : 'content.updated',
                description: ($created ? 'Imported' : 'Updated')." content '{$content->title}' for {$brand->name}",
                subject: $content,
                brandId: $brand->id,
                properties: ['type' => $content->type, 'external_id' => $content->external_id],
            );

            return [
                'content' => $content,
                'created' => $created,
            ];
        });
    }
}
