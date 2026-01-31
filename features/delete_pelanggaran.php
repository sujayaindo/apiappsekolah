<?php
// features/delete_pelanggaran.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

require_once '../config/db.php';

$id = $_POST['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM catatan_pelanggaran WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => true, 'message' => 'Catatan berhasil dihapus']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => false, 'message' => 'ID tidak ditemukan']);
}
?>