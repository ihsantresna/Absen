<?php
// function.php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'sistem_absensi';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Utility functions
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function showNotification($message, $type = 'info') {
    $_SESSION['notification'] = [
        'message' => $message,
        'type' => $type
    ];
}

function getNotification() {
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);
        return $notification;
    }
    return null;
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        header('Location: index.php');
        exit();
    }
}

// User functions
function getUserById($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserByEmail($pdo, $email) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function authenticateUser($pdo, $email, $password, $role) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ? AND role = ?");
    $stmt->execute([$email, $password, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $role === 'guru' && !$user['approved']) {
        return false; // Guru belum disetujui
    }
    
    return $user;
}

function registerUser($pdo, $userData) {
    $sql = "INSERT INTO users (name, nis, email, phone, gender, birth_date, address, password, role, approved, class, major, year, parent_phone, subject, education, university, experience) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        $userData['name'],
        $userData['nis'],
        $userData['email'],
        $userData['phone'],
        $userData['gender'],
        $userData['birth_date'],
        $userData['address'],
        $userData['password'],
        $userData['role'],
        $userData['role'] === 'siswa' ? 1 : 0, // Auto approve siswa
        $userData['class'] ?? null,
        $userData['major'] ?? null,
        $userData['year'] ?? null,
        $userData['parent_phone'] ?? null,
        $userData['subject'] ?? null,
        $userData['education'] ?? null,
        $userData['university'] ?? null,
        $userData['experience'] ?? null
    ]);
}

