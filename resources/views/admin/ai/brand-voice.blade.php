@extends('layouts.admin')

@section('title', 'Brand Voice Engine')
@section('page_title', 'Brand Voice & Personality Engine')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.ai.generator') }}">AI</a></li>
    <li class="breadcrumb-item active">Brand Voice</li>
@endsection

@section('content')
<div class="card card-outline card-purple shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-comment-dots text-purple mr-2"></i> Voice Personality Configuration
        </h3>
        <div class="card-tools d-flex">
            <form method="GET" action="{{ route('admin.ai.brand-voice.index') }}" class="form-inline">
                <label class="mr-2 text-sm font-weight-bold">Select Brand:</label>
                <select name="brand_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" {{ $brand && $brand->id === $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if($brand)
        <div class="card-body">
            @php $voice = $brand->brandVoiceSetting; @endphp
            <form action="{{ route('admin.ai.brand-voice.update', $brand) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tone">Brand Tone & Persona <span class="text-danger">*</span></label>
                            <input type="text" name="tone" id="tone" class="form-control" value="{{ old('tone', $voice?->tone) }}" placeholder="e.g. energetic, gaming-focused OR neutral, factual, objective" required>
                        </div>

                        <div class="form-group">
                            <label for="writing_style">Writing Style Guidelines <span class="text-danger">*</span></label>
                            <textarea name="writing_style" id="writing_style" rows="3" class="form-control" placeholder="Sentence length, vocabulary style, narrative perspective" required>{{ old('writing_style', $voice?->writing_style) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="audience">Target Audience</label>
                            <input type="text" name="audience" id="audience" class="form-control" value="{{ old('audience', $voice?->audience) }}" placeholder="Who are we speaking to?">
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="emoji_style">Emoji Style</label>
                                <select name="emoji_style" id="emoji_style" class="form-control">
                                    <option value="none" {{ old('emoji_style', $voice?->emoji_style) === 'none' ? 'selected' : '' }}>None (Strict/News)</option>
                                    <option value="minimal" {{ old('emoji_style', $voice?->emoji_style) === 'minimal' ? 'selected' : '' }}>Minimal (1 max)</option>
                                    <option value="moderate" {{ old('emoji_style', $voice?->emoji_style) === 'moderate' ? 'selected' : '' }}>Moderate (2-3)</option>
                                    <option value="expressive" {{ old('emoji_style', $voice?->emoji_style) === 'expressive' ? 'selected' : '' }}>Expressive</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="cta_style">Call to Action Style</label>
                                <input type="text" name="cta_style" id="cta_style" class="form-control" value="{{ old('cta_style', $voice?->cta_style ?? 'subtle') }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="hashtag_style">Hashtag Style</label>
                                <input type="text" name="hashtag_style" id="hashtag_style" class="form-control" value="{{ old('hashtag_style', $voice?->hashtag_style ?? 'moderate') }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="preserve_strict_facts" name="preserve_strict_facts" value="1" {{ old('preserve_strict_facts', $voice?->preserve_strict_facts) ? 'checked' : '' }}>
                                <label class="custom-control-label text-danger font-weight-bold" for="preserve_strict_facts">Preserve Strict Factual Claims (Do not invent facts or experiences)</label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        @php
                            $wordsAvoid = $voice?->words_to_avoid ? implode(', ', $voice->words_to_avoid) : '';
                            $phrasesAvoid = $voice?->phrases_to_avoid ? implode('; ', $voice->phrases_to_avoid) : '';
                            $prefPhrases = $voice?->preferred_phrases ? implode('; ', $voice->preferred_phrases) : '';
                        @endphp

                        <div class="form-group">
                            <label for="words_to_avoid">Words to Avoid (Comma-separated)</label>
                            <input type="text" name="words_to_avoid" id="words_to_avoid" class="form-control" value="{{ old('words_to_avoid', $wordsAvoid) }}" placeholder="delve, synergy, cheap, shocking">
                            <small class="form-text text-muted">The AI generator and humanization pass will systematically scrub these words.</small>
                        </div>

                        <div class="form-group">
                            <label for="phrases_to_avoid">Phrases to Avoid (Semicolon-separated)</label>
                            <textarea name="phrases_to_avoid" id="phrases_to_avoid" rows="2" class="form-control" placeholder="in today's digital landscape; game-changer; you won't believe">{{ old('phrases_to_avoid', $phrasesAvoid) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="preferred_phrases">Preferred Brand Phrases (Semicolon-separated)</label>
                            <textarea name="preferred_phrases" id="preferred_phrases" rows="2" class="form-control" placeholder="Play instantly in your browser; Crafted for moments that endure">{{ old('preferred_phrases', $prefPhrases) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label for="additional_instructions">Additional Editorial Instructions</label>
                            <textarea name="additional_instructions" id="additional_instructions" rows="2" class="form-control" placeholder="Specific guardrails, compliance rules, or framing instructions">{{ old('additional_instructions', $voice?->additional_instructions) }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-purple btn-block">
                            <i class="fas fa-save mr-1"></i> Save Brand Voice Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>

<!-- Approved Style Examples Section -->
@if($brand)
<div class="card card-outline card-secondary shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-star text-warning mr-2"></i> Approved Style Reference Examples (Few-Shot Prompts)
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addExampleModal">
                <i class="fas fa-plus mr-1"></i> Add Approved Example
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Platform</th>
                        <th>Approved Post Copy Example</th>
                        <th>Approved</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($brand->brandVoiceExamples as $ex)
                        <tr>
                            <td>
                                @if($ex->socialPlatform)
                                    <i class="{{ $ex->socialPlatform->icon }} mr-1"></i> {{ $ex->socialPlatform->name }}
                                @else
                                    <span class="badge badge-light border">Universal</span>
                                @endif
                            </td>
                            <td>{{ $ex->content }}</td>
                            <td>
                                <span class="badge {{ $ex->approved ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $ex->approved ? 'Approved for AI' : 'Draft Example' }}
                                </span>
                            </td>
                            <td class="text-right text-nowrap">
                                <form action="{{ route('admin.ai.brand-voice.examples.toggle', $ex) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-xs {{ $ex->approved ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                        {{ $ex->approved ? 'Disable' : 'Approve' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.ai.brand-voice.examples.delete', $ex) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this style example?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                No style examples added yet. Add approved posts to guide future AI generations.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Example -->
<div class="modal fade" id="addExampleModal" tabindex="-1" role="dialog" aria-labelledby="addExampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form action="{{ route('admin.ai.brand-voice.examples.store', $brand) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="addExampleModalLabel">Add Approved Style Example for {{ $brand->name }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="ex_platform">Target Platform (Optional)</label>
                        <select name="social_platform_id" id="ex_platform" class="form-control">
                            <option value="">Universal (Applies to all platforms)</option>
                            @foreach($platforms as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="ex_content">Post Content Example <span class="text-danger">*</span></label>
                        <textarea name="content" id="ex_content" rows="4" class="form-control" placeholder="Paste an exemplar high-performing or approved post for this brand..." required></textarea>
                    </div>

                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="ex_approved" name="approved" value="1" checked>
                        <label class="custom-control-label" for="ex_approved">Mark Approved as Style Reference for Future Generations</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Example</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
