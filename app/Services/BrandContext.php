<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BrandContext
{
    protected ?Brand $currentBrand = null;

    protected ?Collection $allBrands = null;

    public function setBrand(?Brand $brand): void
    {
        $this->currentBrand = $brand;
    }

    public function current(): ?Brand
    {
        return $this->currentBrand;
    }

    public function id(): ?int
    {
        return $this->currentBrand?->id;
    }

    public function allBrands(): Collection
    {
        if ($this->allBrands === null) {
            $this->allBrands = Brand::query()->orderBy('name')->get();
        }

        return $this->allBrands;
    }

    public function apply(Builder $query, string $column = 'brand_id', ?int $explicitBrandId = null): Builder
    {
        $brandId = $explicitBrandId ?? $this->id();

        if ($brandId !== null) {
            $query->where($column, $brandId);
        }

        return $query;
    }
}