function updateUser($pdo, $userId, $userData) {
    $sql = "UPDATE users SET name = ?, nis = ?, email = ?, phone = ?, gender = ?, birth_date = ?, address = ?, class = ?, major = ?, year = ?, parent_phone = ?, subject = ?, education = ?, university = ?, experience = ?";
    $params = [
        $userData['name'],
        $userData['nis'],
        $userData['email'],
        $userData['phone'],
        $userData['gender'],
        $userData['birth_date'],
        $userData['address'],
        $userData['class'] ?? null,
        $userData['major'] ?? null,
        $userData['year'] ?? null,
        $userData['parent_phone'] ?? null,
        $userData['subject'] ?? null,
        $userData['education'] ?? null,
        $userData['university'] ?? null,
        $userData['experience'] ?? null
    ];
    
    if (!empty($userData['password'])) {
        $sql .= ", password = ?";
        $params[] = $userData['password'];
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $userId;
    
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

// Attendance functions
function getAttendanceByStudentAndDate($pdo, $studentId, $date) {
    $stmt = $pdo->prepare("SELECT * FROM attendances WHERE student_id = ? AND date = ?");
    $stmt->execute([$studentId, $date]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getStudentAttendanceHistory($pdo, $studentId, $search = '', $status = '', $sort = 'newest') {
    $sql = "SELECT * FROM attendances WHERE student_id = ?";
    $params = [$studentId];
    
    if (!empty($search)) {
        $sql .= " AND (date LIKE ? OR reason LIKE ? OR status LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($status) && $status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY date " . ($sort === 'newest' ? 'DESC' : 'ASC');
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAttendanceStats($pdo, $studentId = null) {
    $sql = "SELECT status, COUNT(*) as count FROM attendances";
    $params = [];
    
    if ($studentId) {
        $sql .= " WHERE student_id = ?";
        $params[] = $studentId;
    }
    
    $sql .= " GROUP BY status";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = ['hadir' => 0, 'izin' => 0, 'sakit' => 0];
    foreach ($results as $result) {
        $stats[$result['status']] = $result['count'];
    }
    
    return $stats;
}

function getTodayAttendanceStats($pdo) {
    $today = date('Y-m-d');
    $sql = "SELECT status, COUNT(*) as count FROM attendances WHERE date = ? GROUP BY status";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$today]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = ['hadir' => 0, 'izin' => 0, 'sakit' => 0];
    foreach ($results as $result) {
        $stats[$result['status']] = $result['count'];
    }
    
    return $stats;
}

function getAllStudentsWithAttendance($pdo, $search = '', $sortBy = 'name', $sortOrder = 'asc') {
    $today = date('Y-m-d');
    
    $sql = "SELECT u.*, 
                   ta.status as today_status,
                   COALESCE(att_stats.total_attendances, 0) as total_attendances,
                   COALESCE(att_stats.hadir_count, 0) as hadir_count,
                   CASE 
                       WHEN att_stats.total_attendances > 0 
                       THEN ROUND((att_stats.hadir_count / att_stats.total_attendances) * 100) 
                       ELSE 0 
                   END as attendance_rate
            FROM users u
            LEFT JOIN attendances ta ON u.id = ta.student_id AND ta.date = ?
            LEFT JOIN (
                SELECT student_id, 
                       COUNT(*) as total_attendances,
                       SUM(CASE WHEN status = 'hadir' THEN 1 ELSE 0 END) as hadir_count
                FROM attendances 
                GROUP BY student_id
            ) att_stats ON u.id = att_stats.student_id
            WHERE u.role = 'siswa'";
    
    $params = [$today];
    
    if (!empty($search)) {
        $sql .= " AND (u.name LIKE ? OR u.class LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Add sorting
    $validSortColumns = ['name', 'class', 'attendance_rate', 'today_status'];
    if (in_array($sortBy, $validSortColumns)) {
        $sql .= " ORDER BY ";
        if ($sortBy === 'today_status') {
            $sql .= "CASE WHEN ta.status IS NULL THEN 'zzz' ELSE ta.status END";
        } else {
            $sql .= $sortBy;
        }
        $sql .= " " . ($sortOrder === 'desc' ? 'DESC' : 'ASC');
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Manual code functions
function generateManualCode($pdo, $teacherId) {
    // Delete old codes
    $pdo->prepare("DELETE FROM manual_codes WHERE created_by = ?")->execute([$teacherId]);
    
    // Generate new code
    $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
    
    $stmt = $pdo->prepare("INSERT INTO manual_codes (code, created_by) VALUES (?, ?)");
    $stmt->execute([$code, $teacherId]);
    
    return $code;
}

function getActiveManualCode($pdo) {
    $stmt = $pdo->prepare("SELECT code FROM manual_codes ORDER BY created_at DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['code'] : null;
}

// Admin functions
function getPendingTeachers($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'guru' AND approved = FALSE ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function approveTeacher($pdo, $teacherId) {
    $stmt = $pdo->prepare("UPDATE users SET approved = TRUE WHERE id = ? AND role = 'guru'");
    return $stmt->execute([$teacherId]);
}

function rejectTeacher($pdo, $teacherId) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'guru' AND approved = FALSE");
    return $stmt->execute([$teacherId]);
}

function getAllUsers($pdo, $role, $search = '', $sortBy = 'name', $sortOrder = 'asc') {
    $sql = "SELECT * FROM users WHERE role = ?";
    $params = [$role];
    
    if (!empty($search)) {
        $sql .= " AND (name LIKE ? OR email LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        
        if ($role === 'siswa') {
            $sql = str_replace("(name LIKE ? OR email LIKE ?)", "(name LIKE ? OR email LIKE ? OR class LIKE ?)", $sql);
            $params[] = $searchTerm;
        }
    }
    
    $validSortColumns = ['name', 'email', 'class', 'approved'];
    if (in_array($sortBy, $validSortColumns)) {
        $sql .= " ORDER BY $sortBy " . ($sortOrder === 'desc' ? 'DESC' : 'ASC');
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Password reset functions
function createPasswordRequest($pdo, $email) {
    $user = getUserByEmail($pdo, $email);
    if (!$user) {
        return false;
    }
    
    // Check if request already exists
    $stmt = $pdo->prepare("SELECT * FROM password_requests WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user['id']]);
    if ($stmt->fetch()) {
        return 'exists';
    }
    
    $stmt = $pdo->prepare("INSERT INTO password_requests (user_id, email, name, role) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user['id'], $user['email'], $user['name'], $user['role']]);
}

function getPendingPasswordRequests($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM password_requests WHERE status = 'pending' ORDER BY created_at DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function approvePasswordRequest($pdo, $requestId) {
    $stmt = $pdo->prepare("SELECT pr.*, u.password FROM password_requests pr JOIN users u ON pr.user_id = u.id WHERE pr.id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        // Update request status
        $stmt = $pdo->prepare("UPDATE password_requests SET status = 'approved', processed_at = NOW() WHERE id = ?");
        $stmt->execute([$requestId]);
        
        // Create notification for user
        createNotification($pdo, $request['user_id'], "Password Anda telah dikonfirmasi oleh admin: " . $request['password'], 'password_reset');
        
        return $request['password'];
    }
    
    return false;
}

function rejectPasswordRequest($pdo, $requestId) {
    $stmt = $pdo->prepare("UPDATE password_requests SET status = 'rejected', processed_at = NOW() WHERE id = ?");
    return $stmt->execute([$requestId]);
}

// Notification functions
function createNotification($pdo, $userId, $message, $type) {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, ?)");
    return $stmt->execute([$userId, $message, $type]);
}

function getUserNotifications($pdo, $userId, $unreadOnly = true) {
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$userId];
    
    if ($unreadOnly) {
        $sql .= " AND is_read = FALSE";
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markNotificationAsRead($pdo, $notificationId) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
    return $stmt->execute([$notificationId]);
}

// Validation functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

function checkEmailExists($pdo, $email, $excludeId = null) {
    $sql = "SELECT id FROM users WHERE email = ?";
    $params = [$email];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() !== false;
}

function checkNISExists($pdo, $nis, $excludeId = null) {
    $sql = "SELECT id FROM users WHERE nis = ?";
    $params = [$nis];
    
    if ($excludeId) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() !== false;
}
?>