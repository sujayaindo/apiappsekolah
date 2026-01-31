<?php
// auth/login.php
// TARUH INI DI BARIS PALING ATAS FILE PHP (login.php & check_session.php)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
// 1. Tampilkan Error PHP (Untuk Debugging sementara)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Cek apakah file database ada
if (!file_exists('../config/db.php')) {
    echo json_encode(['status' => false, 'message' => 'File db.php tidak ditemukan']);
    exit;
}

require_once '../config/db.php';

// Pastikan koneksi variable $pdo tersedia
if (!isset($pdo)) {
    echo json_encode(['status' => false, 'message' => 'Koneksi database (variable $pdo) tidak terdefinisi']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['username']) || !isset($input['password'])) {
    echo json_encode(['status' => false, 'message' => 'Username/Password kosong']);
    exit;
}

$username = $input['username'];
$password = md5($input['password']); 

try {
    // Cek User
    $stmt = $pdo->prepare("SELECT user_id, username, role, ref_id FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => false, 'message' => 'Username atau Password salah']);
        exit;
    }

    // Logika Role
    $can_report = false; 
    $nama_user = $user['username'];

    if ($user['role'] == 'guru') {
        $can_report = true;
        
        $stmtGuru = $pdo->prepare("SELECT nama FROM guru WHERE guru_id = ?");
        $stmtGuru->execute([$user['ref_id']]);
        $dataGuru = $stmtGuru->fetch();
        if($dataGuru) $nama_user = $dataGuru['nama'];

    } elseif ($user['role'] == 'siswa') {
        // Ambil Tahun Ajaran Aktif
        $stmtSetting = $pdo->query("SELECT nilai FROM settings WHERE nama_setting = 'tahun_ajaran_aktif'");
        $tahunAktifKode = $stmtSetting->fetchColumn(); 

        if ($tahunAktifKode) {
            $stmtTahun = $pdo->prepare("SELECT tahun_id FROM tahun_ajaran WHERE kode = ?");
            $stmtTahun->execute([$tahunAktifKode]);
            $tahunId = $stmtTahun->fetchColumn(); 

            if ($tahunId) {
                // Cek PKS
                $queryPks = "
                    SELECT p.id 
                    FROM pks p
                    JOIN siswa_kelas sk ON p.siswa_kelas_id = sk.id
                    WHERE sk.siswa_id = ? AND sk.tahun_id = ?
                ";
                $stmtPks = $pdo->prepare($queryPks);
                $stmtPks->execute([$user['ref_id'], $tahunId]);
                
                if ($stmtPks->rowCount() > 0) {
                    $can_report = true;
                }
            }
        }

        $stmtSiswa = $pdo->prepare("SELECT nama FROM siswa WHERE id = ?");
        $stmtSiswa->execute([$user['ref_id']]);
        $dataSiswa = $stmtSiswa->fetch();
        if($dataSiswa) $nama_user = $dataSiswa['nama'];
    }

    $token = bin2hex(random_bytes(32)); 
    
    $stmtSession = $pdo->prepare("INSERT INTO sessions (session_id, user_id) VALUES (?, ?)");
    $stmtSession->execute([$token, $user['user_id']]);

    // Hapus semua output buffer sebelumnya (untuk menghilangkan angka 1 jika masih membandel)
    ob_clean(); 
    
    echo json_encode([
        'status' => true,
        'message' => 'Login Berhasil',
        'data' => [
            'token' => $token,
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'nama_lengkap' => $nama_user,
            'role' => $user['role'],
            'can_report' => $can_report
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>