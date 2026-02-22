<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::select('id', 'full_name', 'email', 'phone', 'role', 'rescue_team_id', 'created_at');

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('full_name', 'ilike', '%' . $request->search . '%')
                  ->orWhere('email', 'ilike', '%' . $request->search . '%')
                  ->orWhere('phone', 'ilike', '%' . $request->search . '%');
            });
        }

        $users = $query->latest()->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        // No need to show role selection - all created users are rescuers
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|regex:/^[0-9]{11}$/',
            'password' => 'required|min:8|confirmed',
        ], [
            'phone.regex' => 'Phone number must be exactly 11 digits.',
        ]);

        try {
            $supabaseUrl = config('services.supabase.url');
            $supabaseAnonKey = config('services.supabase.anon_key');
            
            \Log::info('Creating user via Supabase Auth API (mobile app pattern)', [
                'email' => $validated['email'],
            ]);
            
            if (!$supabaseUrl || !$supabaseAnonKey) {
                throw new \Exception('Supabase configuration missing');
            }
            
            // Use the same pattern as mobile app: supabase.auth.signUp()
            // This calls Supabase Auth API directly
            $response = Http::timeout(30)->withHeaders([
                'apikey' => $supabaseAnonKey,
                'Content-Type' => 'application/json',
            ])->post("{$supabaseUrl}/auth/v1/signup", [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'data' => [
                    'full_name' => $validated['full_name'],
                    'phone' => $validated['phone'] ?? null,
                    'role' => 'rescuer',
                ]
            ]);

            \Log::info('Supabase Auth Response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $userId = $result['user']['id'] ?? null;
                
                if (!$userId) {
                    throw new \Exception('User ID not returned from Supabase');
                }
                
                // Check if user already exists in public.users (might be created by trigger)
                $existingUser = DB::table('users')->where('id', $userId)->first();
                
                if (!$existingUser) {
                    // Create the user in public.users table only if it doesn't exist
                    DB::table('users')->insert([
                        'id' => $userId,
                        'email' => $validated['email'],
                        'full_name' => $validated['full_name'],
                        'phone' => $validated['phone'] ?? null,
                        'role' => 'rescuer',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    \Log::info('User already exists in public.users (created by trigger)', [
                        'user_id' => $userId
                    ]);
                }
                
                \Log::info('User created successfully', [
                    'email' => $validated['email'],
                    'user_id' => $userId
                ]);
                
                // Redirect back without old input to ensure form is cleared
                return redirect()->route('admin.users.create')
                    ->with('success', 'Rescuer created successfully! You can create another one.')
                    ->with('clear_form', true);
            } else {
                $statusCode = $response->status();
                $errorBody = $response->json();
                $errorMessage = $errorBody['msg'] ?? $errorBody['message'] ?? $errorBody['error_description'] ?? 'Failed to create user';
                
                \Log::error('Supabase Auth Error', [
                    'status' => $statusCode,
                    'body' => $errorBody,
                    'email' => $validated['email'],
                ]);
                
                // Make error message user-friendly
                if (str_contains($errorMessage, 'already') || str_contains($errorMessage, 'registered')) {
                    $errorMessage = 'This email address is already registered. Please use a different email.';
                }
                
                return redirect()->back()
                    ->withInput()
                    ->with('error', $errorMessage);
            }
                
        } catch (\Exception $e) {
            \Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $validated['email'] ?? 'unknown'
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Sync users from Supabase auth to public.users table
     */
    public function syncFromAuth()
    {
        try {
            $supabaseUrl = config('services.supabase.url');
            $supabaseServiceKey = config('services.supabase.service_key');
            
            if (!$supabaseServiceKey || $supabaseServiceKey === 'your_service_role_key_here') {
                return redirect()->back()
                    ->with('error', 'SUPABASE_SERVICE_KEY not configured');
            }
            
            // Get all users from Supabase Auth
            $response = Http::withHeaders([
                'apikey' => $supabaseServiceKey,
                'Authorization' => 'Bearer ' . $supabaseServiceKey,
            ])->get("{$supabaseUrl}/auth/v1/admin/users");

            if ($response->successful()) {
                $authUsers = $response->json()['users'] ?? [];
                $synced = 0;
                $skipped = 0;
                
                foreach ($authUsers as $authUser) {
                    // Check if user exists in public.users
                    if (!User::where('id', $authUser['id'])->exists()) {
                        // Create missing user
                        User::create([
                            'id' => $authUser['id'],
                            'full_name' => $authUser['user_metadata']['full_name'] ?? $authUser['email'],
                            'email' => $authUser['email'],
                            'phone' => $authUser['user_metadata']['phone'] ?? null,
                            'role' => $authUser['user_metadata']['role'] ?? 'rescuer',
                            'encrypted_password' => '', // Password is in auth.users
                        ]);
                        $synced++;
                    } else {
                        $skipped++;
                    }
                }
                
                return redirect()->route('admin.users.index')
                    ->with('success', "Sync complete: {$synced} users synced, {$skipped} already existed");
            } else {
                throw new \Exception('Failed to fetch users from Supabase Auth');
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to sync users', [
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to sync users: ' . $e->getMessage());
        }
    }

    public function show(User $user)
    {
        $user->load(['emergencyReports', 'assignedReports']);
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|regex:/^[0-9]{11}$/',
            'role' => 'required|in:admin,rescuer,citizen',
            'password' => 'nullable|min:8|confirmed',
        ], [
            'phone.regex' => 'Phone number must be exactly 11 digits.',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully');
    }
}
