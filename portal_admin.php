<?php
// portal_admin.php
require_once 'function.php';
requireRole('admin');

$user = getUserById($pdo, $_SESSION['user_id']);
$pendingTeachers = getPendingTeachers($pdo);
$pendingPasswordRequests = getPendingPasswordRequests($pdo);
$allStudents = getAllUsers($pdo, 'siswa');
$allTeachers = getAllUsers($pdo, 'guru');
$todayStats = getTodayAttendanceStats($pdo);

$notification = getNotification();

// Handle approve teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_teacher'])) {
    $teacherId = (int)$_POST['teacher_id'];
    if (approveTeacher($pdo, $teacherId)) {
        showNotification('Guru berhasil disetujui!', 'success');
    } else {
        showNotification('Gagal menyetujui guru!', 'error');
    }
    header('Location: portal_admin.php');
    exit();
}

// Handle reject teacher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_teacher'])) {
    $teacherId = (int)$_POST['teacher_id'];
    if (rejectTeacher($pdo, $teacherId)) {
        showNotification('Guru berhasil ditolak!', 'success');
    } else {
        showNotification('Gagal menolak guru!', 'error');
    }
    header('Location: portal_admin.php');
    exit();
}

// Handle approve password request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_password'])) {
    $requestId = (int)$_POST['request_id'];
    $password = approvePasswordRequest($pdo, $requestId);
    if ($password) {
        showNotification("Permintaan password disetujui! Password: $password", 'success');
    } else {
        showNotification('Gagal menyetujui permintaan password!', 'error');
    }
    header('Location: portal_admin.php');
    exit();
}

