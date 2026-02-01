<?php
// get_laporan_pelanggaran.php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
require_once '../config/db.php';

$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : null;
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : null;
$kelas_id = isset($_GET['kelas_id']) && $_GET['kelas_id'] !== '' ? $_GET['kelas_id'] : null;
$pelanggaran_id = isset($_GET['pelanggaran_id']) && $_GET['pelanggaran_id'] !== '' ? $_GET['pelanggaran_id'] : null;

try {
    // 1. Ambil Tahun Ajaran Aktif dari tabel Settings
    $stmtSetting = $pdo->query("SELECT nilai FROM settings WHERE nama_setting = 'tahun_ajaran_aktif'");
    $tahunAktifKode = $stmtSetting->fetchColumn();

    if (!$tahunAktifKode) {
        throw new Exception("Tahun ajaran aktif belum ditentukan di tabel settings.");
    }

    // 2. Query SQL dengan Filter Tahun Ajaran Aktif
    $sql = "SELECT 
                s.id AS siswa_id, 
                s.nama AS nama_siswa, 
                k.nama_kelas, 
                COUNT(cp.id) AS jumlah_pelanggaran, 
                COALESCE(SUM(p.poin), 0) AS total_poin
            FROM siswa s
            JOIN siswa_kelas sk ON s.id = sk.siswa_id
            JOIN kelas k ON sk.kelas_id = k.kelas_id
            JOIN tahun_ajaran ta ON sk.tahun_id = ta.tahun_id
            LEFT JOIN catatan_pelanggaran cp ON sk.id = cp.siswa_kelas_id
            LEFT JOIN pelanggaran p ON cp.pelanggaran_id = p.id
            WHERE ta.kode = :tahun_aktif"; // Memastikan hanya data tahun ajaran ini

    $params = ['tahun_aktif' => $tahunAktifKode];

    // Filter Tanggal
    if ($tgl_awal && $tgl_akhir) {
        $sql .= " AND cp.tanggal BETWEEN :tgl_awal AND :tgl_akhir";
        $params['tgl_awal'] = $tgl_awal . " 00:00:00";
        $params['tgl_akhir'] = $tgl_akhir . " 23:59:59";
    }

    // Filter Kelas
    if ($kelas_id) {
        $sql .= " AND sk.kelas_id = :kelas_id";
        $params['kelas_id'] = $kelas_id;
    }

    // Filter Jenis Pelanggaran
    if ($pelanggaran_id) {
        $sql .= " AND cp.pelanggaran_id = :pel_id";
        $params['pel_id'] = $pelanggaran_id;
    }

    $sql .= " GROUP BY s.id, s.nama, k.nama_kelas";
    $sql .= " HAVING total_poin > 0";
    $sql .= " ORDER BY total_poin DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'data' => $results,
        'tahun_ajaran' => $tahunAktifKode
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Gagal: ' . $e->getMessage()
    ]);
}
?>