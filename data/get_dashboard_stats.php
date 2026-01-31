<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include '../config/db.php';

try {
    // 1. Hitung Total Pelanggaran Hari Ini
    $queryTotal = "SELECT COUNT(*) FROM catatan_pelanggaran WHERE DATE(tanggal) = CURDATE()";
    $stmtTotal = $pdo->query($queryTotal);
    $totalHariIni = $stmtTotal->fetchColumn();

    // 2. Ambil Statistik Per Jenis (ALL TYPES)
    // Ubah p.poin menjadi p.kategori
    $queryDetail = "SELECT 
                        p.nama_pelanggaran, 
                        p.kategori,  
                        COUNT(cp.id) as jumlah 
                    FROM pelanggaran p
                    LEFT JOIN catatan_pelanggaran cp 
                        ON p.id = cp.pelanggaran_id 
                        AND DATE(cp.tanggal) = CURDATE()
                    GROUP BY p.id
                    ORDER BY jumlah DESC, p.nama_pelanggaran ASC"; 
    
    $stmtDetail = $pdo->query($queryDetail);
    $detail = $stmtDetail->fetchAll();

    echo json_encode([
        'status' => true,
        'total' => $totalHariIni,
        'detail' => $detail
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Gagal memuat statistik: ' . $e->getMessage()
    ]);
}
?>