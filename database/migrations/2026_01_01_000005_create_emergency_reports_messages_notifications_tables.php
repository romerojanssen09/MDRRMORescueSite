<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_reports', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('citizen_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignUuid('assigned_rescuer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignUuid('assigned_team_id')
                ->nullable()
                ->constrained('rescue_teams')
                ->nullOnDelete();
            $table->text('emergency_type')->index();
            $table->text('description')->nullable();
            $table->text('location');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('status')->default('Pending')->index();
            $table->text('photo_url')->nullable();
            $table->text('image_url')->nullable();
            $table->boolean('show_name')->default(true);
            $table->text('device_id')->nullable()->index();
            $table->text('guest_name')->nullable();
            $table->text('guest_contact')->nullable();
            $table->boolean('is_guest_report')->default(false);
            $table->timestampsTz();

            $table->index(['status', 'created_at'], 'idx_emergency_reports_status_created');
            $table->index(['device_id', 'created_at'], 'idx_emergency_reports_device_created');
            $table->index(['citizen_id', 'device_id', 'created_at'], 'idx_emergency_reports_citizen_device_created');
        });

        DB::statement("ALTER TABLE emergency_reports ADD CONSTRAINT emergency_reports_status_check CHECK (status IN ('Pending', 'In Progress', 'Completed', 'Resolved', 'Cancelled'));");
        DB::statement('ALTER TABLE emergency_reports ADD COLUMN location_point geography(Point, 4326);');
        DB::statement('CREATE INDEX emergency_reports_location_point_gist_idx ON emergency_reports USING GIST (location_point);');

        Schema::create('rescue_operations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('emergency_report_id')
                ->nullable()
                ->constrained('emergency_reports')
                ->cascadeOnDelete();
            $table->foreignUuid('rescue_team_id')
                ->nullable()
                ->constrained('rescue_teams')
                ->nullOnDelete();
            $table->text('status')->default('Assigned');
            $table->text('notes')->nullable();
            $table->timestampTz('started_at')->useCurrent();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();

            $table->index(['emergency_report_id', 'status'], 'idx_rescue_operations_report_status');
        });

        DB::statement("ALTER TABLE rescue_operations ADD CONSTRAINT rescue_operations_status_check CHECK (status IN ('Assigned', 'En Route', 'On Site', 'Completed'));");

        Schema::create('rescuer_locations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('rescuer_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignUuid('report_id')
                ->constrained('emergency_reports')
                ->cascadeOnDelete();
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->decimal('heading', 5, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['rescuer_id', 'report_id'], 'rescuer_locations_rescuer_report_unique');
            $table->index(['rescuer_id', 'is_active'], 'idx_rescuer_locations_rescuer_active');
            $table->index('created_at', 'idx_rescuer_locations_created_at');
        });

        DB::statement('ALTER TABLE rescuer_locations ADD COLUMN location_point geography(Point, 4326);');
        DB::statement('CREATE INDEX rescuer_locations_location_point_gist_idx ON rescuer_locations USING GIST (location_point);');

        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('report_id')
                ->constrained('emergency_reports')
                ->cascadeOnDelete();
            $table->foreignUuid('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestampsTz();

            $table->index(['report_id', 'created_at'], 'idx_messages_report_created');
        });

        Schema::create('message_reads', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('message_id')
                ->constrained('messages')
                ->cascadeOnDelete();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestampTz('read_at')->useCurrent();
            $table->timestampsTz();

            $table->unique(['message_id', 'user_id'], 'message_reads_unique_user_message');
            $table->index('read_at', 'idx_message_reads_read_at');
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignUuid('report_id')
                ->nullable()
                ->constrained('emergency_reports')
                ->cascadeOnDelete();
            $table->foreignUuid('related_report_id')
                ->nullable()
                ->constrained('emergency_reports')
                ->nullOnDelete();
            $table->text('title');
            $table->text('body');
            $table->text('type')->default('info')->index();
            $table->jsonb('data')->default(DB::raw("'{}'::jsonb"));
            $table->boolean('is_read')->default(false);
            $table->boolean('read')->default(false);
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();

            $table->index(['user_id', 'read'], 'idx_user_notifications_user_read');
            $table->index(['user_id', 'is_read'], 'idx_user_notifications_user_is_read');
            $table->index('created_at', 'idx_user_notifications_created');
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('report_id')
                ->nullable()
                ->constrained('emergency_reports')
                ->cascadeOnDelete();
            $table->foreignUuid('recipient_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('notification_type')->index();
            $table->text('title');
            $table->text('body');
            $table->timestampTz('sent_at')->useCurrent();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();

            $table->index(['report_id', 'sent_at'], 'idx_notification_logs_report_sent');
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('email')->index();
            $table->text('otp_hash');
            $table->text('purpose');
            $table->timestampTz('expires_at')->index();
            $table->integer('attempts')->default(0);
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['email', 'expires_at'], 'idx_otp_codes_email_expires');
            $table->index(['email', 'purpose'], 'idx_otp_codes_email_purpose');
        });

        DB::statement("ALTER TABLE otp_codes ADD CONSTRAINT otp_codes_purpose_check CHECK (purpose IN ('password_reset', 'password_change'));");
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('message_reads');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('rescuer_locations');
        Schema::dropIfExists('rescue_operations');
        Schema::dropIfExists('emergency_reports');
    }
};
