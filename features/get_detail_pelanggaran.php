<?php
// features/get_detail_pelanggaran.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php'; 

$siswa_id = $_GET['siswa_id'] ?? null;

if (!$siswa_id) {
    echo json_encode(['status' => false, 'message' => 'Siswa ID diperlukan']);
    exit;
}

try {
    // 1. Ambil Tahun Ajaran Aktif
    $stmtSetting = $pdo->query("SELECT nilai FROM settings WHERE nama_setting = 'tahun_ajaran_aktif'");
    $tahunAktifKode = $stmtSetting->fetchColumn();

    // 2. Query Detail berdasarkan Siswa dan Tahun Ajaran
    $sql = "SELECT 
                p.nama_pelanggaran, 
                p.poin, 
                cp.tanggal, 
                cp.keterangan,
                cp.url_bukti_foto
            FROM catatan_pelanggaran cp
            JOIN pelanggaran p ON cp.pelanggaran_id = p.id
            JOIN siswa_kelas sk ON cp.siswa_kelas_id = sk.id
            JOIN tahun_ajaran ta ON sk.tahun_id = ta.tahun_id
            WHERE sk.siswa_id = :siswa_id 
            AND ta.kode = :tahun_aktif
            ORDER BY cp.tanggal DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'siswa_id' => $siswa_id,
        'tahun_aktif' => $tahunAktifKode
    ]);

    echo json_encode([
        'status' => true, 
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'tahun_ajaran' => $tahunAktifKode
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => false, 'message' => $e->getMessage()]);
}