<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver', 40)->default('openai');
            $table->string('model');
            $table->string('base_url')->nullable();
            $table->json('configuration')->nullable();
            $table->longText('encrypted_api_key')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->integer('priority')->default(10);
            $table->boolean('is_default')->default(false)->index();
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_health_status', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('brand_voice_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->unique()->constrained('brands')->cascadeOnDelete();
            $table->string('tone');
            $table->string('writing_style');
            $table->string('audience')->nullable();
            $table->string('emoji_style')->default('minimal');
            $table->string('cta_style')->default('subtle');
            $table->string('hashtag_style')->default('moderate');
            $table->string('default_language', 10)->default('en');
            $table->json('words_to_avoid')->nullable();
            $table->json('phrases_to_avoid')->nullable();
            $table->json('preferred_phrases')->nullable();
            $table->text('additional_instructions')->nullable();
            $table->boolean('preserve_strict_facts')->default(false);
            $table->timestamps();
        });

        Schema::create('brand_voice_examples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('social_platform_id')->nullable()->constrained('social_platforms')->nullOnDelete();
            $table->text('content');
            $table->string('source', 40)->default('manual');
            $table->boolean('approved')->default(false)->index();
            $table->decimal('performance_score', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('purpose', 50)->index();
            $table->text('system_prompt');
            $table->longText('user_prompt_template');
            $table->integer('version')->default(1);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('content_id')->nullable()->constrained('contents')->nullOnDelete();
            $table->foreignId('social_platform_id')->nullable()->constrained('social_platforms')->nullOnDelete();
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('prompt_template_id')->nullable()->constrained('prompt_templates')->nullOnDelete();
            $table->integer('prompt_version')->default(1);
            $table->string('generation_type', 50)->index();
            $table->longText('input');
            $table->longText('output')->nullable();
            $table->string('model')->nullable();
            $table->integer('input_tokens')->nullable();
            $table->integer('output_tokens')->nullable();
            $table->decimal('estimated_cost', 10, 6)->nullable();
            $table->string('status', 30)->default('completed')->index();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
        Schema::dropIfExists('prompt_templates');
        Schema::dropIfExists('brand_voice_examples');
        Schema::dropIfExists('brand_voice_settings');
        Schema::dropIfExists('ai_providers');
    }
};
