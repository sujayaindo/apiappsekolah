<?php
// change_password.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json');

require_once '../config/db.php';

// Menangani request OPTIONS untuk CORS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Mengambil input JSON
$data = json_decode(file_get_contents("php://input"), true);

// Validasi input
if (!isset($data['user_id']) || !isset($data['old_password']) || !isset($data['new_password'])) {
    echo json_encode([
        'status' => false, 
        'message' => 'Data tidak lengkap. Pastikan user_id, password lama, dan baru terisi.'
    ]);
    exit;
}

$user_id = $data['user_id'];
// Menggunakan md5 sesuai struktur database Anda
$old_pass = md5($data['old_password']); 
$new_pass = md5($data['new_password']);

try {
    // 1. Cek apakah user_id ada dan password lama cocok
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => false, 'message' => 'Pengguna tidak ditemukan']);
        exit;
    }

    if ($user['password'] !== $old_pass) {
        echo json_encode(['status' => false, 'message' => 'Password lama yang Anda masukkan salah']);
        exit;
    }

    // 2. Jika cocok, update ke password baru
    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $updateStmt->execute([$new_pass, $user_id]);

    echo json_encode([
        'status' => true, 
        'message' => 'Password berhasil diperbarui secara permanen'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => false, 
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
?>