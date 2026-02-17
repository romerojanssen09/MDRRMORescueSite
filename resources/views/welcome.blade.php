<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MDRRMO Rescue System</title>
    @production
        <link rel="stylesheet" href="{{ asset('build/assets/app-BEI4OL5h.css') }}">
        <script src="{{ asset('build/assets/app-CKl8NZMC.js') }}" defer></script>
    @else
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endproduction
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
        
        /* Hero Section with High Contrast */
        .hero-section {
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.8) 100%);
            position: relative;
            min-height: 75vh;
            display: flex;
            align-items: center;
            padding: 4rem 0;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(ellipse at center, rgba(78, 205, 196, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        
        /* Download Button Glow */
        .download-btn {
            background: linear-gradient(135deg, #4ECDC4 0%, #14b8a6 100%);
            box-shadow: 0 4px 20px rgba(78, 205, 196, 0.3);
            transition: all 0.3s ease;
        }
        
        .download-btn:hover {
            box-shadow: 0 6px 30px rgba(78, 205, 196, 0.5);
            transform: translateY(-2px);
        }
        
        /* Feature Cards */
        .feature-card {
            transition: all 0.3s ease;
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(78, 205, 196, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(78, 205, 196, 0.5), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(78, 205, 196, 0.3);
            background: rgba(15, 23, 42, 0.6);
        }
        
        .feature-card:hover::before {
            opacity: 1;
        }
        
        /* Stats Card */
        .stat-card {
            background: rgba(15, 23, 42, 0.3);
            border: 1px solid rgba(78, 205, 196, 0.1);
            padding: 1.25rem;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
        }
        
        @media (min-width: 640px) {
            .stat-card {
                padding: 2rem;
            }
        }
        
        .stat-card:hover {
            border-color: rgba(78, 205, 196, 0.3);
            background: rgba(15, 23, 42, 0.5);
        }
        
        /* Section Divider */
        .section-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(78, 205, 196, 0.2), transparent);
        }
    </style>
</head>
<body class="bg-primary-900 grid-background">
    <!-- Navigation -->
    <nav class="bg-black/50 backdrop-blur-md border-b border-primary-800/30 fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-14 sm:h-16">
                <div class="flex items-center gap-2 sm:gap-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-secondary-400/10 rounded-lg flex items-center justify-center border border-secondary-400/30">
                        <i class="fas fa-shield-alt text-secondary-400 text-sm sm:text-lg"></i>
                    </div>
                    <div>
                        <span class="text-base sm:text-lg font-bold text-white block leading-none">MDRRMO</span>
                        <span class="text-xs text-primary-400 hidden sm:block">Rescue System</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 sm:gap-4">
                    <a href="#features" class="text-xs sm:text-sm text-primary-300 hover:text-secondary-400 transition hidden sm:block">Features</a>
                    <a href="{{ route('admin.login') }}" class="btn-accent px-4 py-2 sm:px-6 text-xs sm:text-sm font-medium">
                        Admin
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section pt-14 sm:pt-20">
        <div class="max-w-6xl mx-auto px-4 text-center relative z-10">
            <div class="mb-6 sm:mb-8">
                <span class="inline-block px-3 py-1.5 sm:px-5 sm:py-2 bg-secondary-400/10 border border-secondary-400/30 rounded-full text-secondary-400 text-xs font-medium tracking-wide">
                    <i class="fas fa-bolt mr-1"></i> EMERGENCY RESPONSE
                </span>
            </div>
            <h1 class="text-3xl sm:text-5xl md:text-6xl font-bold mb-4 sm:mb-6 text-white leading-tight px-2">
                Municipal Disaster Risk<br/>
                <span class="text-secondary-400">Reduction & Management</span>
            </h1>
            <p class="text-base sm:text-lg mb-8 sm:mb-12 text-primary-200 max-w-3xl mx-auto leading-relaxed px-4">
                Real-time emergency response and rescue coordination platform
            </p>
            
            <!-- Primary CTA -->
            <div class="flex flex-col items-center gap-4 sm:gap-6 mb-6 sm:mb-8 px-4">
                <a href="/downloads/mdrrmo-app.apk" download class="download-btn text-primary-900 w-full sm:w-auto px-8 sm:px-10 py-4 rounded-xl font-semibold text-base flex items-center justify-center gap-3 group">
                    <i class="fab fa-android text-2xl group-hover:scale-110 transition-transform"></i>
                    <div class="text-left">
                        <div class="text-xs sm:text-sm opacity-80">Download for Android</div>
                        <div class="font-bold text-sm sm:text-base">Mobile App</div>
                    </div>
                </a>
                <p class="text-xs text-primary-500">
                    <i class="fas fa-download mr-1"></i> APK • 185 MB • v1.0.0
                </p>
            </div>
            
            <!-- Secondary Actions -->
            <div class="flex flex-col sm:flex-row justify-center gap-3 sm:gap-4 pt-2 sm:pt-4 px-4">
                <a href="{{ route('admin.login') }}" class="border border-secondary-400/30 text-secondary-400 px-6 py-3 rounded-lg font-medium hover:bg-secondary-400/10 hover:border-secondary-400/50 transition text-sm w-full sm:w-auto">
                    <i class="fas fa-user-shield mr-2"></i>Admin Dashboard
                </a>
                <a href="#features" class="border border-primary-600 text-primary-200 px-6 py-3 rounded-lg font-medium hover:bg-white/5 hover:border-primary-500 transition text-sm w-full sm:w-auto">
                    <i class="fas fa-info-circle mr-2"></i>Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-16 sm:py-24 relative">
        <div class="section-divider mb-16 sm:mb-24"></div>
        
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center mb-12 sm:mb-16">
                <span class="text-secondary-400 text-xs sm:text-sm font-semibold tracking-wide uppercase mb-2 sm:mb-3 block">Platform Features</span>
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3 sm:mb-4">Everything You Need</h2>
                <p class="text-primary-300 text-base sm:text-lg max-w-2xl mx-auto px-4">Comprehensive tools for emergency management</p>
            </div>

            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-6">
                <div class="feature-card p-6 sm:p-8 rounded-xl">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-secondary-400/10 rounded-xl flex items-center justify-center mb-4 sm:mb-5">
                        <i class="fas fa-mobile-alt text-secondary-400 text-xl sm:text-2xl"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-semibold mb-2 sm:mb-3 text-white">Mobile App</h3>
                    <p class="text-sm text-primary-400 leading-relaxed">Citizens report emergencies instantly with GPS tracking and photos</p>
                </div>

                <div class="feature-card p-6 sm:p-8 rounded-xl">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-secondary-400/10 rounded-xl flex items-center justify-center mb-4 sm:mb-5">
                        <i class="fas fa-users text-secondary-400 text-xl sm:text-2xl"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-semibold mb-2 sm:mb-3 text-white">Team Management</h3>
                    <p class="text-sm text-primary-400 leading-relaxed">Organize rescue teams with real-time availability status</p>
                </div>

                <div class="feature-card p-6 sm:p-8 rounded-xl">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-secondary-400/10 rounded-xl flex items-center justify-center mb-4 sm:mb-5">
                        <i class="fas fa-map-marked-alt text-secondary-400 text-xl sm:text-2xl"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-semibold mb-2 sm:mb-3 text-white">Live Map View</h3>
                    <p class="text-sm text-primary-400 leading-relaxed">Interactive map showing emergency and team locations</p>
                </div>

                <div class="feature-card p-6 sm:p-8 rounded-xl">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-secondary-400/10 rounded-xl flex items-center justify-center mb-4 sm:mb-5">
                        <i class="fas fa-bell text-secondary-400 text-xl sm:text-2xl"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-semibold mb-2 sm:mb-3 text-white">Push Notifications</h3>
                    <p class="text-sm text-primary-400 leading-relaxed">Real-time alerts for emergencies and mission updates</p>
                </div>

                <div class="feature-card p-6 sm:p-8 rounded-xl">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-secondary-400/10 rounded-xl flex items-center justify-center mb-4 sm:mb-5">
                        <i class="fas fa-comments text-secondary-400 text-xl sm:text-2xl"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-semibold mb-2 sm:mb-3 text-white">In-App Chat</h3>
                    <p class="text-sm text-primary-400 leading-relaxed">Direct communication between citizens and teams</p>
                </div>

                <div class="feature-card p-6 sm:p-8 rounded-xl">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 bg-secondary-400/10 rounded-xl flex items-center justify-center mb-4 sm:mb-5">
                        <i class="fas fa-chart-line text-secondary-400 text-xl sm:text-2xl"></i>
                    </div>
                    <h3 class="text-base sm:text-lg font-semibold mb-2 sm:mb-3 text-white">Analytics Dashboard</h3>
                    <p class="text-sm text-primary-400 leading-relaxed">Statistics and reports for data-driven decisions</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-12 sm:py-20">
        <div class="max-w-6xl mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                <div class="stat-card text-center">
                    <div class="text-3xl sm:text-5xl font-bold mb-2 sm:mb-3 text-secondary-400">24/7</div>
                    <div class="text-xs sm:text-sm text-primary-300 font-medium">Emergency Response</div>
                </div>
                <div class="stat-card text-center">
                    <div class="text-3xl sm:text-5xl font-bold mb-2 sm:mb-3 text-secondary-400">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="text-xs sm:text-sm text-primary-300 font-medium">Real-time Updates</div>
                </div>
                <div class="stat-card text-center">
                    <div class="text-3xl sm:text-5xl font-bold mb-2 sm:mb-3 text-secondary-400">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="text-xs sm:text-sm text-primary-300 font-medium">GPS Tracking</div>
                </div>
                <div class="stat-card text-center">
                    <div class="text-3xl sm:text-5xl font-bold mb-2 sm:mb-3 text-secondary-400">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div class="text-xs sm:text-sm text-primary-300 font-medium">Instant Dispatch</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-16 sm:py-24">
        <div class="section-divider mb-16 sm:mb-24"></div>
        
        <div class="max-w-4xl mx-auto px-4">
            <div class="bg-gradient-to-br from-secondary-400/10 to-transparent border border-secondary-400/20 rounded-2xl p-8 sm:p-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-3 sm:mb-4">Ready to Get Started?</h2>
                <p class="text-primary-300 mb-8 sm:mb-10 text-base sm:text-lg">Access the admin dashboard to manage emergency operations</p>
                <a href="{{ route('admin.login') }}" class="btn-accent inline-block w-full sm:w-auto px-8 sm:px-10 py-3 sm:py-4 text-sm sm:text-base font-semibold rounded-xl hover:scale-105 transition-transform">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login to Admin Panel
                </a>
                <div class="mt-6 sm:mt-8 pt-6 sm:pt-8 border-t border-primary-700/30">
                    <p class="text-xs text-primary-500 mb-2">Default Credentials</p>
                    <p class="text-xs sm:text-sm break-all">
                        <span class="text-secondary-400 font-mono">admin@mdrrmo.com</span>
                        <span class="text-primary-600 mx-2">•</span>
                        <span class="text-secondary-400 font-mono">admin123</span>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-primary-800/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-secondary-400/10 rounded-lg flex items-center justify-center border border-secondary-400/30">
                        <i class="fas fa-shield-alt text-secondary-400"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-white">MDRRMO Rescue System</p>
                        <p class="text-xs text-primary-500">Emergency Response Platform</p>
                    </div>
                </div>
                <div class="text-center md:text-right">
                    <p class="text-sm text-primary-400">&copy; {{ date('Y') }} All rights reserved</p>
                    <p class="text-xs text-primary-600 mt-1">Built with Laravel & React Native</p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
