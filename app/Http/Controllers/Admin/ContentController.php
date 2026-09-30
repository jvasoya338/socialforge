<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContentRequest;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Content;
use App\Models\ContentMedia;
use App\Models\ContentSource;
use App\Models\SocialPlatform;
use App\Services\BrandContext;
use App\Services\Content\ContentImportService;
use App\Services\Content\MediaLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function __construct(
        protected BrandContext $brandContext,
        protected ContentImportService $contentImportService,
        protected MediaLibraryService $mediaLibraryService,
    ) {}

    public function sources(Request $request): View
    {
        $query = $this->brandContext->apply(
            ContentSource::with('brand')->withCount('contents'),
            'brand_id',
            $request->integer('brand_id') ?: null
        );

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sources = $query->latest()->paginate(15)->withQueryString();
        $brands = Brand::orderBy('name')->get();

        return view('admin.content.sources', compact('sources', 'brands'));
    }

    public function storeSource(Request $request): RedirectResponse
    {
        Gate::authorize('manage-system');

        $data = $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'max:40'],
            'base_url' => ['nullable', 'url', 'max:500'],
            'authentication_type' => ['required', 'string', 'max:40'],
        ]);

        $source = ContentSource::create([
            'brand_id' => $data['brand_id'],
            'name' => $data['name'],
            'type' => $data['type'],
            'base_url' => $data['base_url'] ?? null,
            'authentication_type' => $data['authentication_type'],
            'status' => ContentSource::STATUS_ACTIVE,
        ]);

        $rawKey = $source->rotateApiKey();

        ActivityLog::record(
            action: 'content_source.created',
            description: "Created content source '{$source->name}'",
            subject: $source,
            brandId: $source->brand_id,
        );

        return back()
            ->with('success', "Content source '{$source->name}' created.")
            ->with('generated_api_key', $rawKey)
            ->with('generated_source_name', $source->name);
    }

    public function rotateSourceKey(ContentSource $source): RedirectResponse
    {
        Gate::authorize('manage-credentials');

        $rawKey = $source->rotateApiKey();

        ActivityLog::record(
            action: 'content_source.key_rotated',
            description: "Rotated API key for content source '{$source->name}'",
            subject: $source,
            brandId: $source->brand_id,
        );

        return back()
            ->with('success', "Rotated API key for '{$source->name}'. Copy the secret now—it will only be shown once.")
            ->with('generated_api_key', $rawKey)
            ->with('generated_source_name', $source->name);
    }

    public function revokeSourceKey(ContentSource $source): RedirectResponse
    {
        Gate::authorize('manage-credentials');

        $source->revokeApiKey();

        ActivityLog::record(
            action: 'content_source.key_revoked',
            description: "Revoked API key for content source '{$source->name}'",
            subject: $source,
            brandId: $source->brand_id,
        );

        return back()->with('success', "Revoked API key for '{$source->name}'.");
    }

    public function index(Request $request): View
    {
        $query = $this->brandContext->apply(
            Content::with(['brand', 'contentSource'])->withCount(['socialPosts', 'contentMedia']),
            'brand_id',
            $request->integer('brand_id') ?: null
        );

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $contents = $query->latest()->paginate(15)->withQueryString();
        $brands = Brand::orderBy('name')->get();
        $platforms = SocialPlatform::active()->get();

        return view('admin.content.index', compact('contents', 'brands', 'platforms'));
    }

    public function store(StoreContentRequest $request): RedirectResponse
    {
        $brand = Brand::findOrFail($request->validated('brand_id'));

        $result = $this->contentImportService->importContent(
            brand: $brand,
            data: $request->validated(),
        );

        return redirect()->route('admin.content.show', $result['content'])
            ->with('success', "Content '{$result['content']->title}' saved to library.");
    }

    public function show(Content $content): View
    {
        $content->load(['brand', 'contentSource', 'contentMedia', 'socialPosts.socialPlatform', 'aiGenerations.aiProvider']);
        $platforms = SocialPlatform::active()->get();

        return view('admin.content.show', compact('content', 'platforms'));
    }

    public function mediaLibrary(Request $request): View
    {
        $query = $this->brandContext->apply(
            ContentMedia::with(['brand', 'content']),
            'brand_id',
            $request->integer('brand_id') ?: null
        );

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->input('search')) {
            $query->where('original_name', 'like', "%{$search}%");
        }

        $mediaItems = $query->latest()->paginate(18)->withQueryString();
        $brands = Brand::orderBy('name')->get();

        return view('admin.content.media', compact('mediaItems', 'brands'));
    }

    public function uploadMedia(Request $request): RedirectResponse
    {
        Gate::authorize('edit-content');

        $request->validate([
            'brand_id' => ['nullable', 'exists:brands,id'],
            'content_id' => ['nullable', 'exists:contents,id'],
            'file' => ['required', 'file', 'max:20480', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm'],
        ]);

        $this->mediaLibraryService->storeUploadedMedia(
            file: $request->file('file'),
            brandId: $request->integer('brand_id') ?: $this->brandContext->id(),
            contentId: $request->integer('content_id') ?: null,
        );

        return back()->with('success', 'Media file uploaded and validated successfully.');
    }
}
