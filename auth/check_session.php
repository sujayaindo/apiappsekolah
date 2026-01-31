<?php
// auth/check_session.php
// TARUH INI DI BARIS PALING ATAS FILE PHP (login.php & check_session.php)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');
require_once '../config/db.php';

$headers = getallheaders();
$token = isset($headers['Authorization']) ? $headers['Authorization'] : '';
// Hapus prefix "Bearer " jika ada
$token = str_replace('Bearer ', '', $token);

if (empty($token)) {
    echo json_encode(['status' => false, 'message' => 'Token kosong']);
    exit;
}

try {
    // Cek token di tabel sessions
    $sql = "SELECT s.user_id, u.username, u.role, u.ref_id 
            FROM sessions s
            JOIN users u ON s.user_id = u.user_id
            WHERE s.session_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => false, 'message' => 'Token expired/invalid']);
        exit;
    }

    // Logic Cek Hak Akses (Sama seperti login.php)
    $can_report = false;
    
    if ($user['role'] == 'guru') {
        $can_report = true;
    } elseif ($user['role'] == 'siswa') {
        // Ambil Tahun Ajaran Aktif
        $stmtSetting = $pdo->query("SELECT nilai FROM settings WHERE nama_setting = 'tahun_ajaran_aktif'");
        $tahunAktifKode = $stmtSetting->fetchColumn(); 

        if ($tahunAktifKode) {
            $stmtTahun = $pdo->prepare("SELECT tahun_id FROM tahun_ajaran WHERE kode = ?");
            $stmtTahun->execute([$tahunAktifKode]);
            $tahunId = $stmtTahun->fetchColumn(); 

            if ($tahunId) {
                // Cek apakah siswa ini PKS di tahun aktif
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
    }

    echo json_encode([
        'status' => true,
        'message' => 'Session Valid',
        'data' => [
            'username' => $user['username'],
            'role' => $user['role'],
            'can_report' => $can_report
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>