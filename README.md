# Modul Buku Tamu Berbasis PDO

Tugas Mandiri Modul 7: buku tamu digital perpustakaan. Pengunjung menulis pesan lewat formulir, pesan disimpan ke basis data MySQL melalui PDO, lalu seluruh pesan ditampilkan di halaman yang sama.

## Struktur Berkas

| Berkas | Keterangan |
| --- | --- |
| `GuestBook.php` | Kelas yang menyimpan (`save`) dan mengambil (`getAll`) data melalui PDO, serta memvalidasi masukan (`validate`). |
| `guestbook.php` | Halaman form input dan tabel daftar pesan. |
| `buku_tamu.sql` | Skema basis data `perpustakaan` dan tabel `buku_tamu`. |

## Skema Tabel `buku_tamu`

| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| `id` | `INT UNSIGNED` | Kunci primer, `AUTO_INCREMENT` |
| `nama` | `VARCHAR(100)` | Nama pengunjung |
| `email` | `VARCHAR(255)` | Email pengunjung |
| `pesan` | `TEXT` | Isi pesan |
| `tanggal_kirim` | `DATETIME` | Terisi otomatis dengan `CURRENT_TIMESTAMP` |

## Penerapan Keamanan

1. **Token CSRF**: token acak 32 byte (`random_bytes`) disimpan di sesi dan disisipkan sebagai input tersembunyi. Token dibandingkan dengan `hash_equals` dan diganti setelah setiap kiriman.
2. **Sanitasi keluaran**: seluruh data yang ditampilkan melewati `htmlspecialchars` (`ENT_QUOTES | ENT_SUBSTITUTE`, UTF-8), sehingga masukan seperti `<script>` tampil sebagai teks biasa.
3. **Prepared statements**: query `INSERT` dan `SELECT` memakai parameter terikat. `PDO::ATTR_EMULATE_PREPARES` dimatikan agar statement disiapkan langsung oleh MySQL.

## Validasi Masukan

- Nama tidak boleh kosong (maksimal 100 karakter sesuai kolom).
- Email harus berformat valid (`FILTER_VALIDATE_EMAIL`).
- Pesan minimal 5 karakter (dihitung dengan `mb_strlen`).

Validasi dilakukan di server. Atribut `required`, `type="email"`, dan `minlength` di form hanya membantu pengguna dan tidak menggantikan validasi server.

Setelah pesan berhasil disimpan, halaman dialihkan dengan pola Post/Redirect/Get (HTTP 303), sehingga menekan tombol *refresh* tidak mengirim ulang pesan.

## Cara Menjalankan

1. Impor skema:
   ```bash
   mysql -u root -p < buku_tamu.sql
   ```
2. Atur kredensial basis data bila berbeda dari bawaan (`root` tanpa kata sandi, host `127.0.0.1`, basis data `perpustakaan`):
   ```bash
   export DB_USER=root
   export DB_PASS=rahasia
   ```
3. Jalankan server bawaan PHP lalu buka `http://localhost:8000/guestbook.php`:
   ```bash
   php -S localhost:8000
   ```

> Catatan: `guestbook.php` dan `GuestBook.php` hanya berbeda huruf kapital. Pada sistem berkas yang tidak membedakan huruf besar/kecil (bawaan macOS dan Windows), kedua berkas tidak bisa berada dalam satu folder. Jalankan proyek ini di sistem berkas yang *case-sensitive* (misalnya Linux, atau volume APFS *case-sensitive* di macOS).

## Referensi

- Dokumentasi PHP: [PDO](https://www.php.net/manual/en/book.pdo.php), [htmlspecialchars](https://www.php.net/manual/en/function.htmlspecialchars.php), [filter_var](https://www.php.net/manual/en/function.filter-var.php), [hash_equals](https://www.php.net/manual/en/function.hash-equals.php)
- OWASP: [Cross-Site Request Forgery Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
