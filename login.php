<?php
// login.php
require_once 'function.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    header("Location: portal_$role.php");
    exit();
}

$portal = $_GET['portal'] ?? '';
if (!in_array($portal, ['siswa', 'guru', 'admin'])) {
    header('Location: index.php');
    exit();
}

$notification = getNotification();
$showRegister = false;
$showForgotPassword = false;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);
    
    if (empty($email) || empty($password)) {
        showNotification('Email dan password harus diisi!', 'error');
    } else {
        $user = authenticateUser($pdo, $email, $password, $portal);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            
            showNotification('Login berhasil!', 'success');
            header("Location: portal_{$user['role']}.php");
            exit();
        } else {
            if ($portal === 'guru') {
                // Check if user exists but not approved
                $checkUser = getUserByEmail($pdo, $email);
                if ($checkUser && $checkUser['role'] === 'guru' && !$checkUser['approved']) {
                    showNotification('Akun Anda belum disetujui oleh admin!', 'error');
                } else {
                    showNotification('Email atau password salah!', 'error');
                }
            } else {
                showNotification('Email atau password salah!', 'error');
            }
        }
    }
    $notification = getNotification();
}

// Handle register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $userData = [
        'name' => sanitize($_POST['name']),
        'nis' => sanitize($_POST['nis']),
        'email' => sanitize($_POST['email']),
        'phone' => sanitize($_POST['phone']),
        'gender' => sanitize($_POST['gender']),
        'birth_date' => sanitize($_POST['birth_date']),
        'address' => sanitize($_POST['address']),
        'password' => sanitize($_POST['password']),
        'role' => $portal
    ];
    
    $confirmPassword = sanitize($_POST['confirm_password']);
    
    // Validation
    $errors = [];
    
    if (empty($userData['name'])) $errors[] = 'Nama harus diisi';
    if (empty($userData['nis'])) $errors[] = 'NIS/NIP harus diisi';
    if (empty($userData['email']) || !validateEmail($userData['email'])) $errors[] = 'Email tidak valid';
    if (empty($userData['password']) || !validatePassword($userData['password'])) $errors[] = 'Password minimal 6 karakter';
    if ($userData['password'] !== $confirmPassword) $errors[] = 'Password dan konfirmasi password tidak sama';
    
    if (checkEmailExists($pdo, $userData['email'])) $errors[] = 'Email sudah terdaftar';
    if (checkNISExists($pdo, $userData['nis'])) $errors[] = ($portal === 'siswa' ? 'NIS' : 'NIP') . ' sudah terdaftar';
    
    // Role-specific validation
    if ($portal === 'siswa') {
        $userData['class'] = sanitize($_POST['class']);
        $userData['major'] = sanitize($_POST['major']);
        $userData['year'] = sanitize($_POST['year']);
        $userData['parent_phone'] = sanitize($_POST['parent_phone']);
        
        if (empty($userData['class'])) $errors[] = 'Kelas harus dipilih';
        if (empty($userData['major'])) $errors[] = 'Jurusan harus dipilih';
        if (empty($userData['year'])) $errors[] = 'Tahun masuk harus dipilih';
        if (empty($userData['parent_phone'])) $errors[] = 'No. HP orang tua harus diisi';
    } elseif ($portal === 'guru') {
        $userData['subject'] = sanitize($_POST['subject']);
        $userData['education'] = sanitize($_POST['education']);
        $userData['university'] = sanitize($_POST['university']);
        $userData['experience'] = sanitize($_POST['experience']);
        
        if (empty($userData['subject'])) $errors[] = 'Mata pelajaran harus dipilih';
        if (empty($userData['education'])) $errors[] = 'Pendidikan terakhir harus dipilih';
        if (empty($userData['university'])) $errors[] = 'Universitas harus diisi';
        if (empty($userData['experience'])) $errors[] = 'Pengalaman mengajar harus dipilih';
    }
    
    if (empty($errors)) {
        if (registerUser($pdo, $userData)) {
            if ($portal === 'guru') {
                showNotification('Registrasi berhasil! Menunggu persetujuan admin.', 'success');
            } else {
                showNotification('Registrasi berhasil! Silakan login dengan akun Anda.', 'success');
            }
            $showRegister = false;
        } else {
            showNotification('Terjadi kesalahan saat registrasi!', 'error');
        }
    } else {
        showNotification(implode(', ', $errors), 'error');
        $showRegister = true;
    }
    $notification = getNotification();
}

