<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('content_id')->nullable()->constrained('contents')->nullOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->foreignId('social_platform_id')->constrained('social_platforms')->cascadeOnDelete();
            $table->foreignId('ai_generation_id')->nullable()->constrained('ai_generations')->nullOnDelete();
            $table->string('title')->nullable();
            $table->longText('body');
            $table->json('hashtags')->nullable();
            $table->string('cta')->nullable();
            $table->string('link_url', 1024)->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->string('approval_status', 30)->default('pending')->index();
            $table->string('publish_mode', 30)->default('scheduled');
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('platform_post_id')->nullable();
            $table->string('platform_post_url', 1024)->nullable();
            $table->string('failure_category', 40)->nullable()->index();
            $table->text('last_error_message')->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'status']);
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('social_post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('content_media_id')->nullable()->constrained('content_media')->nullOnDelete();
            $table->string('type', 30)->default('image');
            $table->string('path', 1024);
            $table->string('thumbnail', 1024)->nullable();
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('publishing_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->integer('attempt_number')->default(1);
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->integer('http_status')->nullable();
            $table->string('platform_post_id')->nullable();
            $table->string('platform_post_url', 1024)->nullable();
            $table->json('response_data')->nullable();
            $table->string('failure_category', 40)->nullable()->index();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->string('status', 30)->default('failed')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publishing_attempts');
        Schema::dropIfExists('social_post_media');
        Schema::dropIfExists('social_posts');
    }
};
