<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('email')->unique();
            $table->text('full_name');
            $table->text('phone')->nullable();
            $table->text('role')->default('citizen')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_verified')->default(false)->index();
            $table->foreignUuid('rescue_team_id')
                ->nullable()
                ->constrained('rescue_teams')
                ->nullOnDelete();
            $table->text('avatar_url')->nullable();
            $table->text('expo_push_token')->nullable();
            $table->text('encrypted_password')->nullable();
            $table->timestampTz('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestampsTz();

            $table->index(['role', 'is_verified'], 'idx_users_role_verified');
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('citizen', 'rescuer', 'admin'));");
        DB::statement('CREATE INDEX idx_users_expo_push_token ON users (expo_push_token) WHERE expo_push_token IS NOT NULL;');

        Schema::create('rescuer_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('badge_number')->nullable();
            $table->text('department')->nullable();
            $table->text('specialization')->nullable();
            $table->text('status')->default('available')->index();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE rescuer_profiles ADD CONSTRAINT rescuer_profiles_status_check CHECK (status IN ('available', 'on_duty', 'off_duty'));");

        Schema::create('push_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('push_token')->unique();
            $table->text('device_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index(['user_id', 'is_active'], 'idx_push_tokens_user_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_tokens');
        Schema::dropIfExists('rescuer_profiles');
        Schema::dropIfExists('users');
    }
};
