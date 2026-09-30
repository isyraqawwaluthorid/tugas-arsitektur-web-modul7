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

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Buku Tamu Digital Perpustakaan</title>
<style>
    :root {
        color-scheme: light dark;
        font-family: system-ui, sans-serif;
    }

    body {
        max-width: 760px;
        margin: 2rem auto;
        padding: 0 1rem;
        line-height: 1.5;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-block: 1.5rem;
        padding: 1rem;
        border: 1px solid #8888;
        border-radius: 8px;
    }

    textarea {
        min-height: 6rem;
        resize: vertical;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        text-align: left;
        vertical-align: top;
        padding: 0.4rem 0.6rem;
        border-bottom: 1px solid #8884;
    }

    td.pesan {
        white-space: pre-line;
        overflow-wrap: anywhere;
    }

    .errors {
        color: #b91c1c;
    }

    .success {
        color: #15803d;
    }
</style>
</head>
<body>
<h1>Buku Tamu Digital Perpustakaan</h1>

<?php if ($errors !== []): ?>
    <ul class="errors">
        <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($success !== null): ?>
    <p class="success"><?= e($success) ?></p>
<?php endif; ?>

<form method="post" action="guestbook.php">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <label for="nama">Nama</label>
    <input type="text" name="nama" id="nama" maxlength="100" value="<?= e($old['nama']) ?>" required>

    <label for="email">Email</label>
    <input type="email" name="email" id="email" maxlength="255" value="<?= e($old['email']) ?>" required>

    <label for="pesan">Pesan</label>
    <textarea name="pesan" id="pesan" minlength="5" required><?= e($old['pesan']) ?></textarea>

    <button type="submit">Kirim Pesan</button>
</form>

<h2>Daftar Pesan</h2>
<?php if ($entries === []): ?>
    <p>Belum ada pesan di buku tamu.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Pesan</th>
                <th>Tanggal Kirim</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><?= e($entry['nama']) ?></td>
                    <td><?= e($entry['email']) ?></td>
                    <td class="pesan"><?= e($entry['pesan']) ?></td>
                    <td><?= e((string) $entry['tanggal_kirim']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</body>
</html>
