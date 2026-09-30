<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Services\BrandContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveBrandContext
{
    public function __construct(protected BrandContext $brandContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $brandId = $request->session()->get('selected_brand_id');

        if ($brandId === null && $request->user()?->current_brand_id) {
            $brandId = $request->user()->current_brand_id;
        }

        if ($brandId) {
            $brand = Brand::find($brandId);
            $this->brandContext->setBrand($brand);
        } else {
            $this->brandContext->setBrand(null);
        }

        View::share('currentBrand', $this->brandContext->current());
        View::share('globalBrands', $this->brandContext->allBrands());

        return $next($request);
    }
}
