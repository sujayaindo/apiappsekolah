<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php'; 

try {
    // Tabel kelas menggunakan kelas_id dan nama_kelas 
    $stmtKelas = $pdo->query("SELECT kelas_id AS id, nama_kelas AS nama FROM kelas ORDER BY nama_kelas");
    
    // Tabel pelanggaran menggunakan id dan nama_pelanggaran 
    $stmtPel = $pdo->query("SELECT id, nama_pelanggaran AS nama FROM pelanggaran ORDER BY nama_pelanggaran");

    echo json_encode([
        'status' => true,
        'data' => [
            'kelas' => $stmtKelas->fetchAll(PDO::FETCH_ASSOC),
            'pelanggaran' => $stmtPel->fetchAll(PDO::FETCH_ASSOC)
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}
?>