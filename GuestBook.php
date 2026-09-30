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
}
