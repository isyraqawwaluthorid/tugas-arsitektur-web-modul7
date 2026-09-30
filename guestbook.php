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

$errors = [];
$old = ['nama' => '', 'email' => '', 'pesan' => ''];
$success = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['csrf_token'], $submittedToken)) {
        $errors[] = 'Token CSRF tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } else {
        $old['nama'] = trim((string) ($_POST['nama'] ?? ''));
        $old['email'] = trim((string) ($_POST['email'] ?? ''));
        $old['pesan'] = trim((string) ($_POST['pesan'] ?? ''));

        $errors = GuestBook::validate($old['nama'], $old['email'], $old['pesan']);

        if ($errors === []) {
            try {
                $guestBook->save($old['nama'], $old['email'], $old['pesan']);

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['flash_success'] = 'Terima kasih, pesan Anda berhasil dikirim.';

                header('Location: guestbook.php', true, 303);
                exit;
            } catch (PDOException $exception) {
                error_log('Gagal menyimpan pesan buku tamu: ' . $exception->getMessage());
                $errors[] = 'Pesan gagal disimpan. Silakan coba lagi.';
            }
        }
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$entries = $guestBook->getAll();
