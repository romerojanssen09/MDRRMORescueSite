<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - MDRRMO Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Grid Background Pattern */
        .grid-background {
            background-color: #000000;
            background-image: 
                linear-gradient(rgba(78, 205, 196, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(78, 205, 196, 0.05) 1px, transparent 1px);
            background-size: 80px 80px;
        }
        
        /* Login Card with Glow Effect */
        .login-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(78, 205, 196, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(78, 205, 196, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-primary-900 grid-background">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="login-card p-8 w-full max-w-md rounded-lg relative z-10">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="flex items-center justify-center gap-2 mb-3">
                    <i class="fas fa-shield-alt text-secondary-400 text-2xl"></i>
                    <h1 class="text-3xl font-bold text-white">MDRRMO</h1>
                </div>
                <p class="text-sm text-primary-300">Admin Panel</p>
            </div>

            <!-- Error Messages -->
            @if($errors->any())
                <div class="bg-accent-900/30 border border-accent-500/30 text-accent-300 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center text-sm">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-accent-900/30 border border-accent-500/30 text-accent-300 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center text-sm">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- Login Form -->
            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf
                
                <!-- Email -->
                <div class="mb-5">
                    <label for="email" class="block text-primary-200 text-sm font-medium mb-2">
                        Email
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-primary-500">
                            <i class="fas fa-envelope text-sm"></i>
                        </span>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}"
                            class="input-field pl-10"
                            placeholder="admin@mdrrmo.com"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label for="password" class="block text-primary-200 text-sm font-medium mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-primary-500">
                            <i class="fas fa-lock text-sm"></i>
                        </span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="input-field pl-10"
                            placeholder="••••••••"
                            required
                        >
                    </div>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="btn-accent w-full py-3 flex items-center justify-center text-sm font-medium"
                >
                    Sign In
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <a href="/" class="text-xs text-primary-400 hover:text-secondary-400 transition">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Home
                </a>
            </div>
            
            <div class="mt-4 text-center text-xs text-primary-500">
                <p>Municipal Disaster Risk Reduction and Management Office</p>
            </div>
        </div>
    </div>
</body>
</html>
