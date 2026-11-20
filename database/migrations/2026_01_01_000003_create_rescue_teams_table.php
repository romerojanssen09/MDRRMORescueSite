<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rescue_teams', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->text('team_name');
            $table->text('specialization');
            $table->text('status')->default('Available')->index();
            $table->text('province')->nullable();
            $table->text('municipality')->nullable();
            $table->text('barangay')->nullable();
            $table->text('street_address')->nullable();
            $table->text('contact_number')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('members_count')->default(0);
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE rescue_teams ADD CONSTRAINT rescue_teams_status_check CHECK (status IN ('Available', 'On Mission', 'Off Duty'));");
        DB::statement('ALTER TABLE rescue_teams ADD COLUMN location_point geography(Point, 4326);');
        DB::statement('CREATE INDEX rescue_teams_location_point_gist_idx ON rescue_teams USING GIST (location_point);');
    }

    public function down(): void
    {
        Schema::dropIfExists('rescue_teams');
    }
};
