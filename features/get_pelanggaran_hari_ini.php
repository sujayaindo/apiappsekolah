<?php
// features/get_pelanggaran_hari_ini.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

require_once '../config/db.php'; 

try {
    $sql = "SELECT 
                cp.id AS id_catatan, 
                cp.dicatat_oleh,
                s_pelanggar.nama AS nama_siswa, 
                p.nama_pelanggaran, 
                cp.tanggal,
                COALESCE(g.nama, s_pencatat.nama, u.username) AS nama_pencatat
            FROM catatan_pelanggaran cp
            JOIN siswa_kelas sk ON cp.siswa_kelas_id = sk.id
            JOIN siswa s_pelanggar ON sk.siswa_id = s_pelanggar.id
            JOIN pelanggaran p ON cp.pelanggaran_id = p.id
            JOIN users u ON cp.dicatat_oleh = u.user_id
            LEFT JOIN guru g ON (u.role = 'guru' AND u.ref_id = g.guru_id)
            LEFT JOIN siswa s_pencatat ON (u.role = 'siswa' AND u.ref_id = s_pencatat.id)
            WHERE DATE(cp.tanggal) = CURDATE()
            ORDER BY cp.tanggal DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll();

    echo json_encode($data);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => false, 
        'message' => 'Query Gagal: ' . $e->getMessage()
    ]);
}
?>