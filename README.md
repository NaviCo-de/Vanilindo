# Vanilindo

Website publik dan panel admin Vanilindo, mengikuti [referensi desain Canva](https://canva.link/89340k6364ag9fi). Stack: Laravel 13, Filament 5, Blade, Tailwind CSS, dan MySQL untuk produksi. Font DM Sans dan Libre Caslon Display disimpan lokal (OFL-1.1).

## Menjalankan di komputer

Prasyarat: PHP 8.3+, Composer 2, dan Node.js 20+. Di komputer pengembangan saat ini PHP dan Composer juga tersedia dalam folder `.tools` (tidak masuk Git); tutup dan buka ulang terminal agar tambahan PATH pengguna terbaca.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File database/database.sqlite -ErrorAction SilentlyContinue
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000` untuk website dan `http://127.0.0.1:8000/admin` untuk panel admin. Buat akun admin awal dari terminal interaktif dengan `php artisan vanilindo:create-admin`; password dimasukkan secara privat, tidak lewat chat maupun Git. Jika ingin mengubah tampilan sambil bekerja, jalankan `npm run dev` di terminal kedua.

Konten halaman publik menggunakan bahasa Inggris. Aset foto asli belum tersedia; area gambar dan teks yang belum final diberi penanda `[Temporary]`. Detail kontak yang terlihat di Canva telah dikonfirmasi untuk digunakan dan tetap dapat diedit dari admin.

Panduan: [admin](docs/admin-guide.md), [cek deployment cPanel](docs/deployment-checklist.md), dan [rencana implementasi](docs/implementation-plan.md).
