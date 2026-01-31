<?php
// data/get_jenis_pelanggaran.php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
require_once '../config/db.php';

try {
    // Ambil semua jenis pelanggaran diurutkan berdasarkan poin tertinggi
    $query = "SELECT id, nama_pelanggaran, kategori, poin FROM pelanggaran ORDER BY poin DESC";
    $stmt = $pdo->query($query);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'data' => $data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Gagal mengambil data pelanggaran: ' . $e->getMessage()
    ]);
}
?>