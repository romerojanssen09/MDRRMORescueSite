<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MobileAuthController extends Controller
{
    private const TOKEN_TTL_SECONDS = 2592000; // 30 days

    /**
     * Register a new citizen or rescuer account directly in Neon.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'role' => 'nullable|in:citizen,rescuer',
            'badge_number' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
        ]);

        $email = strtolower(trim($validated['email']));
        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'This email address is already registered.',
            ], 422);
        }

        $role = $validated['role'] ?? 'citizen';
        $userId = (string) Str::uuid();
        $isVerified = $role === 'citizen';

        DB::transaction(function () use ($userId, $email, $validated, $role, $isVerified) {
            DB::table('users')->insert([
                'id' => $userId,
                'email' => $email,
                'full_name' => trim($validated['full_name']),
                'phone' => $validated['phone'] ?? null,
                'role' => $role,
                'encrypted_password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
                'is_active' => true,
                'is_verified' => $isVerified,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($role === 'rescuer') {
                DB::table('rescuer_profiles')->insert([
                    'id' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'badge_number' => $validated['badge_number'] ?? null,
                    'specialization' => $validated['specialization'] ?? null,
                    'status' => 'available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $user = User::with('rescueTeam:id,team_name')->findOrFail($userId);

        return response()->json([
            'success' => true,
            'token' => self::issueToken($user),
            'user' => $this->formatUser($user),
        ], 201);
    }

    /**
     * Authenticate mobile user against Neon public.users.encrypted_password.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim($validated['email']));
        $user = User::with('rescueTeam:id,team_name')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if (! $user || empty($user->encrypted_password) || ! password_verify($validated['password'], (string) $user->encrypted_password)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid email or password',
            ], 401);
        }

        if ($user->is_active === false) {
            return response()->json([
                'success' => false,
                'error' => 'Your account has been deactivated.',
            ], 403);
        }

        if ($user->role === 'rescuer' && ! $user->is_verified) {
            return response()->json([
                'success' => false,
                'error' => 'Your rescuer account is pending admin approval. Please wait for verification before logging in.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'token' => self::issueToken($user),
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Return fresh user profile for the authenticated bearer token (or user_id query).
     */
    public function me(Request $request)
    {
        $user = self::resolveAuthenticatedUser($request);
        if (! $user && $request->filled('user_id')) {
            $user = User::with('rescueTeam:id,team_name')->find($request->input('user_id'));
        }

        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'success' => true,
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Update mobile user profile fields.
     */
    public function updateProfile(Request $request)
    {
        $user = self::resolveAuthenticatedUser($request);
        if (! $user && $request->filled('user_id')) {
            $user = User::find($request->input('user_id'));
        }

        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'full_name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'avatar_url' => 'nullable|string|max:2048',
        ]);

        $updates = array_merge($validated, ['updated_at' => now()]);
        DB::table('users')->where('id', $user->id)->update($updates);

        $fresh = User::with('rescueTeam:id,team_name')->findOrFail($user->id);

        return response()->json([
            'success' => true,
            'user' => $this->formatUser($fresh),
        ]);
    }

    /**
     * Change password for an authenticated user after verifying current_password.
     */
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
            'email' => 'nullable|email',
        ]);

        $user = self::resolveAuthenticatedUser($request);
        if (! $user && ! empty($validated['email'])) {
            $user = User::whereRaw('LOWER(email) = ?', [strtolower(trim($validated['email']))])->first();
        }

        if (! $user) {
            return response()->json(['success' => false, 'error' => 'User not found'], 404);
        }

        if (empty($user->encrypted_password) || ! password_verify($validated['current_password'], (string) $user->encrypted_password)) {
            return response()->json([
                'success' => false,
                'error' => 'Current password is incorrect',
            ], 422);
        }

        DB::table('users')->where('id', $user->id)->update([
            'encrypted_password' => Hash::make($validated['new_password']),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully',
        ]);
    }

    /**
     * Generate, store, and dispatch a 6-digit OTP code for password reset or change.
     */
    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'purpose' => 'required|in:password_reset,password_change',
        ]);

        $email = strtolower(trim($validated['email']));
        $purpose = $validated['purpose'];

        if (! User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return response()->json([
                'success' => false,
                'error' => 'No account found with that email address.',
            ], 404);
        }

        $otp = (string) random_int(100000, 999999);
        $otpHash = hash('sha256', $otp);

        DB::table('otp_codes')
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->delete();

        DB::table('otp_codes')->insert([
            'id' => (string) Str::uuid(),
            'email' => $email,
            'otp_hash' => $otpHash,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        $supabaseUrl = config('services.supabase.url');
        $supabaseAnonKey = config('services.supabase.anon_key');
        if ($supabaseUrl && $supabaseAnonKey) {
            try {
                $resp = Http::timeout(15)->withHeaders([
                    'apikey' => $supabaseAnonKey,
                    'Authorization' => 'Bearer '.$supabaseAnonKey,
                    'Content-Type' => 'application/json',
                ])->post(rtrim($supabaseUrl, '/').'/functions/v1/send-otp-email-gmail', [
                    'email' => $email,
                    'otp' => $otp,
                    'purpose' => $purpose,
                ]);

                if (! $resp->successful()) {
                    Log::warning('OTP email relay failed', ['status' => $resp->status(), 'body' => $resp->body()]);
                }
            } catch (\Throwable $e) {
                Log::warning('OTP email relay exception: '.$e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email address.',
        ]);
    }

    /**
     * Verify a 6-digit OTP code.
     */
    public function verifyOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'purpose' => 'required|in:password_reset,password_change',
        ]);

        $email = strtolower(trim($validated['email']));
        $record = DB::table('otp_codes')
            ->where('email', $email)
            ->where('purpose', $validated['purpose'])
            ->orderByDesc('created_at')
            ->first();

        if (! $record) {
            return response()->json(['success' => false, 'error' => 'No OTP found for this email'], 404);
        }

        if (now()->gt($record->expires_at)) {
            DB::table('otp_codes')->where('id', $record->id)->delete();

            return response()->json(['success' => false, 'error' => 'OTP has expired. Please request a new one.'], 422);
        }

        if ((int) $record->attempts >= 5) {
            return response()->json(['success' => false, 'error' => 'Too many failed attempts. Please request a new OTP.'], 429);
        }

        if (! hash_equals((string) $record->otp_hash, hash('sha256', $validated['otp']))) {
            DB::table('otp_codes')->where('id', $record->id)->increment('attempts');

            return response()->json(['success' => false, 'error' => 'Invalid OTP code. Please try again.'], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Reset password using email + verified OTP code.
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'new_password' => 'required|string|min:6',
        ]);

        $email = strtolower(trim($validated['email']));
        $record = DB::table('otp_codes')
            ->where('email', $email)
            ->where('purpose', 'password_reset')
            ->orderByDesc('created_at')
            ->first();

        if (! $record || now()->gt($record->expires_at)) {
            return response()->json(['success' => false, 'error' => 'OTP is missing or expired.'], 422);
        }

        if (! hash_equals((string) $record->otp_hash, hash('sha256', $validated['otp']))) {
            DB::table('otp_codes')->where('id', $record->id)->increment('attempts');

            return response()->json(['success' => false, 'error' => 'Invalid OTP code.'], 422);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'User not found.'], 404);
        }

        DB::table('users')->where('id', $user->id)->update([
            'encrypted_password' => Hash::make($validated['new_password']),
            'updated_at' => now(),
        ]);

        DB::table('otp_codes')->where('email', $email)->where('purpose', 'password_reset')->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
        ]);
    }

    public static function issueToken(User $user): string
    {
        $payload = json_encode([
            'sub' => $user->id,
            'role' => $user->role,
            'exp' => time() + self::TOKEN_TTL_SECONDS,
        ]);

        $encoded = rtrim(strtr(base64_encode((string) $payload), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $encoded, (string) config('app.key'));

        return "{$encoded}.{$sig}";
    }

    public static function resolveAuthenticatedUser(Request $request): ?User
    {
        $header = (string) $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = trim(substr($header, 7));
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$encoded, $sig] = $parts;
        $expected = hash_hmac('sha256', $encoded, (string) config('app.key'));
        if (! hash_equals($expected, $sig)) {
            return null;
        }

        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        $data = $decoded ? json_decode($decoded, true) : null;
        if (! is_array($data) || empty($data['sub']) || empty($data['exp']) || (int) $data['exp'] < time()) {
            return null;
        }

        return User::with('rescueTeam:id,team_name')->find($data['sub']);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'role' => $user->role,
            'rescue_team_id' => $user->rescue_team_id,
            'team_name' => $user->rescueTeam?->team_name,
            'rescue_team' => $user->rescueTeam ? ['team_name' => $user->rescueTeam->team_name] : null,
            'is_verified' => (bool) $user->is_verified,
            'is_active' => $user->is_active ?? true,
            'avatar_url' => $user->avatar_url,
            'expo_push_token' => $user->expo_push_token,
            'created_at' => optional($user->created_at)->toISOString(),
            'updated_at' => optional($user->updated_at)->toISOString(),
        ];
    }
}
