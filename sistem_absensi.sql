-- database.sql
CREATE DATABASE IF NOT EXISTS sistem_absensi;
USE sistem_absensi;

-- Tabel users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    nis VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    gender ENUM('Laki-laki', 'Perempuan') NOT NULL,
    birth_date DATE NOT NULL,
    address TEXT NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('siswa', 'guru', 'admin') NOT NULL,
    approved BOOLEAN DEFAULT FALSE,
    
    -- Data siswa
    class VARCHAR(10) NULL,
    major VARCHAR(50) NULL,
    year VARCHAR(4) NULL,
    parent_phone VARCHAR(20) NULL,
    
    -- Data guru
    subject VARCHAR(100) NULL,
    education VARCHAR(50) NULL,
    university VARCHAR(255) NULL,
    experience VARCHAR(50) NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel attendances
CREATE TABLE attendances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    student_class VARCHAR(10) NOT NULL,
    status ENUM('hadir', 'izin', 'sakit') NOT NULL,
    reason TEXT NULL,
    method ENUM('button', 'code') NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel manual_codes
CREATE TABLE manual_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(6) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel password_requests
CREATE TABLE password_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('siswa', 'guru') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert admin default
INSERT INTO users (name, nis, email, phone, gender, birth_date, address, password, role, approved) 
VALUES ('Administrator', 'ADMIN001', 'admin@sekolah.com', '081234567890', 'Laki-laki', '1990-01-01', 'Sekolah', 'admin123', 'admin', TRUE);

-- Insert sample data siswa
INSERT INTO users (name, nis, email, phone, gender, birth_date, address, password, role, approved, class, major, year, parent_phone) 
VALUES 
('Ahmad Rizki', '2024001', 'ahmad@student.com', '081234567891', 'Laki-laki', '2006-05-15', 'Jl. Merdeka No. 1', 'siswa123', 'siswa', TRUE, '12A', 'IPA', '2022', '081234567892'),
('Siti Nurhaliza', '2024002', 'siti@student.com', '081234567893', 'Perempuan', '2006-08-20', 'Jl. Sudirman No. 2', 'siswa123', 'siswa', TRUE, '12A', 'IPA', '2022', '081234567894'),
('Budi Santoso', '2024003', 'budi@student.com', '081234567895', 'Laki-laki', '2006-03-10', 'Jl. Thamrin No. 3', 'siswa123', 'siswa', TRUE, '12B', 'IPS', '2022', '081234567896');

-- Insert sample data guru
INSERT INTO users (name, nis, email, phone, gender, birth_date, address, password, role, approved, subject, education, university, experience) 
VALUES 
('Dr. Indira Sari', 'GURU001', 'indira@teacher.com', '081234567897', 'Perempuan', '1985-12-05', 'Jl. Diponegoro No. 4', 'guru123', 'guru', TRUE, 'Matematika', 'S2', 'Universitas Indonesia', '5-10 tahun'),
('Pak Joko Widodo', 'GURU002', 'joko@teacher.com', '081234567898', 'Laki-laki', '1980-06-21', 'Jl. Gatot Subroto No. 5', 'guru123', 'guru', TRUE, 'Bahasa Indonesia', 'S1', 'Universitas Gadjah Mada', '> 10 tahun');