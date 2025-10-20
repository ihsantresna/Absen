<?php
// index.php
require_once 'function.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    header("Location: portal_$role.php");
    exit();
}

$notification = getNotification();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Sekolah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            box-sizing: border-box;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes glow {
            0%, 100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.5); }
            50% { box-shadow: 0 0 40px rgba(59, 130, 246, 0.8), 0 0 60px rgba(59, 130, 246, 0.4); }
        }
        
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-glow { animation: glow 2s ease-in-out infinite alternate; }
        
        .animation-delay-1000 { animation-delay: 1s; }
        .animation-delay-2000 { animation-delay: 2s; }
        .animation-delay-3000 { animation-delay: 3s; }
        .animation-delay-4000 { animation-delay: 4s; }
        .animation-delay-5000 { animation-delay: 5s; }
        .animation-delay-6000 { animation-delay: 6s; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Notification -->
    <?php if ($notification): ?>
    <div id="notification" class="fixed top-4 right-4 z-50 max-w-sm">
        <div class="bg-white border border-gray-200 rounded-lg shadow-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0 w-8 h-8 mr-3 flex items-center justify-center rounded-full 
                    <?php echo $notification['type'] === 'success' ? 'bg-green-100 text-green-600' : 
                              ($notification['type'] === 'error' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600'); ?>">
                    <?php echo $notification['type'] === 'success' ? '✓' : 
                              ($notification['type'] === 'error' ? '✗' : 'ℹ'); ?>
                </div>
                <div class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($notification['message']); ?></div>
            </div>
        </div>
    </div>
    <script>
        setTimeout(() => {
            document.getElementById('notification').style.display = 'none';
        }, 4000);
    </script>
    <?php endif; ?>

    <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-red-900 via-navy-900 to-red-900 relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-blue-400 to-navy-500 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-pulse"></div>
            <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-br from-red-400 to-red-500 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-pulse animation-delay-2000"></div>
            <div class="absolute top-40 left-40 w-60 h-60 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-pulse animation-delay-4000"></div>
        </div>
        
        <!-- Floating Particles -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-2 h-2 bg-white rounded-full opacity-60 animate-bounce animation-delay-1000"></div>
            <div class="absolute top-1/3 right-1/3 w-1 h-1 bg-blue-300 rounded-full opacity-80 animate-bounce animation-delay-2000"></div>
            <div class="absolute bottom-1/4 left-1/3 w-3 h-3 bg-red-300 rounded-full opacity-50 animate-bounce animation-delay-3000"></div>
            <div class="absolute bottom-1/3 right-1/4 w-2 h-2 bg-navy-300 rounded-full opacity-70 animate-bounce animation-delay-4000"></div>
            <div class="absolute top-1/2 left-1/6 w-1 h-1 bg-yellow-300 rounded-full opacity-60 animate-bounce animation-delay-5000"></div>
            <div class="absolute top-3/4 right-1/6 w-2 h-2 bg-green-300 rounded-full opacity-50 animate-bounce animation-delay-6000"></div>
        </div>
        
        <div class="relative z-10 bg-white/10 backdrop-blur-xl rounded-3xl shadow-2xl p-10 w-full max-w-lg border border-white/20">
            <!-- Header Section -->
            <div class="text-center mb-12">
                <div class="relative mb-8">
                    <div class="w-24 h-24 bg-gradient-to-br from-blue-400 via-navy-500 to-red-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-2xl transform hover:scale-110 transition-all duration-500 animate-pulse">
                        <span class="text-4xl">🎓</span>
                    </div>
                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-yellow-400 rounded-full animate-ping"></div>
                    <div class="absolute -bottom-2 -left-2 w-4 h-4 bg-green-400 rounded-full animate-ping animation-delay-1000"></div>
                </div>
                <h1 class="text-5xl font-black text-white bg-clip-text bg-gradient-to-r from-navy-400 via-navy-500 to--500 mb-4 animate-pulse">
                    Sistem Absensi
                </h1>
				 <h1 class="text-3xl font-black text-white bg-clip-text bg-gradient-to-r from-navy-400 via-navy-500 to--500 mb-4 animate-pulse">
                    SMAN 1 Parungkuda
                </h1>
                <div class="h-1 w-32 bg-gradient-to-r from-white-400 to-white-500 rounded-full mx-auto mb-6 animate-pulse"></div>
                <p class="text-white/90 text-lg font-medium">Pilih portal untuk memulai perjalanan Anda</p>
                <p class="text-white/70 text-sm mt-2">Sistem manajemen kehadiran modern dan terintegrasi</p>
            </div>
            
            <!-- Portal Cards -->
            <div class="space-y-6">
                <!-- Student Portal -->
                <a href="login.php?portal=siswa" class="group w-full bg-gradient-to-r from-blue-500/20 to-cyan-500/20 hover:from-blue-500/30 hover:to-cyan-500/30 backdrop-blur-sm border border-blue-400/30 hover:border-blue-400/50 text-white py-6 px-6 rounded-2xl font-medium transition-all duration-500 transform hover:scale-105 hover:shadow-2xl hover:shadow-blue-500/25 block">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:rotate-12">
                                <span class="text-2xl">👨‍🎓</span>
                            </div>
                            <div class="text-left">
                                <h3 class="text-xl font-bold text-white group-hover:text-blue-300 transition-colors">Portal Siswa</h3>
                                <p class="text-blue-200 text-sm group-hover:text-blue-100 transition-colors">Akses untuk siswa sekolah</p>
                            </div>
                        </div>
                        <div class="text-white/60 group-hover:text-white group-hover:translate-x-2 transition-all duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Teacher Portal -->
                <a href="login.php?portal=guru" class="group w-full bg-gradient-to-r from-green-500/20 to-emerald-500/20 hover:from-green-500/30 hover:to-emerald-500/30 backdrop-blur-sm border border-green-400/30 hover:border-green-400/50 text-white py-6 px-6 rounded-2xl font-medium transition-all duration-500 transform hover:scale-105 hover:shadow-2xl hover:shadow-green-500/25 block">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:rotate-12">
                                <span class="text-2xl">👨‍🏫</span>
                            </div>
                            <div class="text-left">
                                <h3 class="text-xl font-bold text-white group-hover:text-green-300 transition-colors">Portal Guru</h3>
                                <p class="text-green-200 text-sm group-hover:text-green-100 transition-colors">Akses untuk tenaga pengajar</p>
                            </div>
                        </div>
                        <div class="text-white/60 group-hover:text-white group-hover:translate-x-2 transition-all duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>

                <!-- Admin Portal -->
                <a href="login.php?portal=admin" class="group w-full bg-gradient-to-r from-red-500/20 to-red-500/20 hover:from-red-500/30 hover:to-red-500/30 backdrop-blur-sm border border-red-400/30 hover:border-red-400/50 text-white py-6 px-6 rounded-2xl font-medium transition-all duration-500 transform hover:scale-105 hover:shadow-2xl hover:shadow-red-500/25 block">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-gradient-to-br from-red-400 to-red-500 rounded-2xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-300 group-hover:rotate-12">
                                <span class="text-2xl">👨‍💼</span>
                            </div>
                            <div class="text-left">
                                <h3 class="text-xl font-bold text-white group-hover:text-red-300 transition-colors">Portal Admin</h3>
                                <p class="text-red-200 text-sm group-hover:text-red-100 transition-colors">Akses untuk administrator</p>
                            </div>
                        </div>
                        <div class="text-white/60 group-hover:text-white group-hover:translate-x-2 transition-all duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Footer Info -->
            <div class="mt-10 text-center">
                <div class="flex items-center justify-center space-x-2 text-white/60 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span>Sistem keamanan tingkat tinggi</span>
                </div>
                <div class="flex items-center justify-center space-x-6 mt-4 text-white/40 text-xs">
                    <span>🔒 Aman</span>
                    <span>⚡ Cepat</span>
                    <span>📱 Responsif</span>
                    <span>🎯 Akurat</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

