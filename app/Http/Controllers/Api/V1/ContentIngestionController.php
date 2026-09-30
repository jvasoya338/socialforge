<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncomingContentApiRequest;
use App\Http\Resources\ContentResource;
use App\Jobs\GenerateSocialContentJob;
use App\Models\Brand;
use App\Models\ContentSource;
use App\Models\SocialPlatform;
use App\Services\Content\ContentImportService;
use Illuminate\Http\JsonResponse;

class ContentIngestionController extends Controller
{
    public function __construct(protected ContentImportService $contentImportService) {}

    public function store(IncomingContentApiRequest $request): JsonResponse
    {
        /** @var Brand $brand */
        $brand = $request->attributes->get('authenticated_brand');
        /** @var ContentSource $source */
        $source = $request->attributes->get('authenticated_content_source');

        $result = $this->contentImportService->importContent(
            brand: $brand,
            data: $request->validated(),
            source: $source,
        );

        $content = $result['content']->load('brand');
        $created = $result['created'];

        if ($request->boolean('auto_generate') && $created) {
            $platformIds = $request->input('platform_ids')
                ?: SocialPlatform::active()->pluck('id')->all();

            if (! empty($platformIds)) {
                GenerateSocialContentJob::dispatch($content->id, $platformIds);
            }
        }

        return response()->json([
            'status' => $created ? 'created' : 'updated',
            'duplicate' => ! $created,
            'data' => new ContentResource($content),
        ], $created ? 201 : 200);
    }
}
