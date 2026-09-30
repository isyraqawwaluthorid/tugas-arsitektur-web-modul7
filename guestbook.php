<?php

declare(strict_types=1);

require_once __DIR__ . '/GuestBook.php';

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$dsn = getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=perpustakaan;charset=utf8mb4';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    error_log('Koneksi basis data gagal: ' . $exception->getMessage());
    http_response_code(500);
    exit('Layanan buku tamu sedang tidak tersedia. Silakan coba beberapa saat lagi.');
}

$guestBook = new GuestBook($pdo);
