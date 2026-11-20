<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            -- 1. PostGIS geography(Point, 4326) sync trigger
            -- Note: ST_MakePoint expects (longitude, latitude).
            CREATE OR REPLACE FUNCTION sync_location_point()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL THEN
                    NEW.location_point := ST_SetSRID(
                        ST_MakePoint(NEW.longitude::double precision, NEW.latitude::double precision),
                        4326
                    )::geography;
                ELSE
                    NEW.location_point := NULL;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS trg_rescue_teams_location_point ON rescue_teams;
            CREATE TRIGGER trg_rescue_teams_location_point
                BEFORE INSERT OR UPDATE OF latitude, longitude ON rescue_teams
                FOR EACH ROW
                EXECUTE FUNCTION sync_location_point();

            DROP TRIGGER IF EXISTS trg_emergency_reports_location_point ON emergency_reports;
            CREATE TRIGGER trg_emergency_reports_location_point
                BEFORE INSERT OR UPDATE OF latitude, longitude ON emergency_reports
                FOR EACH ROW
                EXECUTE FUNCTION sync_location_point();

            DROP TRIGGER IF EXISTS trg_rescuer_locations_location_point ON rescuer_locations;
            CREATE TRIGGER trg_rescuer_locations_location_point
                BEFORE INSERT OR UPDATE OF latitude, longitude ON rescuer_locations
                FOR EACH ROW
                EXECUTE FUNCTION sync_location_point();

            -- 2. Updated-at trigger for emergency_reports (from mobile migration 1043)
            CREATE OR REPLACE FUNCTION update_updated_at_column()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS update_emergency_reports_updated_at ON emergency_reports;
            CREATE TRIGGER update_emergency_reports_updated_at
                BEFORE UPDATE ON emergency_reports
                FOR EACH ROW
                EXECUTE FUNCTION update_updated_at_column();

            -- 3. Team & rescuer status sync triggers (from mobile migrations 1049 & 1051)
            CREATE OR REPLACE FUNCTION sync_team_member_status()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.status = 'On Mission' AND (OLD.status IS NULL OR OLD.status != 'On Mission') THEN
                    UPDATE rescuer_profiles
                    SET status = 'on_duty', updated_at = NOW()
                    WHERE user_id IN (
                        SELECT id FROM users
                        WHERE rescue_team_id = NEW.id
                        AND role = 'rescuer'
                        AND is_active = true
                    );
                ELSIF NEW.status = 'Available' AND (OLD.status IS NULL OR OLD.status != 'Available') THEN
                    UPDATE rescuer_profiles
                    SET status = 'available', updated_at = NOW()
                    WHERE user_id IN (
                        SELECT id FROM users
                        WHERE rescue_team_id = NEW.id
                        AND role = 'rescuer'
                        AND is_active = true
                    );
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS trigger_sync_team_member_status ON rescue_teams;
            CREATE TRIGGER trigger_sync_team_member_status
                AFTER UPDATE OF status ON rescue_teams
                FOR EACH ROW
                WHEN (OLD.status IS DISTINCT FROM NEW.status)
                EXECUTE FUNCTION sync_team_member_status();

            CREATE OR REPLACE FUNCTION update_team_status_on_report_completion()
            RETURNS TRIGGER AS $$
            BEGIN
                IF (NEW.status IN ('Completed', 'Resolved'))
                   AND (OLD.status IS NULL OR OLD.status NOT IN ('Completed', 'Resolved'))
                   AND NEW.assigned_team_id IS NOT NULL THEN
                    UPDATE rescue_teams
                    SET status = 'Available', updated_at = NOW()
                    WHERE id = NEW.assigned_team_id;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS trigger_update_team_on_report_completion ON emergency_reports;
            CREATE TRIGGER trigger_update_team_on_report_completion
                AFTER UPDATE OF status ON emergency_reports
                FOR EACH ROW
                WHEN (OLD.status IS DISTINCT FROM NEW.status)
                EXECUTE FUNCTION update_team_status_on_report_completion();

            CREATE OR REPLACE FUNCTION update_team_status_on_assignment()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.assigned_team_id IS NOT NULL
                   AND (OLD.assigned_team_id IS NULL OR OLD.assigned_team_id != NEW.assigned_team_id)
                   AND NEW.status NOT IN ('Completed', 'Resolved', 'Cancelled') THEN
                    UPDATE rescue_teams
                    SET status = 'On Mission', updated_at = NOW()
                    WHERE id = NEW.assigned_team_id;
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS trigger_update_team_on_assignment ON emergency_reports;
            CREATE TRIGGER trigger_update_team_on_assignment
                AFTER UPDATE OF assigned_team_id ON emergency_reports
                FOR EACH ROW
                WHEN (OLD.assigned_team_id IS DISTINCT FROM NEW.assigned_team_id)
                EXECUTE FUNCTION update_team_status_on_assignment();

            DROP TRIGGER IF EXISTS trigger_update_team_on_assignment_insert ON emergency_reports;
            CREATE TRIGGER trigger_update_team_on_assignment_insert
                AFTER INSERT ON emergency_reports
                FOR EACH ROW
                WHEN (NEW.assigned_team_id IS NOT NULL AND NEW.status NOT IN ('Completed', 'Resolved', 'Cancelled'))
                EXECUTE FUNCTION update_team_status_on_assignment();

            -- 4. Mobile & Admin RPC functions (from migrations 1017, 1022, 1025, 1045, 1000)
            CREATE OR REPLACE FUNCTION get_active_rescuer_tokens()
            RETURNS TABLE (
                push_token TEXT,
                user_id UUID,
                full_name TEXT,
                role TEXT
            ) AS $$
            BEGIN
                RETURN QUERY
                SELECT
                    pt.push_token::TEXT,
                    u.id AS user_id,
                    u.full_name::TEXT,
                    u.role::TEXT
                FROM push_tokens pt
                INNER JOIN users u ON pt.user_id = u.id
                WHERE u.role IN ('rescuer', 'admin')
                  AND u.is_active = true
                  AND pt.is_active = true;
            END;
            $$ LANGUAGE plpgsql STABLE;

            CREATE OR REPLACE FUNCTION upsert_rescuer_status(
                p_user_id UUID,
                p_status TEXT
            )
            RETURNS void AS $$
            BEGIN
                INSERT INTO rescuer_profiles (user_id, status, created_at, updated_at)
                VALUES (p_user_id, p_status, NOW(), NOW())
                ON CONFLICT (user_id) DO UPDATE
                SET status = EXCLUDED.status,
                    updated_at = NOW();
            END;
            $$ LANGUAGE plpgsql;

            CREATE OR REPLACE FUNCTION upsert_rescuer_location(
                p_rescuer_id UUID,
                p_report_id UUID,
                p_latitude DECIMAL,
                p_longitude DECIMAL,
                p_accuracy DECIMAL DEFAULT NULL,
                p_heading DECIMAL DEFAULT NULL,
                p_speed DECIMAL DEFAULT NULL,
                p_is_active BOOLEAN DEFAULT true
            )
            RETURNS void AS $$
            BEGIN
                INSERT INTO rescuer_locations (
                    rescuer_id, report_id, latitude, longitude,
                    accuracy, heading, speed, is_active, created_at, updated_at
                )
                VALUES (
                    p_rescuer_id, p_report_id, p_latitude, p_longitude,
                    p_accuracy, p_heading, p_speed, p_is_active, NOW(), NOW()
                )
                ON CONFLICT (rescuer_id, report_id) DO UPDATE
                SET latitude = EXCLUDED.latitude,
                    longitude = EXCLUDED.longitude,
                    accuracy = EXCLUDED.accuracy,
                    heading = EXCLUDED.heading,
                    speed = EXCLUDED.speed,
                    is_active = EXCLUDED.is_active,
                    updated_at = NOW();
            END;
            $$ LANGUAGE plpgsql;

            CREATE OR REPLACE FUNCTION get_report_stats()
            RETURNS jsonb AS $$
                SELECT jsonb_build_object(
                    'total_reports', COUNT(*),
                    'pending_reports', COUNT(*) FILTER (WHERE status = 'Pending'),
                    'in_progress_reports', COUNT(*) FILTER (WHERE status = 'In Progress'),
                    'resolved_reports', COUNT(*) FILTER (WHERE status IN ('Completed', 'Resolved')),
                    'cancelled_reports', COUNT(*) FILTER (WHERE status = 'Cancelled')
                )
                FROM emergency_reports;
            $$ LANGUAGE sql STABLE;

            CREATE OR REPLACE FUNCTION get_user_stats()
            RETURNS jsonb AS $$
                SELECT jsonb_build_object(
                    'total_users', (SELECT COUNT(*) FROM users),
                    'total_citizens', (SELECT COUNT(*) FROM users WHERE role = 'citizen'),
                    'total_rescuers', (SELECT COUNT(*) FROM users WHERE role IN ('rescuer', 'admin')),
                    'available_rescuers', (
                        SELECT COUNT(*)
                        FROM rescuer_profiles rp
                        JOIN users u ON rp.user_id = u.id
                        WHERE rp.status = 'available' AND u.is_verified = true
                    )
                );
            $$ LANGUAGE sql STABLE;

            CREATE OR REPLACE FUNCTION get_team_stats()
            RETURNS jsonb AS $$
                SELECT jsonb_build_object(
                    'total_teams', COUNT(*),
                    'available_teams', COUNT(*) FILTER (WHERE status = 'Available'),
                    'on_mission_teams', COUNT(*) FILTER (WHERE status = 'On Mission')
                )
                FROM rescue_teams;
            $$ LANGUAGE sql STABLE;

            CREATE OR REPLACE FUNCTION get_emergency_type_stats()
            RETURNS jsonb AS $$
                SELECT COALESCE(
                    jsonb_object_agg(emergency_type, type_count),
                    '{}'::jsonb
                )
                FROM (
                    SELECT emergency_type, COUNT(*) AS type_count
                    FROM emergency_reports
                    GROUP BY emergency_type
                ) counts;
            $$ LANGUAGE sql STABLE;

            CREATE OR REPLACE FUNCTION cleanup_expired_otps()
            RETURNS void AS $$
                DELETE FROM otp_codes WHERE expires_at < NOW();
            $$ LANGUAGE sql;
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS trigger_update_team_on_assignment_insert ON emergency_reports;
            DROP TRIGGER IF EXISTS trigger_update_team_on_assignment ON emergency_reports;
            DROP TRIGGER IF EXISTS trigger_update_team_on_report_completion ON emergency_reports;
            DROP TRIGGER IF EXISTS trigger_sync_team_member_status ON rescue_teams;
            DROP TRIGGER IF EXISTS update_emergency_reports_updated_at ON emergency_reports;
            DROP TRIGGER IF EXISTS trg_rescuer_locations_location_point ON rescuer_locations;
            DROP TRIGGER IF EXISTS trg_rescue_teams_location_point ON rescue_teams;
            DROP TRIGGER IF EXISTS trg_emergency_reports_location_point ON emergency_reports;
            DROP FUNCTION IF EXISTS cleanup_expired_otps();
            DROP FUNCTION IF EXISTS get_emergency_type_stats();
            DROP FUNCTION IF EXISTS get_team_stats();
            DROP FUNCTION IF EXISTS get_user_stats();
            DROP FUNCTION IF EXISTS get_report_stats();
            DROP FUNCTION IF EXISTS upsert_rescuer_location(UUID, UUID, DECIMAL, DECIMAL, DECIMAL, DECIMAL, DECIMAL, BOOLEAN);
            DROP FUNCTION IF EXISTS upsert_rescuer_status(UUID, TEXT);
            DROP FUNCTION IF EXISTS get_active_rescuer_tokens();
            DROP FUNCTION IF EXISTS update_team_status_on_assignment();
            DROP FUNCTION IF EXISTS update_team_status_on_report_completion();
            DROP FUNCTION IF EXISTS sync_team_member_status();
            DROP FUNCTION IF EXISTS update_updated_at_column();
            DROP FUNCTION IF EXISTS sync_location_point();
        SQL);
    }
};
