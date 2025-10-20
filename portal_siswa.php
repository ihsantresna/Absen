<?php
// portal_student.php
require_once 'function.php';
requireRole('siswa');

$user = getUserById($pdo, $_SESSION['user_id']);
$today = date('Y-m-d');
$todayAttendance = getAttendanceByStudentAndDate($pdo, $_SESSION['user_id'], $today);
$attendanceHistory = getStudentAttendanceHistory($pdo, $_SESSION['user_id']);
$stats = getAttendanceStats($pdo, $_SESSION['user_id']);
$notifications = getUserNotifications($pdo, $_SESSION['user_id']);
$activeCode = getActiveManualCode($pdo);

$notification = getNotification();

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    if ($todayAttendance) {
        showNotification('Anda sudah absen hari ini!', 'error');
    } else {
        $status = sanitize($_POST['status']);
        $reason = sanitize($_POST['reason'] ?? '');
        $method = sanitize($_POST['method']);
        
        if ($method === 'code') {
            $inputCode = sanitize($_POST['manual_code']);
            if (!$activeCode || $inputCode !== $activeCode) {
                showNotification('Kode manual salah!', 'error');
                $notification = getNotification();
            } else {
                $stmt = $pdo->prepare("INSERT INTO attendances (student_id, student_name, student_class, status, reason, method, date) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $user['name'], $user['class'], $status, $reason, $method, $today]);
                
                showNotification('Absensi berhasil!', 'success');
                header('Location: portal_siswa.php');
                exit();
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO attendances (student_id, student_name, student_class, status, reason, method, date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $user['name'], $user['class'], $status, $reason, $method, $today]);
            
            showNotification('Absensi berhasil!', 'success');
            header('Location: portal_siswa.php');
            exit();
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Siswa - Sistem Absensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            box-sizing: border-box;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
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

    <!-- Header -->
    <div class="bg-white shadow-lg border-b-4 border-blue-500">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-lg">
                        <span class="text-white text-2xl">👨‍🎓</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Portal Siswa</h1>
                        <p class="text-blue-600 font-medium"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-sm text-gray-500">Kelas <?php echo htmlspecialchars($user['class']); ?> - <?php echo htmlspecialchars($user['major']); ?></p>
                    </div>
                </div>
				<div class="text-right">
                    <button onclick="openEditProfile()" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-6 py-3 rounded-full font-medium transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                      ✏️ Edit Profil
                    </button>
                    <div class="flex items-center space-x-2">
					</div>
                
                <div class="flex items-center space-x-3">
                    <a href="?logout=1" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-6 py-3 rounded-full font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        🚪 Keluar
                    </a>
                </div>
				    
                </div>				
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto py-8 px-4">
        <!-- Welcome Card -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl p-8 mb-8 shadow-2xl text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold mb-3">Selamat Datang! 🎉</h2>
                    <p class="text-blue-100 text-lg">Jangan lupa untuk melakukan absensi hari ini</p>
                    <div class="mt-4 flex items-center space-x-4">
                        <div class="bg-white bg-opacity-20 rounded-lg px-4 py-2">
                            <span class="text-sm">Hari ini: </span>
                            <span class="font-bold"><?php echo date('l, d F Y'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="text-6xl opacity-80">📚</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Absensi Card -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-xl p-8">
                <div class="flex items-center mb-8">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                        <span class="text-white text-2xl">📝</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Absensi Hari Ini</h2>
                        <p class="text-gray-600">Pilih status kehadiran Anda</p>
                    </div>
                </div>
                
                <!-- Attendance Status -->
                <div class="mb-8">
                    <?php if ($todayAttendance): ?>
                        <?php
                        $statusColors = [
                            'hadir' => ['bg' => 'green', 'emoji' => '✅'],
                            'izin' => ['bg' => 'yellow', 'emoji' => '📋'],
                            'sakit' => ['bg' => 'red', 'emoji' => '🏥']
                        ];
                        $config = $statusColors[$todayAttendance['status']];
                        ?>
                        <div class="p-4 bg-<?php echo $config['bg']; ?>-50 border border-<?php echo $config['bg']; ?>-200 rounded-lg">
                            <div class="flex items-center">
                                <span class="text-2xl mr-3"><?php echo $config['emoji']; ?></span>
                                <div>
                                    <p class="text-<?php echo $config['bg']; ?>-800 font-medium">Anda sudah absen hari ini: <?php echo strtoupper($todayAttendance['status']); ?></p>
                                    <?php if ($todayAttendance['reason']): ?>
                                    <p class="text-sm text-<?php echo $config['bg']; ?>-600 mt-1">Alasan: <?php echo htmlspecialchars($todayAttendance['reason']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-center">
                                <span class="text-2xl mr-3">⏰</span>
                                <p class="text-yellow-800 font-medium">Anda belum absen hari ini</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!$todayAttendance): ?>
                <!-- Tombol Absensi -->
                <div class="grid grid-cols-3 gap-6 mb-8">
                    <button onclick="showAttendanceModal('hadir')" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white py-6 px-4 rounded-2xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-2">
                        <div class="text-3xl mb-3">✅</div>
                        <div class="text-lg font-bold">Hadir</div>
                        <div class="text-sm opacity-90">Saya hadir</div>
                    </button>
                    <button onclick="showAttendanceModal('izin')" class="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white py-6 px-4 rounded-2xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-2">
                        <div class="text-3xl mb-3">📋</div>
                        <div class="text-lg font-bold">Izin</div>
                        <div class="text-sm opacity-90">Ada keperluan</div>
                    </button>
                    <button onclick="showAttendanceModal('sakit')" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white py-6 px-4 rounded-2xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-2">
                        <div class="text-3xl mb-3">🏥</div>
                        <div class="text-lg font-bold">Sakit</div>
                        <div class="text-sm opacity-90">Tidak sehat</div>
                    </button>
                </div>
                
                <!-- Kode Manual -->
                <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-2xl p-6 border-2 border-dashed border-blue-300">
                    <div class="flex items-center mb-4">
                        <span class="text-2xl mr-3">🔑</span>
                        <label class="text-lg font-bold text-gray-700">Kode Manual dari Guru</label>
                    </div>
                    <form method="POST" class="flex space-x-4">
                        <input type="text" name="manual_code_input" placeholder="Masukkan kode 6 digit..." class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg font-mono" required>
                        <button type="submit" name="submit_manual_code" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-8 py-3 rounded-xl font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
                            Submit
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Statistik -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                        <span class="text-white text-2xl">📊</span>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">Statistik Saya</h2>
                        <p class="text-gray-600 text-sm">Rekap kehadiran</p>
                    </div>
                </div>
                <div class="h-64 mb-4">
                    <canvas id="studentChart"></canvas>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="bg-green-50 rounded-lg p-3">
                        <div class="text-green-600 font-bold text-lg"><?php echo $stats['hadir']; ?></div>
                        <div class="text-green-600 text-xs">Hadir</div>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-3">
                        <div class="text-yellow-600 font-bold text-lg"><?php echo $stats['izin']; ?></div>
                        <div class="text-yellow-600 text-xs">Izin</div>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3">
                        <div class="text-red-600 font-bold text-lg"><?php echo $stats['sakit']; ?></div>
                        <div class="text-red-600 text-xs">Sakit</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Kehadiran -->
        <div class="mt-8 bg-white rounded-2xl shadow-xl p-8">
            <div class="flex items-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                    <span class="text-white text-2xl">📅</span>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Riwayat Kehadiran</h2>
                    <p class="text-gray-600">Lihat semua catatan absensi Anda</p>
                </div>
            </div>

            <div class="max-h-96 overflow-y-auto space-y-4">
                <?php if (empty($attendanceHistory)): ?>
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">📅</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Belum Ada Riwayat Kehadiran</h3>
                    <p class="text-gray-500">Mulai absen untuk melihat riwayat kehadiran Anda</p>
                </div>
                <?php else: ?>
                    <?php foreach ($attendanceHistory as $attendance): ?>
                        <?php
                        $attendanceDate = new DateTime($attendance['date']);
                        $formattedDate = $attendanceDate->format('l, d F Y');
                        $attendanceTime = date('H:i', strtotime($attendance['created_at']));
                        
                        $statusConfig = [
                            'hadir' => ['emoji' => '✅', 'color' => 'green', 'bgClass' => 'from-green-50 to-emerald-50', 'borderClass' => 'border-green-200', 'textClass' => 'text-green-800'],
                            'izin' => ['emoji' => '📋', 'color' => 'yellow', 'bgClass' => 'from-yellow-50 to-orange-50', 'borderClass' => 'border-yellow-200', 'textClass' => 'text-yellow-800'],
                            'sakit' => ['emoji' => '🏥', 'color' => 'red', 'bgClass' => 'from-red-50 to-pink-50', 'borderClass' => 'border-red-200', 'textClass' => 'text-red-800']
                        ];
                        
                        $config = $statusConfig[$attendance['status']];
                        ?>
                        <div class="bg-gradient-to-r <?php echo $config['bgClass']; ?> border-2 <?php echo $config['borderClass']; ?> rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-16 h-16 bg-gradient-to-r from-<?php echo $config['color']; ?>-500 to-<?php echo $config['color']; ?>-600 rounded-2xl flex items-center justify-center shadow-lg">
                                        <span class="text-white text-2xl"><?php echo $config['emoji']; ?></span>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold <?php echo $config['textClass']; ?>"><?php echo $formattedDate; ?></h3>
                                        <div class="flex items-center space-x-4 text-sm <?php echo $config['textClass']; ?> mt-2">
                                            <span>⏰ <?php echo $attendanceTime; ?></span>
                                            <span><?php echo $attendance['method'] === 'code' ? '🔑 Kode Manual' : '📱 Tombol'; ?></span>
                                        </div>
                                        <?php if ($attendance['reason']): ?>
                                        <div class="mt-3 p-3 bg-white bg-opacity-50 rounded-lg">
                                            <p class="text-sm <?php echo $config['textClass']; ?>">💬 <?php echo htmlspecialchars($attendance['reason']); ?></p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Attendance Modal -->
    <div id="attendanceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
            <h3 id="attendanceModalTitle" class="text-xl font-bold text-gray-800 mb-4 text-center"></h3>
            <form method="POST">
                <input type="hidden" id="attendanceStatus" name="status">
                <div id="reasonSection" class="hidden mb-4">
                    <label for="attendanceReason" class="block text-sm font-medium text-gray-700 mb-2">Alasan</label>
                    <textarea id="attendanceReason" name="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Metode Absen</label>
                    <div class="space-y-2">
                        <label class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                            <input type="radio" name="method" value="button" checked class="mr-3">
                            <span class="font-medium">Tombol</span>
                        </label>
                        <label class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                            <input type="radio" name="method" value="code" class="mr-3">
                            <span class="font-medium">Kode Manual</span>
                        </label>
                    </div>
                </div>
                <div id="manualCodeSection" class="hidden mb-4">
                    <label for="manualCodeInput" class="block text-sm font-medium text-gray-700 mb-2">Kode Manual</label>
                    <input type="text" id="manualCodeInput" name="manual_code" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div class="flex space-x-3">
                    <button type="button" onclick="closeAttendanceModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                        Batal
                    </button>
                    <button type="submit" name="submit_attendance" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
<!-- Edit Profile Modal -->
    <div id="editProfileModal" class="hidden fixed inset-0 bg-black bg-opacity-50 modal z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800">✏️ Edit Profil</h3>
                <button onclick="closeEditProfile()" class="text-gray-500 hover:text-gray-700">✕</button>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="update_profile" value="1">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Saat Ini</label>
                    <input type="password" name="current_password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                    <input type="password" name="new_password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeEditProfile()" 
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <script>
        // Chart for student statistics
        const ctx = document.getElementById('studentChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Izin', 'Sakit'],
                datasets: [{
                    data: [<?php echo $stats['hadir']; ?>, <?php echo $stats['izin']; ?>, <?php echo $stats['sakit']; ?>],
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
// Profile modal functions
        function openEditProfile() {
            document.getElementById('editProfileModal').classList.remove('hidden');
        }

        function closeEditProfile() {
            document.getElementById('editProfileModal').classList.add('hidden');
        }
        function showAttendanceModal(status) {
            const modal = document.getElementById('attendanceModal');
            const title = document.getElementById('attendanceModalTitle');
            const statusInput = document.getElementById('attendanceStatus');
            const reasonSection = document.getElementById('reasonSection');
            
            statusInput.value = status;
            
            const statusConfig = {
                'hadir': { title: '✅ Konfirmasi Kehadiran', showReason: false },
                'izin': { title: '📋 Absen dengan Izin', showReason: true },
                'sakit': { title: '🏥 Absen karena Sakit', showReason: true }
            };
            
            const config = statusConfig[status];
            title.textContent = config.title;
            
            if (config.showReason) {
                reasonSection.classList.remove('hidden');
                document.getElementById('attendanceReason').required = true;
            } else {
                reasonSection.classList.add('hidden');
                document.getElementById('attendanceReason').required = false;
            }
            
            modal.classList.remove('hidden');
        }

        function closeAttendanceModal() {
            document.getElementById('attendanceModal').classList.add('hidden');
        }

        // Handle method selection
        document.querySelectorAll('input[name="method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const codeSection = document.getElementById('manualCodeSection');
                const codeInput = document.getElementById('manualCodeInput');
                
                if (this.value === 'code') {
                    codeSection.classList.remove('hidden');
                    codeInput.required = true;
                } else {
                    codeSection.classList.add('hidden');
                    codeInput.required = false;
                }
            });
        });
		// Profile modal functions
        function openEditProfile() {
            document.getElementById('editProfileModal').classList.remove('hidden');
        }

        function closeEditProfile() {
            document.getElementById('editProfileModal').classList.add('hidden');
        }

        // Search and filter functions
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const sort = document.getElementById('sortSelect').value;
            
            const url = new URL(window.location);
            url.searchParams.set('search', search);
            url.searchParams.set('sort', sort);
            url.searchParams.set('page', '1'); // Reset to first page
            
            window.location.href = url.toString();
        }

        // Enter key search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        // Placeholder functions for modals
        function openAttendanceModal() {
            alert('Fitur absensi akan segera tersedia!');
        }

        function openManualCodeModal() {
            alert('Fitur input kode manual akan segera tersedia!');
        }

        function exportAttendance() {
            alert('Fitur export data akan segera tersedia!');
        }

        // Auto-close success/error messages
        <?php if (isset($success_message) || isset($error_message)): ?>
            setTimeout(() => {
                openEditProfile();
            }, 100);
        <?php endif; ?>
    </script>
</body>
</html>