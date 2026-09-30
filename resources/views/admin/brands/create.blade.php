@extends('layouts.admin')

@section('title', 'Add Brand')
@section('page_title', 'Create Brand')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.brands.index') }}">Brands</a></li>
    <li class="breadcrumb-item active">Add Brand</li>
@endsection

@section('content')
<form action="{{ route('admin.brands.store') }}" method="POST">
    @csrf
    <div class="row">
        <!-- Basic Info -->
        <div class="col-md-7">
            <div class="card card-outline card-primary shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">Brand Information</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Brand Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. ApexGear, CloudNexus" required>
                    </div>

                    <div class="form-group">
                        <label for="slug">Slug (Identifier)</label>
                        <input type="text" name="slug" id="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" placeholder="auto-generated from name if left empty">
                        <small class="form-text text-muted">Used for URLs, API mapping, and webhook payloads.</small>
                    </div>

                    <div class="form-group">
                        <label for="website_url">Website URL</label>
                        <input type="url" name="website_url" id="website_url" class="form-control @error('website_url') is-invalid @enderror" value="{{ old('website_url') }}" placeholder="https://example.com">
                    </div>

                    <div class="form-group">
                        <label for="description">Brand Description</label>
                        <textarea name="description" id="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Brief summary of the brand mission, products, or editorial focus">{{ old('description') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="timezone">Timezone <span class="text-danger">*</span></label>
                            <select name="timezone" id="timezone" class="form-control select2" required>
                                <option value="UTC" {{ old('timezone', 'UTC') === 'UTC' ? 'selected' : '' }}>UTC (Coordinated Universal Time)</option>
                                <option value="America/New_York" {{ old('timezone') === 'America/New_York' ? 'selected' : '' }}>America/New_York (EST/EDT)</option>
                                <option value="America/Chicago" {{ old('timezone') === 'America/Chicago' ? 'selected' : '' }}>America/Chicago (CST/CDT)</option>
                                <option value="America/Los_Angeles" {{ old('timezone') === 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles (PST/PDT)</option>
                                <option value="Europe/London" {{ old('timezone') === 'Europe/London' ? 'selected' : '' }}>Europe/London (GMT/BST)</option>
                                <option value="Europe/Paris" {{ old('timezone') === 'Europe/Paris' ? 'selected' : '' }}>Europe/Paris (CET/CEST)</option>
                                <option value="Asia/Tokyo" {{ old('timezone') === 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo (JST)</option>
                                <option value="Asia/Kolkata" {{ old('timezone') === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata (IST)</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="default_language">Default Language <span class="text-danger">*</span></label>
                            <input type="text" name="default_language" id="default_language" class="form-control" value="{{ old('default_language', 'en') }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="status">Initial Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Publishing & AI Settings -->
        <div class="col-md-5">
            <div class="card card-outline card-secondary shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">Publishing & Review Policies</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="approval_required" name="approval_required" value="1" {{ old('approval_required', '1') ? 'checked' : '' }}>
                            <label class="custom-control-label font-weight-bold" for="approval_required">Require Admin Approval</label>
                            <small class="form-text text-muted">All AI drafts must be explicitly approved by an editor before publishing.</small>
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="auto_publish_approved" name="auto_publish_approved" value="1" {{ old('auto_publish_approved') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="auto_publish_approved">Auto-Publish Approved Posts</label>
                            <small class="form-text text-muted">Automatically publish when approved without waiting for manual dispatch.</small>
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="require_strict_factual_check" name="require_strict_factual_check" value="1" {{ old('require_strict_factual_check') ? 'checked' : '' }}>
                            <label class="custom-control-label text-danger font-weight-bold" for="require_strict_factual_check">Strict Factual Safeguards (News/Journalism)</label>
                            <small class="form-text text-muted">Enforces strict non-sensationalism and preserves original dates, names, numbers, quotes, and URLs without exaggeration.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="default_hashtags">Default Brand Hashtags</label>
                        <input type="text" name="default_hashtags" id="default_hashtags" class="form-control" value="{{ old('default_hashtags') }}" placeholder="#BrandName, #Official">
                        <small class="form-text text-muted">Comma-separated list of hashtags automatically appended.</small>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-purple shadow-sm mb-4">
                <div class="card-header">
                    <h3 class="card-title font-weight-bold">Initial Brand Voice</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="tone">Brand Tone</label>
                        <input type="text" name="tone" id="tone" class="form-control" value="{{ old('tone', 'professional, engaging') }}" placeholder="e.g. energetic, gaming-focused OR elegant, luxury">
                    </div>
                    <div class="form-group">
                        <label for="writing_style">Writing Style</label>
                        <textarea name="writing_style" id="writing_style" rows="2" class="form-control" placeholder="Key stylistic attributes, sentence length, and vocabulary focus">{{ old('writing_style', 'Clear, audience-first messaging with active voice.') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.brands.index') }}" class="btn btn-default">Cancel</a>
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-check-circle mr-1"></i> Save Brand</button>
            </div>
        </div>
    </div>
</form>
@endsection
