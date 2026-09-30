<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('website_url')->nullable();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->string('default_language', 10)->default('en');
            $table->string('status', 20)->default('active')->index();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('brand_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->unique()->constrained('brands')->cascadeOnDelete();
            $table->string('default_timezone', 64)->default('UTC');
            $table->string('default_language', 10)->default('en');
            $table->json('posting_preferences')->nullable();
            $table->json('ai_preferences')->nullable();
            $table->boolean('approval_required')->default(true);
            $table->boolean('auto_publish_approved')->default(false);
            $table->boolean('require_strict_factual_check')->default(false);
            $table->json('default_hashtags')->nullable();
            $table->json('url_tracking_settings')->nullable();
            $table->timestamps();
        });

        Schema::create('social_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('fas fa-share-alt');
            $table->string('status', 20)->default('active')->index();
            $table->boolean('supports_text')->default(true);
            $table->boolean('supports_image')->default(true);
            $table->boolean('supports_video')->default(false);
            $table->boolean('supports_reels')->default(false);
            $table->boolean('supports_stories')->default(false);
            $table->boolean('supports_analytics')->default(true);
            $table->boolean('supports_scheduling')->default(true);
            $table->integer('max_text_length')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('editor')->after('password')->index();
            $table->boolean('is_active')->default(true)->after('role');
            $table->foreignId('current_brand_id')->nullable()->after('is_active')->constrained('brands')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('current_brand_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_brand_id');
            $table->dropColumn(['role', 'is_active', 'last_login_at']);
        });

        Schema::dropIfExists('social_platforms');
        Schema::dropIfExists('brand_settings');
        Schema::dropIfExists('brands');
    }
};