// Handle reject password request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_password'])) {
    $requestId = (int)$_POST['request_id'];
    if (rejectPasswordRequest($pdo, $requestId)) {
        showNotification('Permintaan password ditolak!', 'success');
    } else {
        showNotification('Gagal menolak permintaan password!', 'error');
    }
    header('Location: portal_admin.php');
    exit();
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $userId = (int)$_POST['user_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    if ($stmt->execute([$userId])) {
        showNotification('Pengguna berhasil dihapus!', 'success');
    } else {
        showNotification('Gagal menghapus pengguna!', 'error');
    }
    header('Location: portal_admin.php');
    exit();
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
    <title>Portal Admin - Sistem Absensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            box-sizing: border-box;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-red-50 to-pink-100 min-h-screen">
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
    <div class="bg-white shadow-lg border-b-4 border-red-500">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gradient-to-r from-red-500 to-red-600 rounded-full flex items-center justify-center shadow-lg">
                        <span class="text-white text-2xl">👨‍💼</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Portal Admin</h1>
                        <p class="text-red-600 font-medium"><?php echo htmlspecialchars($user['name']); ?></p>
                        <p class="text-sm text-gray-500">Administrator Sistem</p>
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

    <main class="max-w-7xl mx-auto py-8 px-4">
        <!-- Welcome Card -->
        <div class="bg-gradient-to-r from-red-500 to-pink-600 rounded-2xl p-8 mb-8 shadow-2xl text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold mb-3">Dashboard Admin 🎛️</h2>
                    <p class="text-red-100 text-lg">Kelola sistem absensi sekolah dengan kontrol penuh</p>
                    <div class="mt-4 flex items-center space-x-4">
                        <div class="bg-white bg-opacity-20 rounded-lg px-4 py-2">
                            <span class="text-sm">Hari ini: </span>
                            <span class="font-bold"><?php echo date('l, d F Y'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="text-6xl opacity-80">⚙️</div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-green-600 text-xl">✅</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Hadir Hari Ini</h3>
                        <p class="text-2xl font-bold text-green-600"><?php echo $todayStats['hadir']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-yellow-600 text-xl">📋</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Izin Hari Ini</h3>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $todayStats['izin']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-red-600 text-xl">🏥</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Sakit Hari Ini</h3>
                        <p class="text-2xl font-bold text-red-600"><?php echo $todayStats['sakit']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-blue-600 text-xl">👨‍🎓</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Total Siswa</h3>
                        <p class="text-2xl font-bold text-blue-600"><?php echo count($allStudents); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mr-4">
                        <span class="text-purple-600 text-xl">👨‍🏫</span>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Total Guru</h3>
                        <p class="text-2xl font-bold text-purple-600"><?php echo count($allTeachers); ?></p>
                    </div>
                </div>
            </div>
        </div>
		    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="hidden fixed inset-0 bg-black bg-opacity-50 modal z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-800">✏️ Edit Profil Admin</h3>
                <button onclick="closeEditProfile()" class="text-gray-500 hover:text-gray-700">✕</button>
            </div>
            
            <?php if (isset($profile_success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    <?php echo $profile_success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($profile_error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $profile_error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="update_profile" value="1">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                           required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Saat Ini</label>
                    <input type="password" name="current_password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password Baru</label>
                    <input type="password" name="new_password" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                </div>
                
                <div class="flex space-x-3 pt-4">
                    <button type="button" onclick="closeEditProfile()" 
                            class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg transition-colors">
                        Batal
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-purple-500 hover:bg-purple-600 text-white py-2 px-4 rounded-lg transition-colors">
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
            // Clear password fields when opening modal
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('passwordError').classList.add('hidden');
        }

        function closeEditProfile() {
            document.getElementById('editProfileModal').classList.add('hidden');
        }

        // Password validation function
        function validateProfileForm() {
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const errorDiv = document.getElementById('passwordError');
            
            // Hide previous errors
            errorDiv.classList.add('hidden');
            
            // If user wants to change password
            if (newPassword || confirmPassword || currentPassword) {
                // Check if current password is provided
                if (!currentPassword) {
                    showPasswordError('Password saat ini wajib diisi untuk mengubah password!');
                    return false;
                }
                
                // Check if new password is provided
                if (!newPassword) {
                    showPasswordError('Password baru wajib diisi!');
                    return false;
                }
                
                // Check password length
                if (newPassword.length < 6) {
                    showPasswordError('Password baru minimal 6 karakter!');
                    return false;
                }
                
                // Check if passwords match
                if (newPassword !== confirmPassword) {
                    showPasswordError('Konfirmasi password tidak cocok!');
                    return false;
                }
                
                // Check if new password is different from current
                if (newPassword === currentPassword) {
                    showPasswordError('Password baru harus berbeda dari password saat ini!');
                    return false;
                }
            }
            
            return true;
        }
        
        function showPasswordError(message) {
            const errorDiv = document.getElementById('passwordError');
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
		</script>

        <!-- Pending Approvals -->
        <?php if (!empty($pendingTeachers) || !empty($pendingPasswordRequests)): ?>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Pending Teachers -->
            <?php if (!empty($pendingTeachers)): ?>
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                        <span class="text-white text-2xl">👨‍🏫</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Persetujuan Guru</h2>
                        <p class="text-gray-600">Guru menunggu persetujuan</p>
                    </div>
                </div>

                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php foreach ($pendingTeachers as $teacher): ?>
                    <div class="bg-gradient-to-r from-orange-50 to-yellow-50 border-2 border-orange-200 rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center shadow-lg">
                                    <span class="text-white text-sm font-bold"><?php echo strtoupper(substr($teacher['name'], 0, 2)); ?></span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($teacher['name']); ?></h3>
                                    <p class="text-sm text-gray-600">📧 <?php echo htmlspecialchars($teacher['email']); ?></p>
                                    <p class="text-sm text-gray-600">📚 <?php echo htmlspecialchars($teacher['subject']); ?></p>
                                    <p class="text-xs text-orange-600 font-medium">📅 <?php echo date('d F Y', strtotime($teacher['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                    <button type="submit" name="approve_teacher" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
                                        ✅ Setuju
                                    </button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                    <button type="submit" name="reject_teacher" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
                                        ❌ Tolak
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pending Password Requests -->
            <?php if (!empty($pendingPasswordRequests)): ?>
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                        <span class="text-white text-2xl">🔑</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Reset Password</h2>
                        <p class="text-gray-600">Permintaan reset password</p>
                    </div>
                </div>

                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php foreach ($pendingPasswordRequests as $request): ?>
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-2xl p-6 shadow-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-lg">
                                    <span class="text-white text-sm font-bold"><?php echo strtoupper(substr($request['name'], 0, 2)); ?></span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($request['name']); ?></h3>
                                    <p class="text-sm text-gray-600">📧 <?php echo htmlspecialchars($request['email']); ?></p>
                                    <p class="text-sm text-gray-600">👤 <?php echo ucfirst($request['role']); ?></p>
                                    <p class="text-xs text-blue-600 font-medium">📅 <?php echo date('d F Y H:i', strtotime($request['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="approve_password" class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
                                        ✅ Setuju
                                    </button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <button type="submit" name="reject_password" class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 shadow-lg hover:shadow-xl">
                                        ❌ Tolak
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- User Management -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Students Management -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                        <span class="text-white text-2xl">👨‍🎓</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Kelola Siswa</h2>
                        <p class="text-gray-600">Daftar semua siswa terdaftar</p>
                    </div>
                </div>

                <div class="max-h-96 overflow-y-auto space-y-3">
                    <?php if (empty($allStudents)): ?>
                    <div class="text-center py-8">
                        <div class="text-4xl mb-2">👨‍🎓</div>
                        <p class="text-gray-500">Belum ada siswa terdaftar</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($allStudents as $student): ?>
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 flex items-center justify-between hover:shadow-lg transition-all">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-lg">
                                    <span class="text-white text-xs font-bold"><?php echo strtoupper(substr($student['name'], 0, 2)); ?></span>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($student['name']); ?></h3>
                                    <p class="text-sm text-gray-600">Kelas <?php echo htmlspecialchars($student['class']); ?> - <?php echo htmlspecialchars($student['major']); ?></p>
                                </div>
                            </div>
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $student['id']; ?>">
                                <button type="submit" name="delete_user" onclick="return confirm('Yakin ingin menghapus siswa ini?')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-xs font-medium transition-colors">
                                    🗑️ Hapus
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Teachers Management -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <div class="flex items-center mb-6">
                    <div class="w-16 h-16 bg-gradient-to-r from-green-500 to-green-600 rounded-2xl flex items-center justify-center mr-6 shadow-lg">
                        <span class="text-white text-2xl">👨‍🏫</span>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Kelola Guru</h2>
                        <p class="text-gray-600">Daftar semua guru yang disetujui</p>
                    </div>
                </div>

                <div class="max-h-96 overflow-y-auto space-y-3">
                    <?php 
                    $approvedTeachers = array_filter($allTeachers, function($teacher) {
                        return $teacher['approved'] == 1;
                    });
                    ?>
                    <?php if (empty($approvedTeachers)): ?>
                    <div class="text-center py-8">
                        <div class="text-4xl mb-2">👨‍🏫</div>
                        <p class="text-gray-500">Belum ada guru yang disetujui</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($approvedTeachers as $teacher): ?>
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 flex items-center justify-between hover:shadow-lg transition-all">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-lg">
                                    <span class="text-white text-xs font-bold"><?php echo strtoupper(substr($teacher['name'], 0, 2)); ?></span>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-800"><?php echo htmlspecialchars($teacher['name']); ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($teacher['subject']); ?></p>
                                </div>
                            </div>
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $teacher['id']; ?>">
                                <button type="submit" name="delete_user" onclick="return confirm('Yakin ingin menghapus guru ini?')" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg text-xs font-medium transition-colors">
                                    🗑️ Hapus
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
	<?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>