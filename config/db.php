<?php
// config/db.php
$host = 'localhost';
$db   = 'dbappsekolah';
$user = 'root';
$pass = ''; // Pastikan password ini benar (kosong default XAMPP)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    // HAPUS jika ada baris: echo "Koneksi Berhasil"; atau echo 1;
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'message' => 'Koneksi Gagal: ' . $e->getMessage()]);
    exit;
}
?>