# Deployment cPanel tanpa SSH — 12 Oktober 2026

Domain: `indonesiavanillas.com`, akun cPanel `indone57`. SSL Let’s Encrypt untuk domain utama dan www sudah aktif dan lulus validasi tepercaya. cPanel mencatat masa berlaku sampai 10 Januari 2027 dan renewal melalui AutoSSL. Paket hosting tetap sama.

## Struktur yang dipakai

- `/home/indone57/vanilindo`: source Laravel, vendor produksi, konfigurasi `.env`, log, cache, session dan file privat.
- `/home/indone57/public_html`: hanya front controller serta aset public. Front controller memuat source dari folder di atas dan mengatur public path ke direktori ini.
- `/home/indone57/public_html/storage`: hanya media JPG/JPEG/PNG/WebP. Aturan `.htaccess` menolak ekstensi lain, nama executable termasuk `php.jpg`, dan directory listing. Upload menggunakan disk public yang root-nya dikonfigurasi lewat `PUBLIC_STORAGE_PATH`.

Seluruh gambar, font, CSS dan JS public byte-identical dengan release yang sudah diaudit. Tidak ada perubahan desain.

## Berkas yang sudah disiapkan di Mac

Semua berada di `dist/cpanel`, yang diabaikan Git:

| Berkas | Tujuan |
| --- | --- |
| `private-patch.zip` | Extract ke `/home/indone57/vanilindo`; menambahkan konfigurasi disk public dan pemeriksaan storage untuk cPanel. Tidak memuat password. |
| `public-html.zip` | Extract ke `/home/indone57/public_html` setelah DB/env siap. Tidak berisi source, vendor, env atau data upload lama. |
| `initial-database.sql` | Import hanya ke DB baru kosong lewat phpMyAdmin. 10 migrasi, 1 site setting, 17 content block. Tidak berisi admin, produk/artikel audit, session, cache, job, ataupun DROP TABLE. |
| `production.env` | Template privat dengan APP_KEY baru; isi nama DB/user sebenarnya serta password DB, upload ke folder source privat lalu rename menjadi `.env`. Jangan mengunggah ke public_html. |

Admin produksi terpisah: `dist/admin-credentials.txt` dan `dist/create-admin.sql`. Nama `adminvanilindo`, email `vanilindo@gmail.com`; file password dan SQL hash bcrypt memiliki permission 600 dan tidak masuk ZIP. Akun telah dibuat di hosting melalui import user dan login berhasil diverifikasi di Safari; jangan mengimpor ulang. Jangan gunakan kredensial produksi untuk pengujian lokal.

## Urutan aktivasi

1. cPanel → Database Wizard: buat DB baru `indone57_vanilindo`. Buat user aplikasi untuk DB itu, dengan password kuat yang dimasukkan/disimpan user sendiri. Grant ALL PRIVILEGES hanya pada DB aplikasi ini. Catat nama lengkap DB/user dari cPanel; prefix harus benar.
2. File Manager: periksa source release tetap berada di folder privat, lalu upload/extract private patch. Jangan menghapus folder source, data upload atau database lain.
3. phpMyAdmin: pilih DB aplikasi kosong dan import `initial-database.sql`. Pastikan semua tabel terbentuk, 10 migrasi tercatat, users kosong, site_settings 1 dan content_blocks 17. Jangan mengimpor ulang ke DB yang berisi konten.
4. Isi password DB pada template `production.env` sendiri. Upload hanya ke `/home/indone57/vanilindo`, rename menjadi `.env`, permission 600. Nilai APP_ENV production, APP_DEBUG false, APP_URL HTTPS, SESSION_SECURE_COOKIE true; PUBLIC_STORAGE_PATH harus sesuai direktori media dedicated. APP_KEY baru hanya untuk instalasi pertama ini; pertahankan pada deployment berikutnya.
5. Source runtime `storage/framework/{cache/data,sessions,views}`, `storage/logs` dan `bootstrap/cache` harus writable oleh PHP. Paket sudah berisi direktori ini; gunakan permission sesuai owner cPanel, bukan 777. Jangan upload cache konfigurasi lokal.
6. Buat backup file placeholder yang sudah ada di public_html, di folder privat. Setelah DB/env siap, upload/extract public archive. Existing file media tidak dihapus. Periksa `.htaccess`, `index.php`, `build/manifest.json` dan `storage/.htaccess` tersedia.
7. Aktifkan Force HTTPS Redirect di Domains setelah sertifikat valid; bila perlu route homepage dan semua tautan harus menghasilkan URL HTTPS yang konsisten.
8. User mengimpor SQL admin sendiri ke DB aplikasi yang sudah terinisialisasi. Ini membuat akun baru dengan credential yang disiapkan; jangan menjalankan SQL jika email tersebut sudah ada, dan jangan mengubah akun existing menjadi admin.
9. Periksa live homepage, redirect anchor, aset, menu/carousel desktop/mobile. Uji login admin, edit konten, upload gambar dan tampil di public. Verifikasi .env/vendor/log/source tidak terbuka, Host palsu ditolak, cookie session Secure/HttpOnly/SameSite, media nonimage ditolak Apache, dan tidak ada console error/mixed content.
10. Simpan backup DB/media di Mac di luar hosting. Hapus hanya arsip sementara yang sudah tidak diperlukan melalui Trash yang dapat dipulihkan, tanpa menghapus konten/runtime. Catat disk usage dan uji beban kecil sesuai batas hosting bersama.

