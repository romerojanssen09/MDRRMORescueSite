<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminEmail = 'admin@mdrrmo.com';
        $adminPassword = 'admin123';

        // Check if admin already exists
        $adminExists = User::where('email', $adminEmail)->exists();

        if ($adminExists) {
            $this->command->info('Admin user already exists.');
            return;
        }

        try {
            $supabaseUrl = config('services.supabase.url');
            $supabaseAnonKey = config('services.supabase.anon_key');

            if (!$supabaseUrl || !$supabaseAnonKey) {
                $this->command->error('Supabase configuration missing in .env file');
                return;
            }

            $this->command->info('Creating admin user in Supabase Auth...');

            // Create user in Supabase Auth
            $response = Http::timeout(30)->withHeaders([
                'apikey' => $supabaseAnonKey,
                'Content-Type' => 'application/json',
            ])->post("{$supabaseUrl}/auth/v1/signup", [
                'email' => $adminEmail,
                'password' => $adminPassword,
                'data' => [
                    'full_name' => 'MDRRMO Administrator',
                    'phone' => '+639123456789',
                    'role' => 'admin',
                ]
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $userId = $result['user']['id'] ?? null;

                if (!$userId) {
                    $this->command->error('User ID not returned from Supabase');
                    return;
                }

                // Check if user already exists in public.users (might be created by trigger)
                $existingUser = DB::table('users')->where('id', $userId)->first();
                
                if (!$existingUser) {
                    // Create user in public.users table
                    DB::table('users')->insert([
                        'id' => $userId,
                        'email' => $adminEmail,
                        'full_name' => 'MDRRMO Administrator',
                        'phone' => '+639123456789',
                        'role' => 'admin',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    // Update existing user to admin role
                    DB::table('users')->where('id', $userId)->update([
                        'role' => 'admin',
                        'full_name' => 'MDRRMO Administrator',
                        'phone' => '+639123456789',
                        'updated_at' => now(),
                    ]);
                    $this->command->info('User already existed in database, updated to admin role.');
                }

                $this->command->info('✓ Admin user created successfully!');
                $this->command->info('');
                $this->command->info('Login Credentials:');
                $this->command->info('  Email: ' . $adminEmail);
                $this->command->info('  Password: ' . $adminPassword);
            } else {
                $errorBody = $response->json();
                $errorMessage = $errorBody['msg'] ?? $errorBody['message'] ?? $errorBody['error_description'] ?? 'Unknown error';
                $this->command->error('Failed to create admin user: ' . $errorMessage);
            }
        } catch (\Exception $e) {
            $this->command->error('Error creating admin user: ' . $e->getMessage());
        }
    }
}
