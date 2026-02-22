<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MDRRMO Admin Panel')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .realtime-badge {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
        }
        .new-item {
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-primary-900">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-primary-950 flex flex-col border-r border-primary-800">
            <!-- Logo Section -->
            <div class="p-6 border-b border-primary-800">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-secondary-400 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shield-halved text-primary-950 text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-base font-bold text-primary-100">MDRRMO</h1>
                        <p class="text-xs text-primary-500">Rescue System</p>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <!-- Main Section -->
                <div class="mb-4">
                    <p class="px-3 text-xs font-semibold text-primary-500 uppercase tracking-wider mb-2">Main</p>
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-secondary-400 text-primary-950 font-medium' : 'text-primary-300 hover:bg-primary-800 hover:text-primary-100' }}">
                        <i class="fas fa-chart-line w-5 text-base"></i>
                        <span class="ml-3">Dashboard</span>
                    </a>
                </div>

                <!-- Emergency Section -->
                <div class="mb-4">
                    <p class="px-3 text-xs font-semibold text-primary-500 uppercase tracking-wider mb-2">Emergency</p>
                    <a href="{{ route('admin.reports.index') }}" class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('admin.reports.*') ? 'bg-secondary-400 text-primary-950 font-medium' : 'text-primary-300 hover:bg-primary-800 hover:text-primary-100' }}">
                        <i class="fas fa-exclamation-circle w-5 text-base"></i>
                        <span class="ml-3">Reports</span>
                        @if(isset($pendingCount) && $pendingCount > 0)
                            <span class="ml-auto bg-accent-400 text-white text-xs font-bold px-2 py-0.5 rounded-full">{{ $pendingCount }}</span>
                        @endif
                    </a>
                </div>

                <!-- Management Section -->
                <div class="mb-4">
                    <p class="px-3 text-xs font-semibold text-primary-500 uppercase tracking-wider mb-2">Management</p>
                    <a href="{{ route('admin.teams.index') }}" class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('admin.teams.*') ? 'bg-secondary-400 text-primary-950 font-medium' : 'text-primary-300 hover:bg-primary-800 hover:text-primary-100' }}">
                        <i class="fas fa-users-gear w-5 text-base"></i>
                        <span class="ml-3">Rescue Teams</span>
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2.5 text-sm rounded-lg transition-all {{ request()->routeIs('admin.users.*') ? 'bg-secondary-400 text-primary-950 font-medium' : 'text-primary-300 hover:bg-primary-800 hover:text-primary-100' }}">
                        <i class="fas fa-user-group w-5 text-base"></i>
                        <span class="ml-3">Users</span>
                    </a>
                </div>
            </nav>

            <!-- User Profile Section -->
            <div class="p-4 border-t border-primary-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 flex-1 min-w-0">
                        <div class="w-9 h-9 bg-primary-800 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user text-secondary-400 text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-primary-100 truncate">{{ session('user_name', 'Admin') }}</p>
                            <p class="text-xs text-primary-500">Administrator</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}" id="logoutForm" class="inline">
                        @csrf
                        <button type="button" onclick="confirmLogout()" class="text-primary-400 hover:text-accent-400 transition p-2" title="Logout">
                            <i class="fas fa-right-from-bracket text-base"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-primary-800 shadow-lg border-b border-primary-700">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-primary-100">@yield('header', 'Dashboard')</h2>
                        <p class="text-xs text-primary-400 mt-0.5">@yield('subtitle', 'Overview and statistics')</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Realtime Status -->
                        <div id="realtime-status" class="flex items-center space-x-2 px-3 py-1.5 bg-primary-900 rounded-lg border border-primary-700">
                            <span class="w-2 h-2 bg-primary-500 rounded-full realtime-badge"></span>
                            <span class="text-xs text-primary-400">Connecting...</span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-6 bg-primary-900">
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Logout confirmation
        function confirmLogout() {
            Swal.fire({
                title: 'Logout?',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4ECDC4',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel',
                background: '#0f172a',
                color: '#e2e8f0',
                iconColor: '#4ECDC4'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logoutForm').submit();
                }
            });
        }

        // SweetAlert for session messages
        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '{{ session('success') }}',
                confirmButtonColor: '#10b981',
                timer: 3000,
                timerProgressBar: true
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '{{ session('error') }}',
                confirmButtonColor: '#ef4444'
            });
        @endif

        let reconnectAttempts = 0;
        const maxReconnectAttempts = 5;
        let channels = [];
        let notificationPermission = false;

        // Request browser notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                notificationPermission = permission === 'granted';
                console.log('🔔 Notification permission:', permission);
            });
        } else if ('Notification' in window) {
            notificationPermission = Notification.permission === 'granted';
        }

        // Initialize Supabase with reconnection logic
        function initializeSupabase() {
            if (typeof window.supabase === 'undefined') {
                console.error('❌ Supabase library not loaded!');
                updateRealtimeStatus(false);
                setTimeout(initializeSupabase, 5000);
                return;
            }

            const supabaseUrl = '{{ config('services.supabase.url') }}';
            const supabaseKey = '{{ config('services.supabase.anon_key') }}';
            
            try {
                window.supabaseClient = window.supabase.createClient(supabaseUrl, supabaseKey, {
                    realtime: {
                        params: {
                            eventsPerSecond: 10
                        }
                    }
                });
                console.log('✅ Supabase client initialized');
                reconnectAttempts = 0;
                updateRealtimeStatus(true);
            } catch (error) {
                console.error('❌ Error initializing Supabase:', error);
                updateRealtimeStatus(false);
                handleReconnect();
            }
        }

        // Handle reconnection with exponential backoff
        function handleReconnect() {
            if (reconnectAttempts < maxReconnectAttempts) {
                reconnectAttempts++;
                const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
                console.log(`🔄 Reconnecting in ${delay/1000}s (attempt ${reconnectAttempts}/${maxReconnectAttempts})`);
                setTimeout(initializeSupabase, delay);
            } else {
                console.error('❌ Max reconnection attempts reached');
                showNotification('Connection lost. Please refresh the page.', 'error');
            }
        }

        // Update realtime status indicator
        function updateRealtimeStatus(connected) {
            const statusEl = document.getElementById('realtime-status');
            if (statusEl) {
                const dot = statusEl.querySelector('.realtime-badge');
                const text = statusEl.querySelector('span:last-child');
                
                if (connected) {
                    dot.classList.remove('bg-primary-500', 'bg-accent-500');
                    dot.classList.add('bg-secondary-500');
                    text.textContent = 'Live';
                    text.classList.remove('text-primary-400', 'text-accent-500');
                    text.classList.add('text-secondary-400');
                } else {
                    dot.classList.remove('bg-secondary-500', 'bg-primary-500');
                    dot.classList.add('bg-accent-500');
                    text.textContent = 'Disconnected';
                    text.classList.remove('text-primary-400', 'text-secondary-400');
                    text.classList.add('text-accent-400');
                }
            }
        }

        // Show toast notification
        function showNotification(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-20 right-6 px-6 py-3 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                'bg-blue-500'
            } text-white`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }

        // Show browser notification
        function showBrowserNotification(title, body, icon = '🚨') {
            if (notificationPermission && 'Notification' in window) {
                try {
                    new Notification(title, {
                        body: body,
                        icon: '/favicon.ico',
                        badge: '/favicon.ico',
                        tag: 'mdrrmo-alert',
                        requireInteraction: true
                    });
                    
                    // Play notification sound
                    const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIGGS57OihUBELTKXh8bllHAU2jdXvzn0pBSh+zPDajzsKElyx6OyrWBQLSKDf8sFuJAUuhM/z24k2CBhku+zooVARC0yl4fG5ZRwFNo3V7859KQUofsz');
                    audio.volume = 0.3;
                    audio.play().catch(e => console.log('Audio play failed:', e));
                } catch (error) {
                    console.error('Browser notification error:', error);
                }
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initializeSupabase);

        // Cleanup channels on page unload
        window.addEventListener('beforeunload', () => {
            channels.forEach(channel => {
                try {
                    window.supabaseClient.removeChannel(channel);
                } catch (e) {}
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
