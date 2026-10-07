# Vanilindo

Website publik dan panel admin Vanilindo, mengikuti [referensi desain Canva](https://canva.link/89340k6364ag9fi). Stack: Laravel 13, Filament 5, Blade, Tailwind CSS, dan MySQL untuk produksi. Font DM Sans dan Libre Caslon Display disimpan lokal (OFL-1.1).

## Menjalankan di komputer

Prasyarat: PHP 8.3+, Composer 2, dan Node.js 20.19+ atau 22.12+. Jalankan perintah dari folder proyek. Pastikan `php --version`, `composer --version`, dan `node --version` berhasil terlebih dahulu. Jika PHP atau Composer belum terpasang, gunakan unduhan resmi [PHP untuk Windows](https://www.php.net/downloads.php?os=windows) dan [Composer](https://getcomposer.org/download/), lalu buka ulang terminal setelah instalasi.

Jika komputer ini menggunakan runtime lokal dalam `.tools/php` dan `.tools/composer`, aktifkan untuk terminal PowerShell saat ini dengan:

```powershell
$env:Path = "$PWD\.tools\php;$PWD\.tools\composer;$env:Path"
```

Folder `.tools` tidak masuk Git dan tidak ikut saat proyek di-clone. PHP harus mengaktifkan ekstensi `intl`, `mbstring`, `openssl`, `fileinfo`, `zip`, dan `pdo_sqlite` untuk setup lokal ini.

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (Select-String -Path .env -Pattern '^APP_KEY=\s*$' -Quiet) { php artisan key:generate }
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite }
php artisan migrate --seed
php artisan storage:link
npm.cmd ci
npm.cmd run build
php artisan serve
```

Buka `http://127.0.0.1:8000` untuk website dan `http://127.0.0.1:8000/admin` untuk panel admin. Setelah setup pertama selesai, cukup jalankan `php artisan serve` untuk membuka website lagi; aktifkan PATH di atas jika memakai runtime lokal pada terminal baru. Buat akun admin awal dari terminal interaktif dengan `php artisan vanilindo:create-admin`; password dimasukkan secara privat, tidak lewat chat maupun Git. Jika ingin mengubah tampilan sambil bekerja, jalankan `npm.cmd run dev` di terminal kedua. Pada macOS/Linux, gunakan `npm` sebagai pengganti `npm.cmd` dan sesuaikan perintah PowerShell.

Konten halaman publik menggunakan bahasa Inggris. Aset foto asli belum tersedia; area gambar dan teks yang belum final diberi penanda `[Temporary]`. Detail kontak yang terlihat di Canva telah dikonfirmasi untuk digunakan dan tetap dapat diedit dari admin.

Panduan: [admin](docs/admin-guide.md), [cek deployment cPanel](docs/deployment-checklist.md), dan [rencana implementasi](docs/implementation-plan.md).
