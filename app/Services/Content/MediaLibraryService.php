<?php

namespace App\Services\Content;

use App\Models\ActivityLog;
use App\Models\ContentMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class MediaLibraryService
{
    public const ALLOWED_MIMES = [
        'image/jpeg' => ContentMedia::TYPE_IMAGE,
        'image/png' => ContentMedia::TYPE_IMAGE,
        'image/webp' => ContentMedia::TYPE_IMAGE,
        'image/gif' => ContentMedia::TYPE_GIF,
        'video/mp4' => ContentMedia::TYPE_VIDEO,
        'video/quicktime' => ContentMedia::TYPE_VIDEO,
        'video/webm' => ContentMedia::TYPE_VIDEO,
    ];

    public function storeUploadedMedia(
        UploadedFile $file,
        ?int $brandId = null,
        ?int $contentId = null,
        string $disk = 'public'
    ): ContentMedia {
        $realMime = $file->getMimeType() ?: $file->getClientMimeType();

        if (! isset(self::ALLOWED_MIMES[$realMime])) {
            throw new InvalidArgumentException("Unsupported media MIME type: {$realMime}");
        }

        $type = self::ALLOWED_MIMES[$realMime];
        $folder = 'media/'.($brandId ? "brand_{$brandId}" : 'shared').'/'.now()->format('Y/m');
        $path = $file->store($folder, $disk);

        $width = null;
        $height = null;

        if ($type === ContentMedia::TYPE_IMAGE || $type === ContentMedia::TYPE_GIF) {
            $dimensions = @getimagesize($file->getRealPath());
            if (is_array($dimensions)) {
                $width = $dimensions[0] ?? null;
                $height = $dimensions[1] ?? null;
            }
        }

        $media = ContentMedia::create([
            'brand_id' => $brandId,
            'content_id' => $contentId,
            'type' => $type,
            'disk' => $disk,
            'path' => $path,
            'url' => Storage::disk($disk)->url($path),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $realMime,
            'width' => $width,
            'height' => $height,
            'size' => $file->getSize(),
            'sort_order' => 0,
        ]);

        ActivityLog::record(
            action: 'media.uploaded',
            description: "Uploaded media '{$media->original_name}' ({$realMime})",
            subject: $media,
            brandId: $brandId,
        );

        return $media;
    }
}
