<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('social_platform_id')->constrained('social_platforms')->cascadeOnDelete();
            $table->string('account_name');
            $table->string('username')->nullable();
            $table->string('platform_account_id')->nullable();
            $table->string('avatar')->nullable();
            $table->string('account_type', 40)->default('page');
            $table->string('status', 20)->default('active')->index();
            $table->string('connection_status', 30)->default('not_connected')->index();
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'social_platform_id']);
        });

        Schema::create('social_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->unique()->constrained('social_accounts')->cascadeOnDelete();
            $table->longText('access_token')->nullable();
            $table->longText('refresh_token')->nullable();
            $table->string('token_type', 40)->default('Bearer');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('refresh_expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->text('last_refresh_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_credentials');
        Schema::dropIfExists('social_accounts');
    }
};
