<?php
// portal_teacher.php
require_once 'function.php';
requireRole('guru');

$user = getUserById($pdo, $_SESSION['user_id']);
$today = date('Y-m-d');
$todayStats = getTodayAttendanceStats($pdo);
$allStudents = getAllStudentsWithAttendance($pdo);
$activeCode = getActiveManualCode($pdo);

$notification = getNotification();

// Handle generate manual code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_code'])) {
    $newCode = generateManualCode($pdo, $_SESSION['user_id']);
    showNotification("Kode manual baru berhasil dibuat: $newCode", 'success');
    header('Location: portal_guru.php');
    exit();
}

// Handle search and filter
$searchTerm = $_GET['search'] ?? '';
$sortBy = $_GET['sort_by'] ?? 'name';
$sortOrder = $_GET['sort_order'] ?? 'asc';

if (!empty($searchTerm) || $sortBy !== 'name' || $sortOrder !== 'asc') {
    $allStudents = getAllStudentsWithAttendance($pdo, $searchTerm, $sortBy, $sortOrder);
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
    <title>Portal Guru - Sistem Absensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            box-sizing: border-box;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen">
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
    <div class="bg-white shadow-lg border-b-4 border-green-500">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-lg">
                        <span class="text-white text-2xl">👨‍🏫</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Portal Guru</h1>
                        <p class="text-green-600 font-medium"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-sm text-gray-500">Mata Pelajaran: <?php echo htmlspecialchars($user['subject']); ?></p>
                    </div>
                </div>
				<div class="flex items-center space-x-3">
                    <button onclick="openEditProfile()" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-6 py-3 rounded-full font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        ✏️ Edit Profil
                    </button>
                    <div class="text-2xl mb-2"></div>
                <div class="flex items-center space-x-3">
                    <a href="?logout=1" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-6 py-3 rounded-full font-medium transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        🚪 Keluar
                    </a>
                </div>
            </div>
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
            
            <form method="POST" class="space-y-4" onsubmit="return validateProfileForm()">
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
                    <input type="password" name="current_password" id="currentPassword"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Wajib diisi jika ingin mengubah password</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                    <input type="password" name="new_password" id="newPassword"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" id="confirmPassword"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Ulangi password baru</p>
                </div>
                
                <div id="passwordError" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded text-sm"></div>
                
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
	// Profile modal functions
        function openEditProfile() {
            document.getElementById('editProfileModal').classList.remove('hidden');
        }

        function closeEditProfile() {
            document.getElementById('editProfileModal').classList.add('hidden');
        }
		</script>

    <main class="max-w-7xl mx-auto py-8 px-4">
        <!-- Welcome Card -->
        <div class="bg-gradient-to-r from-green-500 to-teal-600 rounded-2xl p-8 mb-8 shadow-2xl text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold mb-3">Selamat Datang, Guru! 👋</h2>
                    <p class="text-green-100 text-lg">Kelola absensi siswa dengan mudah dan efisien</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 mb-8">
            <!-- Today's Statistics -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-green-600 text-xl">✅</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Hadir</h3>
                        <p class="text-2xl font-bold text-green-600"><?php echo $todayStats['hadir']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-yellow-600 text-xl">📋</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Izin</h3>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $todayStats['izin']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-red-600 text-xl">🏥</span>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Sakit</h3>
                        <p class="text-2xl font-bold text-red-600"><?php echo $todayStats['sakit']; ?></p>
                    </div>
                </div>
            </div>

            <!-- Manual Code Card -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="text-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-blue-600 text-xl">🔑</span>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800 mb-2">Kode Manual</h3>
                    <?php if ($activeCode): ?>
                        <div class="text-3xl font-bold text-blue-600 mb-4 font-mono"><?php echo $activeCode; ?></div>
                    <?php else: ?>
                        <div class="text-gray-500 mb-4">Belum ada kode</div>
                    <?php endif; ?>
                    <form method="POST">
                        <button type="submit" name="generate_code" class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-all duration-300 shadow-lg hover:shadow-xl text-sm">
                            <?php echo $activeCode ? 'Buat Kode Baru' : 'Buat Kode'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Students List -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                        <span class="text-white text-2xl">👥</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Daftar Siswa</h2>
                        <p class="text-gray-600">Kelola dan pantau kehadiran siswa</p>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="mb-6 flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <form method="GET" class="flex">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="🔍 Cari nama siswa atau kelas..." class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-l-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                        <button type="submit" class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-3 rounded-r-xl font-medium transition-colors">
                            Cari
                        </button>
                    </form>
                </div>
                <div class="flex gap-2">
                    <form method="GET" class="flex gap-2">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <select name="sort_by" onchange="this.form.submit()" class="px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>📝 Nama</option>
                            <option value="class" <?php echo $sortBy === 'class' ? 'selected' : ''; ?>>🏫 Kelas</option>
                            <option value="attendance_rate" <?php echo $sortBy === 'attendance_rate' ? 'selected' : ''; ?>>📊 Tingkat Kehadiran</option>
                            <option value="today_status" <?php echo $sortBy === 'today_status' ? 'selected' : ''; ?>>📅 Status Hari Ini</option>
                        </select>
                        <select name="sort_order" onchange="this.form.submit()" class="px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                            <option value="asc" <?php echo $sortOrder === 'asc' ? 'selected' : ''; ?>>⬆️ A-Z</option>
                            <option value="desc" <?php echo $sortOrder === 'desc' ? 'selected' : ''; ?>>⬇️ Z-A</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Students Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b-2 border-gray-200">
                            <th class="text-left py-4 px-6 font-bold text-gray-700">Siswa</th>
                            <th class="text-left py-4 px-6 font-bold text-gray-700">Kelas</th>
                            <th class="text-center py-4 px-6 font-bold text-gray-700">Status Hari Ini</th>
                            <th class="text-center py-4 px-6 font-bold text-gray-700">Tingkat Kehadiran</th>
                            <th class="text-center py-4 px-6 font-bold text-gray-700">Total Absen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($allStudents)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-12">
                                <div class="text-6xl mb-4">👥</div>
                                <h3 class="text-xl font-bold text-gray-800 mb-2">Tidak Ada Data Siswa</h3>
                                <p class="text-gray-500"><?php echo !empty($searchTerm) ? 'Tidak ada siswa yang sesuai dengan pencarian' : 'Belum ada siswa terdaftar'; ?></p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($allStudents as $student): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-lg">
                                            <span class="text-white text-sm font-bold"><?php echo strtoupper(substr($student['name'], 0, 2)); ?></span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($student['name']); ?></p>
                                            <p class="text-sm text-gray-500">NIS: <?php echo htmlspecialchars($student['nis']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo htmlspecialchars($student['class']); ?>
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php if ($student['today_status']): ?>
                                        <?php
                                        $statusConfig = [
                                            'hadir' => ['emoji' => '✅', 'class' => 'bg-green-100 text-green-800 border-green-300'],
                                            'izin' => ['emoji' => '📋', 'class' => 'bg-yellow-100 text-yellow-800 border-yellow-300'],
                                            'sakit' => ['emoji' => '🏥', 'class' => 'bg-red-100 text-red-800 border-red-300']
                                        ];
                                        $config = $statusConfig[$student['today_status']];
                                        ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?php echo $config['class']; ?> border">
                                            <?php echo $config['emoji']; ?> <?php echo strtoupper($student['today_status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-300">
                                            ⏰ Belum Absen
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full" style="width: <?php echo $student['attendance_rate']; ?>%"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700"><?php echo $student['attendance_rate']; ?>%</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span class="text-lg font-bold text-gray-800"><?php echo $student['total_attendances']; ?></span>
                                    <p class="text-xs text-gray-500">kali absen</p>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>