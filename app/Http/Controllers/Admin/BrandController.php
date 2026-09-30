<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Models\ActivityLog;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function __construct(protected BrandService $brandService) {}

    public function index(Request $request): View
    {
        $query = Brand::with(['brandSetting', 'brandVoiceSetting'])
            ->withCount(['socialAccounts', 'contents', 'socialPosts']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $brands = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.brands.index', compact('brands'));
    }

    public function create(): View
    {
        Gate::authorize('create', Brand::class);

        return view('admin.brands.create');
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $brand = $this->brandService->createBrand($request->validated());

        return redirect()->route('admin.brands.show', $brand)
            ->with('success', "Brand '{$brand->name}' created successfully.");
    }

    public function show(Brand $brand): View
    {
        $brand->load(['brandSetting', 'brandVoiceSetting', 'socialAccounts.socialPlatform', 'contentSources']);

        return view('admin.brands.show', compact('brand'));
    }

    public function edit(Brand $brand): View
    {
        Gate::authorize('update', $brand);
        $brand->load(['brandSetting', 'brandVoiceSetting']);

        return view('admin.brands.edit', compact('brand'));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->brandService->updateBrand($brand, $request->validated());

        return redirect()->route('admin.brands.show', $brand)
            ->with('success', "Brand '{$brand->name}' updated successfully.");
    }

    public function toggleStatus(Brand $brand): RedirectResponse
    {
        Gate::authorize('update', $brand);

        $newStatus = $brand->status === Brand::STATUS_ACTIVE
            ? Brand::STATUS_INACTIVE
            : Brand::STATUS_ACTIVE;

        $brand->update(['status' => $newStatus]);

        ActivityLog::record(
            action: 'brand.status_toggled',
            description: "Changed brand '{$brand->name}' status to {$newStatus}",
            subject: $brand,
            brandId: $brand->id,
        );

        return back()->with('success', "Brand '{$brand->name}' is now {$newStatus}.");
    }
}
