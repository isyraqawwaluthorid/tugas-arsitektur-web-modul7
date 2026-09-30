<?php

declare(strict_types=1);

class GuestBook
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function save(string $nama, string $email, string $pesan): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO buku_tamu (nama, email, pesan) VALUES (:nama, :email, :pesan)'
        );

        $statement->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':pesan' => $pesan,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return list<array{id: int, nama: string, email: string, pesan: string, tanggal_kirim: string}>
     */
    public function getAll(int $limit = 50): array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, nama, email, pesan, tanggal_kirim
             FROM buku_tamu
             ORDER BY tanggal_kirim DESC, id DESC
             LIMIT :limit'
        );

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return list<string> Daftar pesan galat; kosong berarti masukan valid.
     */
    public static function validate(string $nama, string $email, string $pesan): array
    {
        $errors = [];

        if ($nama === '') {
            $errors[] = 'Nama tidak boleh kosong.';
        } elseif (mb_strlen($nama) > 100) {
            $errors[] = 'Nama maksimal 100 karakter.';
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Format email tidak valid.';
        }

        if (mb_strlen($pesan) < 5) {
            $errors[] = 'Pesan minimal 5 karakter.';
        }

        return $errors;
    }
}
