<?php
/**
 * Admin Authentication Diagnostic Script
 * Run this via SSH to check if admin can authenticate
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Admin Authentication Diagnostic ===\n\n";

// Check environment variables
echo "1. Checking Supabase configuration...\n";
$supabaseUrl = env('SUPABASE_URL');
$supabaseAnonKey = env('SUPABASE_ANON_KEY');

if ($supabaseUrl && $supabaseAnonKey) {
    echo "   ✓ Supabase URL: " . $supabaseUrl . "\n";
    echo "   ✓ Supabase Anon Key: " . substr($supabaseAnonKey, 0, 20) . "...\n\n";
} else {
    echo "   ✗ Supabase configuration missing!\n\n";
    exit(1);
}

// Check if admin user exists in database
echo "2. Checking admin user in database...\n";
$adminEmail = 'admin@mdrrmo.com';
$user = \App\Models\User::where('email', $adminEmail)->first();

if ($user) {
    echo "   ✓ Admin user found in database\n";
    echo "   - ID: " . $user->id . "\n";
    echo "   - Email: " . $user->email . "\n";
    echo "   - Role: " . $user->role . "\n";
    echo "   - Is Admin: " . ($user->isAdmin() ? 'Yes' : 'No') . "\n\n";
} else {
    echo "   ✗ Admin user NOT found in database!\n\n";
    exit(1);
}

// Try to authenticate with Supabase
echo "3. Testing Supabase authentication...\n";
$testPassword = 'admin123';

try {
    $response = \Illuminate\Support\Facades\Http::timeout(30)->withHeaders([
        'apikey' => $supabaseAnonKey,
        'Content-Type' => 'application/json',
    ])->post("{$supabaseUrl}/auth/v1/token?grant_type=password", [
        'email' => $adminEmail,
        'password' => $testPassword,
    ]);

    if ($response->successful()) {
        $result = $response->json();
        echo "   ✓ Authentication successful!\n";
        echo "   - User ID from Auth: " . ($result['user']['id'] ?? 'N/A') . "\n";
        echo "   - Access Token: " . substr($result['access_token'] ?? '', 0, 20) . "...\n\n";
        
        // Check if Auth user ID matches database user ID
        $authUserId = $result['user']['id'] ?? null;
        if ($authUserId === $user->id) {
            echo "   ✓ User IDs match!\n\n";
        } else {
            echo "   ✗ User ID mismatch!\n";
            echo "   - Database ID: " . $user->id . "\n";
            echo "   - Auth ID: " . $authUserId . "\n\n";
        }
    } else {
        $errorBody = $response->json();
        echo "   ✗ Authentication failed!\n";
        echo "   - Status: " . $response->status() . "\n";
        echo "   - Error: " . ($errorBody['error_description'] ?? $errorBody['message'] ?? 'Unknown error') . "\n\n";
        
        echo "4. Possible issues:\n";
        echo "   - User not created in Supabase Auth\n";
        echo "   - Password incorrect in Supabase Auth\n";
        echo "   - User email not confirmed in Supabase Auth\n\n";
        
        echo "5. Solution:\n";
        echo "   Go to Supabase Dashboard:\n";
        echo "   https://supabase.com/dashboard/project/kbcdtmnqmismqjtyzmrp\n";
        echo "   → Authentication → Users\n";
        echo "   → Check if admin@mdrrmo.com exists\n";
        echo "   → If not, create user with password: admin123\n";
        echo "   → If exists, reset password to: admin123\n";
        echo "   → Make sure 'Email Confirmed' is checked\n\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error during authentication test: " . $e->getMessage() . "\n\n";
}

echo "=== Diagnostic Complete ===\n";
