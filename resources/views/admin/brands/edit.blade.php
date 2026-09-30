@extends('layouts.admin')

@section('title', 'Edit ' . $brand->name)
@section('page_title', 'Edit Brand: ' . $brand->name)

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.brands.index') }}">Brands</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.brands.show', $brand) }}">{{ $brand->name }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<form action="{{ route('admin.brands.update', $brand) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="row">
        <!-- Basic Info -->
        <div class="col-md-7">
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">Brand Details</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $brand->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="slug">Slug (Identifier)</label>
                        <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $brand->slug) }}">
                    </div>

                    <div class="form-group">
                        <label for="website_url">Website URL</label>
                        <input type="url" name="website_url" id="website_url" class="form-control @error('website_url') is-invalid @enderror" value="{{ old('website_url', $brand->website_url) }}">
                    </div>

                    <div class="form-group">
                        <label for="description">Brand Description</label>
                        <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror">{{ old('description', $brand->description) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="timezone">Timezone <span class="text-danger">*</span></label>
                            <input type="text" name="timezone" id="timezone" class="form-control" value="{{ old('timezone', $brand->timezone) }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="default_language">Default Language <span class="text-danger">*</span></label>
                            <input type="text" name="default_language" id="default_language" class="form-control" value="{{ old('default_language', $brand->default_language) }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active" {{ old('status', $brand->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $brand->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings -->
        <div class="col-md-5">
            <div class="card card-outline card-secondary shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">Publishing & Workflow Policies</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="approval_required" name="approval_required" value="1" {{ old('approval_required', $brand->requiresApproval()) ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold" for="approval_required">Require Admin Approval</label>
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="auto_publish_approved" name="auto_publish_approved" value="1" {{ old('auto_publish_approved', $brand->autoPublishesApproved()) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="auto_publish_approved">Auto-Publish Approved Posts</label>
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="require_strict_factual_check" name="require_strict_factual_check" value="1" {{ old('require_strict_factual_check', $brand->requiresStrictFactualCheck()) ? 'checked' : '' }}>
                            <label class="custom-control-label text-danger font-weight-bold" for="require_strict_factual_check">Strict Factual Safeguards (News/Journalism)</label>
                        </div>
                    </div>

                    @php
                        $hashtags = $brand->brandSetting?->default_hashtags ? implode(', ', $brand->brandSetting->default_hashtags) : '';
                    @endphp
                    <div class="form-group">
                        <label for="default_hashtags">Default Brand Hashtags</label>
                        <input type="text" name="default_hashtags" id="default_hashtags" class="form-control" value="{{ old('default_hashtags', $hashtags) }}">
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.brands.show', $brand) }}" class="btn btn-default">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save mr-1"></i> Update Brand</button>
            </div>
        </div>
    </div>
</form>
@endsection