Tidak dibuat endpoint public untuk menjalankan Artisan. Aplikasi ini tidak mempunyai worker queue atau scheduler aktif. Migrasi dieksekusi di MariaDB lokal yang bersih lalu diimpor; vendor produksi sudah terpasang pada release. Cache framework dapat dibuat saat boot, sehingga command optimasi server tidak menjadi syarat aktivasi. Jangan mengklaim preflight CLI server dijalankan jika SSH tidak tersedia; pemeriksaan live harus membuktikan perilaku yang relevan.

## Verifikasi yang selesai

36 tes lulus, 272 assertions; Pint dan syntax lint lulus. Rehearsal folder split menghasilkan respons berhasil pada halaman publik/login dan 20 aset; source privat merespons 404. Ini bukti lokal, belum acceptance deployment hosting. PHP development server tidak membaca `.htaccess`, jadi pembatasan Apache masih harus diuji live.

Lihat bukti `docs/audit-evidence/2026-10-12` dan pembaruan pada `production-readiness-report.md`. Panduan 10 Oktober di `kuncihost-deployment-guide.md` bersifat historis untuk jalur SSH.

## Status pelaksanaan terakhir

Database/user aplikasi sudah dibuat; import schema melalui phpMyAdmin berhasil dengan 55 query dan 13 tabel. `.env` sudah terpasang di source privat dengan permission 600, patch privat sudah diekstrak. Folder public awal di-backup ke `/home/indone57/vanilindo/public-before-activation-2026-10-12.zip`; file awal tidak dihapus. Paket public yang diperbaiki telah diunggah 100% dan diekstrak kembali. Seluruh 78 aset cocok hash release dan 200. Homepage, health, login, redirect section, apex/www HTTPS serta HTTP→HTTPS lulus. Jalur privat 403/404, listing media 403, CSP, cookie session dan admin no-store lulus. Bukti `live-http.json` bernilai `passed: true`. Tampilan homepage dan navigasi produk diperiksa di Safari; desain/aset tetap sama. User sudah mengimpor SQL credential baru, phpMyAdmin mengonfirmasi 1 query/1 row/id 1 untuk admin; login berhasil. Pembuatan satu produk draft, upload gambar dan edit alt text yang tersimpan setelah reload lulus melalui Safari; gambar HTTPS 200 cocok hash asli. Produk uji tetap tidak terbit (Visible on website dan Featured off), tidak muncul di homepage. Bukti `hosting-admin-import.json` dan `live-admin.json`.

## Permission archive

Generator ZIP public menggunakan `ZipInfo` dengan mode file 644, termasuk `.htaccess`, CSS/JS/font/gambar. Default `writestr` 600 tidak cocok untuk Apache pada hosting ini. `.env` tetap 600 di source privat; perubahan permission hanya untuk berkas public yang memang harus dibaca web server. Verifikasi semua mode file dan CRC dilakukan saat membangun paket. Setelah ekstraksi, jalankan `python3 scripts/verify_live_deployment.py` dari Mac untuk pemeriksaan read-only HTTPS, seluruh aset terhadap hash release, private paths, CSP, cookie dan redirect. Nilai cookie tidak dicatat.

Smoke kecil hosting: 30 request, concurrency 3, 0 error, p95 1.978,2 ms. Ini berbeda dari stress lokal concurrency 20; kapasitas maksimum hosting belum dinyatakan teruji.

Probe Host palsu diarahkan Apache ke virtual host situs lain (bukan Vanilindo). Tes lokal TrustedHosts lulus, tetapi probe hosting ini tidak memverifikasi middleware aplikasi; lihat `live-host-check.json`.

Batas verifikasi hosting: tidak ada SSH/preflight CLI server; belum ada stress besar/soak test. Validasi upload aplikasi lulus pada tes lokal. Upload gambar, persistensi edit, akses media HTTPS, private paths dan penolakan directory listing sudah lulus live. File kosong nyata `.txt` dan `.php.jpg` masing-masing ditolak Apache 403; keberadaan file diperiksa lewat Safari/File Manager, lalu fixture dipindahkan ke source privat tanpa penghapusan. Bukti `live-media-denial.json`.
