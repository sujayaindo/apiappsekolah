<?php
// features/report_violation.php

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/db.php';

$imgbb_api_key = "9fbf48cd28b27f6799c43d238418e33a";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Hanya menerima method POST']);
    exit();
}

// 1. TANGKAP DATA TEKS
$user_id        = $_POST['user_id'] ?? null;
$siswa_kelas_id = $_POST['siswa_kelas_id'] ?? null;
$keterangan     = $_POST['keterangan'] ?? '';

// 2. TANGKAP DATA PELANGGARAN (ARRAY)
// Flutter akan mengirimkan ini sebagai string JSON, jadi kita decode
$pelanggaran_ids_raw = $_POST['pelanggaran_ids'] ?? '[]';
$pelanggaran_ids = json_decode($pelanggaran_ids_raw, true);

// Validasi dasar
if (empty($user_id) || empty($siswa_kelas_id) || empty($pelanggaran_ids)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap (User, Siswa, atau Pelanggaran kosong)']);
    exit();
}

$url_bukti = null;

// 3. PROSES UPLOAD KE IMGBB (JIKA ADA FOTO)
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $path_gambar = $_FILES['image']['tmp_name'];
    $data_gambar = file_get_contents($path_gambar);
    $base64_gambar = base64_encode($data_gambar);

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.imgbb.com/1/upload',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => array(
          'key' => $imgbb_api_key,
          'image' => $base64_gambar,
          'name' => 'bukti_' . time()
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $hasil = json_decode($response, true);
    if (isset($hasil['data']['url'])) {
        $url_bukti = $hasil['data']['url'];
    }
}

// 4. SIMPAN KE DATABASE MENGGUNAKAN TRANSACTION
try {
    // Mulai transaksi agar jika salah satu gagal, semua dibatalkan
    $pdo->beginTransaction();

    $sql = "INSERT INTO catatan_pelanggaran (dicatat_oleh, siswa_kelas_id, pelanggaran_id, keterangan, url_bukti_foto) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    // Looping untuk memasukkan setiap pelanggaran yang dicentang
    foreach ($pelanggaran_ids as $p_id) {
        $stmt->execute([$user_id, $siswa_kelas_id, $p_id, $keterangan, $url_bukti]);
    }

    // Jika semua berhasil, simpan permanen
    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => count($pelanggaran_ids) . ' pelanggaran berhasil dicatat',
        'foto_url' => $url_bukti
    ]);

} catch (Exception $e) {
    // Jika ada error, batalkan semua input yang sempat masuk dalam loop ini
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>