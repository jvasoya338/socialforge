<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 40)->default('api')->index();
            $table->string('base_url')->nullable();
            $table->string('authentication_type', 40)->default('api_key');
            $table->longText('encrypted_credentials')->nullable();
            $table->string('api_key_hash', 64)->nullable()->unique();
            $table->string('api_key_prefix', 24)->nullable();
            $table->timestamp('api_key_last_used_at')->nullable();
            $table->json('configuration')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('content_source_id')->nullable()->constrained('content_sources')->nullOnDelete();
            $table->string('external_id', 191)->nullable();
            $table->string('type', 40)->default('article')->index();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->string('source_url', 1024)->nullable();
            $table->string('featured_image', 1024)->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->timestamps();

            $table->unique(['brand_id', 'external_id']);
            $table->index(['brand_id', 'type', 'status']);
        });

        Schema::create('content_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
            $table->foreignId('content_id')->nullable()->constrained('contents')->cascadeOnDelete();
            $table->string('type', 30)->default('image')->index();
            $table->string('disk', 30)->default('public');
            $table->string('path', 1024);
            $table->string('url', 1024)->nullable();
            $table->string('original_name')->nullable();
            $table->string('thumbnail', 1024)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_media');
        Schema::dropIfExists('contents');
        Schema::dropIfExists('content_sources');
    }
};
