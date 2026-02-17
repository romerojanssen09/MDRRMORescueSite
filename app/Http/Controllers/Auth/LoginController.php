<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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
            $supabaseUrl = env('SUPABASE_URL');
            $supabaseAnonKey = env('SUPABASE_ANON_KEY');

            if (!$supabaseUrl || !$supabaseAnonKey) {
                return back()->withErrors(['error' => 'Authentication service not configured']);
            }

            // Authenticate with Supabase
            $response = Http::timeout(30)->withHeaders([
                'apikey' => $supabaseAnonKey,
                'Content-Type' => 'application/json',
            ])->post("{$supabaseUrl}/auth/v1/token?grant_type=password", [
                'email' => $request->email,
                'password' => $request->password,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $userId = $result['user']['id'] ?? null;

                if (!$userId) {
                    return back()->withErrors(['error' => 'Invalid response from authentication service']);
                }

                // Get user from database
                $user = \App\Models\User::find($userId);

                if (!$user) {
                    return back()->withErrors(['error' => 'User not found in database']);
                }

                // Check if user is admin
                if (!$user->isAdmin()) {
                    return back()->withErrors(['error' => 'Access denied. Admin privileges required.']);
                }

                // Store user session
                session([
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_name' => $user->full_name,
                    'user_role' => $user->role,
                    'access_token' => $result['access_token'],
                ]);

                return redirect()->route('admin.dashboard');
            } else {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error_description'] ?? $errorBody['message'] ?? 'Invalid email or password';
                
                return back()->withErrors(['error' => $errorMessage])->withInput($request->only('email'));
            }
        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'An error occurred during login. Please try again.']);
        }
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect()->route('admin.login');
    }
}
