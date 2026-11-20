<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $email = strtolower(trim((string) $request->input('email')));
            $password = (string) $request->input('password');
            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            // Primary path: authenticate directly against Neon public.users.encrypted_password
            if ($user && ! empty($user->encrypted_password)) {
                if (! password_verify($password, (string) $user->encrypted_password)) {
                    return back()->withErrors(['error' => 'Invalid email or password'])->withInput($request->only('email'));
                }

                if ($user->is_active === false) {
                    return back()->withErrors(['error' => 'Account is inactive.'])->withInput($request->only('email'));
                }

                if (! $user->isAdmin()) {
                    return back()->withErrors(['error' => 'Access denied. Admin privileges required.']);
                }

                $request->session()->regenerate();
                session([
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_name' => $user->full_name,
                    'user_role' => $user->role,
                ]);

                return redirect()->route('admin.dashboard');
            }

            // Fallback path: if encrypted_password is empty and Supabase Auth is configured
            $supabaseUrl = config('services.supabase.url');
            $supabaseAnonKey = config('services.supabase.anon_key');

            if (! $supabaseUrl || ! $supabaseAnonKey) {
                return back()->withErrors(['error' => 'Invalid email or password'])->withInput($request->only('email'));
            }

            $response = Http::timeout(30)->withHeaders([
                'apikey' => $supabaseAnonKey,
                'Content-Type' => 'application/json',
            ])->post("{$supabaseUrl}/auth/v1/token?grant_type=password", [
                'email' => $email,
                'password' => $password,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $userId = $result['user']['id'] ?? null;

                if (! $userId) {
                    return back()->withErrors(['error' => 'Invalid response from authentication service']);
                }

                $user = User::find($userId);

                if (! $user) {
                    return back()->withErrors(['error' => 'User not found in database']);
                }

                if (! $user->isAdmin()) {
                    return back()->withErrors(['error' => 'Access denied. Admin privileges required.']);
                }

                // Backfill encrypted_password in Neon for future native logins
                $user->encrypted_password = Hash::make($password);
                $user->save();

                $request->session()->regenerate();
                session([
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_name' => $user->full_name,
                    'user_role' => $user->role,
                    'access_token' => $result['access_token'] ?? null,
                ]);

                return redirect()->route('admin.dashboard');
            }

            $errorBody = $response->json();
            $errorMessage = $errorBody['error_description'] ?? $errorBody['message'] ?? 'Invalid email or password';

            return back()->withErrors(['error' => $errorMessage])->withInput($request->only('email'));
        } catch (\Throwable $e) {
            \Log::error('Login error: '.$e->getMessage());

            return back()->withErrors(['error' => 'An error occurred during login. Please try again.']);
        }
    }

    public function logout(Request $request)
    {
        $request->session()->flush();

        return redirect()->route('admin.login');
    }
}
