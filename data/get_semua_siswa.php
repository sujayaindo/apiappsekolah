<?php
// data/get_semua_siswa.php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
require_once '../config/db.php';

try {
    // 1. Ambil Tahun Ajaran Aktif dari tabel Settings
    $stmtSetting = $pdo->query("SELECT nilai FROM settings WHERE nama_setting = 'tahun_ajaran_aktif'");
    $tahunAktifKode = $stmtSetting->fetchColumn();

    if (!$tahunAktifKode) {
        throw new Exception("Tahun ajaran aktif belum disetting di tabel settings.");
    }

    // 2. Ambil data siswa yang hanya terdaftar di tahun ajaran tersebut
    // Kita join ke siswa_kelas karena di sana lah link antara siswa, kelas, dan tahun ajaran
    $query = "SELECT 
                sk.id AS siswa_kelas_id, 
                s.nama, 
                k.nama_kelas 
              FROM siswa_kelas sk
              JOIN siswa s ON sk.siswa_id = s.id
              JOIN kelas k ON sk.kelas_id = k.kelas_id
              JOIN tahun_ajaran ta ON sk.tahun_id = ta.tahun_id
              WHERE ta.kode = ? 
              ORDER BY k.nama_kelas ASC, s.nama ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$tahunAktifKode]);
    $siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'data' => $siswa,
        'tahun_aktif' => $tahunAktifKode // Info tambahan untuk debugging
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Gagal mengambil data: ' . $e->getMessage()
    ]);
}
?>