// Handle forgot password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = sanitize($_POST['forgot_email']);
    
    if (empty($email)) {
        showNotification('Email harus diisi!', 'error');
    } else {
        $result = createPasswordRequest($pdo, $email);
        
        if ($result === true) {
            showNotification('Permintaan reset password berhasil dikirim! Admin akan mengonfirmasi segera.', 'success');
            $showForgotPassword = false;
        } elseif ($result === 'exists') {
            showNotification('Permintaan reset password sudah dikirim! Menunggu konfirmasi admin.', 'info');
        } else {
            showNotification('Email tidak ditemukan!', 'error');
        }
    }
    $notification = getNotification();
}

// Handle show register/forgot password
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'register' && $portal !== 'admin') {
        $showRegister = true;
    } elseif ($_GET['action'] === 'forgot') {
        $showForgotPassword = true;
    }
}

$portalNames = [
    'siswa' => 'Siswa',
    'guru' => 'Guru', 
    'admin' => 'Admin'
];

$portalIcons = [
    'siswa' => '👨‍🎓',
    'guru' => '👨‍🏫',
    'admin' => '👨‍💼'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login <?php echo $portalNames[$portal]; ?> - Sistem Absensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            box-sizing: border-box;
        }
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

    <?php if (!$showRegister && !$showForgotPassword): ?>
    <!-- Login Form -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <span class="text-white text-3xl"><?php echo $portalIcons[$portal]; ?></span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Login <?php echo $portalNames[$portal]; ?></h2>
                <a href="index.php" class="text-blue-500 hover:text-blue-600 text-sm font-medium">← Kembali</a>
            </div>
            
            <form method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" id="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" id="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" name="login" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                    Masuk
                </button>
            </form>
            
            <div class="mt-6 text-center space-y-4">
                <?php if ($portal !== 'admin'): ?>
                <a href="?portal=<?php echo $portal; ?>&action=register" class="block w-full text-gray-600 hover:text-gray-800 font-medium">Daftar Akun Baru</a>
                <?php endif; ?>
                <a href="?portal=<?php echo $portal; ?>&action=forgot" class="text-gray-500 hover:text-gray-700 text-sm">Lupa Password?</a>
            </div>
        </div>
    </div>

    <?php elseif ($showRegister): ?>
    <!-- Register Form -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <span class="text-white text-3xl"><?php echo $portalIcons[$portal]; ?></span>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Daftar <?php echo $portalNames[$portal]; ?></h2>
                <p class="text-gray-600 mb-4">Lengkapi data diri Anda dengan benar</p>
                <a href="?portal=<?php echo $portal; ?>" class="text-blue-500 hover:text-blue-600 text-sm font-medium">← Kembali ke Login</a>
            </div>
            
            <form method="POST" class="space-y-6">
                <!-- Data Pribadi -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border-2 border-blue-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <span class="text-2xl mr-3">👤</span>
                        Data Pribadi
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nama Lengkap *</label>
                            <input type="text" id="name" name="name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label for="nis" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo $portal === 'siswa' ? 'NIS *' : 'NIP *'; ?>
                            </label>
                            <input type="text" id="nis" name="nis" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                            <input type="email" id="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Nomor Telepon *</label>
                            <input type="tel" id="phone" name="phone" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700 mb-2">Jenis Kelamin *</label>
                            <select id="gender" name="gender" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Pilih Jenis Kelamin</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-2">Tanggal Lahir *</label>
                            <input type="date" id="birth_date" name="birth_date" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Alamat Lengkap *</label>
                        <textarea id="address" name="address" rows="3" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"></textarea>
                    </div>
                </div>

                <?php if ($portal === 'siswa'): ?>
                <!-- Data Akademik Siswa -->
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border-2 border-green-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <span class="text-2xl mr-3">🎓</span>
                        Data Akademik
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="class" class="block text-sm font-medium text-gray-700 mb-2">Kelas *</label>
                            <select id="class" name="class" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Pilih Kelas</option>
                                <option value="10A">10A</option>
                                <option value="10B">10B</option>
                                <option value="10C">10C</option>
                                <option value="11A">11A</option>
                                <option value="11B">11B</option>
                                <option value="11C">11C</option>
                                <option value="12A">12A</option>
                                <option value="12B">12B</option>
                                <option value="12C">12C</option>
                            </select>
                        </div>
                        <div>
                            <label for="major" class="block text-sm font-medium text-gray-700 mb-2">Jurusan *</label>
                            <select id="major" name="major" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Pilih Jurusan</option>
                                <option value="IPA">IPA (Ilmu Pengetahuan Alam)</option>
                                <option value="IPS">IPS (Ilmu Pengetahuan Sosial)</option>
                                <option value="Bahasa">Bahasa</option>
                            </select>
                        </div>
                        <div>
                            <label for="year" class="block text-sm font-medium text-gray-700 mb-2">Tahun Masuk *</label>
                            <select id="year" name="year" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Pilih Tahun</option>
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                                <option value="2022">2022</option>
                            </select>
                        </div>
                        <div>
                            <label for="parent_phone" class="block text-sm font-medium text-gray-700 mb-2">No. HP Orang Tua *</label>
                            <input type="tel" id="parent_phone" name="parent_phone" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($portal === 'guru'): ?>
                <!-- Data Profesional Guru -->
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border-2 border-purple-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <span class="text-2xl mr-3">👨‍🏫</span>
                        Data Profesional
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Mata Pelajaran *</label>
                            <select id="subject" name="subject" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Pilih Mata Pelajaran</option>
                                <option value="Matematika">Matematika</option>
                                <option value="Bahasa Indonesia">Bahasa Indonesia</option>
                                <option value="Bahasa Inggris">Bahasa Inggris</option>
                                <option value="Fisika">Fisika</option>
                                <option value="Kimia">Kimia</option>
                                <option value="Biologi">Biologi</option>
                                <option value="Sejarah">Sejarah</option>
                                <option value="Geografi">Geografi</option>
                                <option value="Ekonomi">Ekonomi</option>
                                <option value="Sosiologi">Sosiologi</option>
                                <option value="PKN">PKN</option>
                                <option value="Agama">Agama</option>
                                <option value="Olahraga">Olahraga</option>
                                <option value="Seni">Seni</option>
                            </select>
                        </div>
                        <div>
                            <label for="education" class="block text-sm font-medium text-gray-700 mb-2">Pendidikan Terakhir *</label>
                            <select id="education" name="education" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Pilih Pendidikan</option>
                                <option value="S1">S1 (Sarjana)</option>
                                <option value="S2">S2 (Magister)</option>
                                <option value="S3">S3 (Doktor)</option>
                            </select>
                        </div>
                        <div>
                            <label for="university" class="block text-sm font-medium text-gray-700 mb-2">Universitas *</label>
                            <input type="text" id="university" name="university" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                        <div>
                            <label for="experience" class="block text-sm font-medium text-gray-700 mb-2">Pengalaman Mengajar *</label>
                            <select id="experience" name="experience" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                <option value="">Pilih Pengalaman</option>
                                <option value="< 1 tahun">Kurang dari 1 tahun</option>
                                <option value="1-3 tahun">1-3 tahun</option>
                                <option value="3-5 tahun">3-5 tahun</option>
                                <option value="5-10 tahun">5-10 tahun</option>
                                <option value="> 10 tahun">Lebih dari 10 tahun</option>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Data Keamanan -->
                <div class="bg-gradient-to-r from-red-50 to-pink-50 rounded-xl p-6 border-2 border-red-200">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <span class="text-2xl mr-3">🔐</span>
                        Data Keamanan
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                            <input type="password" id="password" name="password" required minlength="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            <p class="text-xs text-gray-500 mt-1">Minimal 6 karakter</p>
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        </div>
                    </div>
                </div>

                <button type="submit" name="register" class="w-full bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white py-4 px-6 rounded-xl font-bold text-lg transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                    <span class="flex items-center justify-center space-x-2">
                        <span>📝</span>
                        <span>Daftar Sekarang</span>
                    </span>
                </button>
            </form>
        </div>
    </div>

    <?php elseif ($showForgotPassword): ?>
    <!-- Forgot Password Form -->
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <span class="text-white text-3xl">🔑</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Lupa Password</h2>
                <a href="?portal=<?php echo $portal; ?>" class="text-blue-500 hover:text-blue-600 text-sm font-medium">← Kembali ke Login</a>
            </div>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label for="forgot_email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="forgot_email" name="forgot_email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <button type="submit" name="forgot_password" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-lg font-medium transition-colors">
                    Reset Password
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>