# Checklist deployment cPanel

Belum dilakukan. Jangan arahkan domain ke proyek sampai seluruh cek ini selesai.

- Pilih PHP 8.3 atau lebih baru untuk domain (saat audit cPanel masih 7.4). Pastikan ekstensi yang diperlukan Laravel/Filament aktif, termasuk mbstring, openssl, pdo_mysql, fileinfo, gd, intl, dan zip.
- Buat database MySQL/MariaDB dan pengguna database dengan hak yang sesuai. Simpan kredensial hanya dalam `.env` di server, tidak dalam Git.
- Pastikan document root domain menunjuk **hanya** ke direktori `public` Laravel. Folder proyek, `.env`, dan `vendor` tidak boleh bisa diakses langsung dari web. Bila cPanel tidak mengizinkan pengaturan aman ini, tentukan metode deployment lain sebelum lanjut.
- Perbaiki SSL domain: sertifikat saat audit masih *self-signed*. Gunakan sertifikat tepercaya untuk `indonesiavanillas.com` dan `www`, lalu paksa HTTPS setelah valid.
- Tentukan apakah Terminal/SSH tersedia. Bila tidak, rencanakan unggah hasil `composer install --no-dev --optimize-autoloader` dan `npm run build` dari lokal dengan aman. Jangan unggah `node_modules` atau `.tools`.
- Periksa batas disk 500 MB terhadap ukuran `vendor`, build, unggahan gambar, backup, dan log. Sediakan ruang untuk pertumbuhan konten.
- Set `.env` produksi: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://indonesiavanillas.com`, `DB_CONNECTION=mysql`, kredensial DB, dan `APP_KEY` unik. Jalankan migrasi/seed dan `storage:link` di lingkungan produksi.
- Buat akun admin dengan prompt privat di terminal yang aman; jangan menggunakan password contoh atau mengirim password di chat.
- Uji URL publik, `/admin`, unggah foto, tautan kontak, sertifikat HTTPS, serta backup database dan file media sebelum peluncuran.

Status yang masih perlu diverifikasi bersama pemilik hosting: PHP 8.3 benar-benar aktif per domain, document root, Terminal/SSH, sertifikat SSL tepercaya, dan kapasitas akhir.
