# Deploy Vanilindo ke KunciHost cPanel

**Pembaruan 12 Oktober 2026:** SSL Let’s Encrypt sudah aktif dan lulus verifikasi untuk domain utama/www. Support mengonfirmasi tidak ada SSH pada paket ini. Gunakan [panduan tanpa SSH](kuncihost-no-ssh-deployment.md) untuk aktivasi selanjutnya; bagian di bawah merupakan alur awal dan kondisi historis 10 Oktober.

Panduan untuk deployment pertama, berdasarkan repository dan sesi Safari yang diperiksa pada 10 Oktober 2026. Paket aplikasi sudah diunggah dan diekstrak di `/home/indone57/vanilindo`; aplikasi belum diaktifkan.

Audit terbaru dan penghalang aktivasi ada di [production-readiness-report.md](production-readiness-report.md). Tes aplikasi lulus; pengecualian CSP admin sudah diterima user. Prasyarat hosting masih menahan aktivasi.

## Kondisi yang sudah terlihat

- Domain utama: `indonesiavanillas.com`.
- Domain sudah terdaftar hingga 21 September 2027, nameserver NS5.KUNCIHOST.COM dan NS6.KUNCIHOST.COM, tetapi registry resmi .com mencatat `client hold`; DNS publik masih NXDOMAIN. Penyebab hold perlu dikonfirmasi registrar/KunciHost. Klien Area juga meminta verifikasi email akun; hubungan peringatan ini dengan hold domain belum terbukti.
- Username cPanel: `indone57`; home directory: `/home/indone57`.
- PHP Selector sudah diterapkan ke **8.3 aktif**; domain memakai **8.3 account default** melalui Selector. PHP CLI belum diverifikasi.
- Sertifikat domain tercatat **self-signed**.
- Kuota disk: **500 MB**, penggunaan sekitar 1,72 MB; belum ada database.
- Menu Terminal, SSH Access, dan Cron Jobs tidak terlihat di daftar Tools. Ini belum membuktikan SSH sepenuhnya tidak tersedia; konfirmasikan ke support.
- Domain berstatus **Main Domain**, document root saat ini **`/public_html`**. Menurut dokumentasi cPanel, document root Main Domain tidak dapat diubah melalui interface Domains biasa.

Project memakai Laravel 13, Filament 5, Vite, dan MySQL untuk produksi. PHP minimum 8.3 juga cocok dengan dependensi produksi di `composer.lock`. PHP 8.3 telah dipasang di Mac pada `/opt/homebrew/opt/php@8.3/bin/php` dan Composer terverifikasi tersedia di `.tools/composer.phar`. Paket lengkap dengan vendor produksi sudah dibangun dan direhearsal; Artisan tetap perlu dijalankan pada hosting melalui akses CLI yang didukung.

## 1. Pilih PHP 8.3

1. Dari cPanel **Tools**, buka **Select PHP Version**.
2. Pilih **8.3**, lalu tekan **Set as current** apabila tombol tersebut muncul.
3. Pada **Extensions**, pastikan ekstensi Laravel dan dependensi tersedia: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `iconv`, `intl`, `mbstring`, `openssl`, `pdo`, `session`, `tokenizer`, `xml`, `xmlreader`, dan `zip`. Aktifkan juga `gd` untuk kebutuhan gambar dan driver MySQL `pdo_mysql` atau `nd_pdo_mysql` sesuai konfigurasi host; tidak perlu memaksa kedua driver MySQL sekaligus.
4. Buka **MultiPHP Manager** dan periksa domain. Jika menggunakan PHP Selector, versi domain harus mengikuti Selector; cPanel menyarankan **inherit**. Jangan memilih PHP 7.4 secara terpisah di MultiPHP Manager.
5. Jika perlu, pada **Options**, gunakan `upload_max_filesize=8M` dan `post_max_size=16M` supaya unggahan gambar 4 MB dari admin tidak terhalang batas PHP.

Verifikasi PHP 8.3 benar-benar digunakan oleh web domain dan CLI; mengganti Selector saja belum menjadi bukti keduanya sudah sesuai.

## 2. Minta support menyiapkan akses, document root, dan SSL

Masuk ke **Klien Area KunciHost → Open Ticket**. Salin teks berikut; support yang menentukan apakah fitur-fitur ini tersedia pada paket saat ini:

Draft terbaru yang mencakup status `clientHold` tersedia di [kuncihost-support-ticket.md](kuncihost-support-ticket.md). Draft sudah diisi ke formulir Technical Support, tetapi **belum dikirim** karena user meminta penjelasan terlebih dahulu. Paket source telah diekstrak; permintaan berikut berkaitan dengan aktivasi server.

> Halo, saya ingin deploy aplikasi Laravel 13 + Filament 5 pada domain indonesiavanillas.com, akun cPanel indone57. Aplikasi membutuhkan PHP 8.3. Mohon konfirmasi dan bantu:
>
> 1. Mengaktifkan Terminal cPanel atau SSH untuk akun saya, berikut host, port, dan cara memakai PHP CLI 8.3 serta Composer 2.
> 2. Mengatur document root indonesiavanillas.com dan www ke /home/indone57/vanilindo/public. Seluruh source Laravel akan berada di /home/indone57/vanilindo, di luar public_html.
> 3. Memasang SSL tepercaya/AutoSSL untuk indonesiavanillas.com dan www; saat ini cPanel menampilkan self-signed.
> 4. Memastikan symlink public/storage untuk storage/app/public dapat digunakan dan PHP web benar-benar memakai versi 8.3.
>
> Jika document root atau SSH tersebut tidak tersedia pada paket saya, mohon jelaskan alternatif deployment Laravel yang didukung dan aman beserta cara menjalankan migrasi serta membuat akun admin melalui terminal interaktif.

Langkah PHP, database, dan build lokal bisa dilakukan sambil menunggu. Untuk alur panduan ini, lanjutkan aktivasi aplikasi setelah akses CLI dan document root dikonfirmasi.

Jangan unggah seluruh source ke `public_html`: domain hanya boleh melayani isi direktori `public`, agar `.env`, `vendor`, dan konfigurasi tidak terbuka lewat web. Pengaturan ulang document root dan SSL oleh support adalah pekerjaan yang diperlukan; tiket di atas belum dikirim oleh Codex.

## 3. Buat database

1. Buka **Tools → Database Wizard**.
2. Buat database, misalnya suffix `vanilindo`.
3. Buat user database dengan password kuat. Masukkan dan simpan password sendiri, tidak melalui chat.
4. Tambahkan user tersebut ke database itu dengan **ALL PRIVILEGES** untuk database aplikasi.
5. Catat nama lengkap database dan user yang ditampilkan cPanel, termasuk prefix. Contoh nama database: `indone57_vanilindo`; jangan menganggap nama user pasti sama.

Gunakan `localhost` sebagai host hanya jika sesuai informasi hosting; bila KunciHost memberikan host lain, gunakan yang diberikan.

## 4. Build frontend dan siapkan ZIP di Mac

Paket lengkap hasil audit tersedia di `dist/vanilindo-release-2026-10-10.zip` (sekitar 32 MB; sekitar 74 MiB setelah ekstraksi). Paket memuat `vendor --no-dev`, build frontend, dan aset Filament. Metadata/SHA-256 ada di `dist/release-manifest.json`. Tidak memuat `.env`, database lokal, akun/media audit, atau cache konfigurasi privat. Paket ini sudah diunggah dan diekstrak ke `/home/indone57/vanilindo`; tahap upload pertama selesai. Petunjuk berikut tetap disimpan untuk pengulangan deployment.

Untuk membangun ulang paket lengkap, jalankan `python3 scripts/build_release.py` setelah frontend dibangun. Script membangun vendor dalam staging terpisah, memeriksa platform/boot, dan memvalidasi archive. Perintah ZIP manual berikut adalah alternatif **source-only**, yang masih memerlukan instalasi vendor di server.

Jalankan di Terminal Mac:

```sh
cd /Users/Shandy/Documents/Project/Vanilindo
npm ci
npm run build
```

Pastikan `public/build/manifest.json` terbentuk. Node.js diperlukan saat build di Mac; web production akan menjalankan PHP dan menyajikan hasil build tersebut.

Lalu buat ZIP source dan aset hasil build:

```sh
zip -r /tmp/vanilindo-deploy.zip \
  app bootstrap config database public resources routes \
  artisan composer.json composer.lock .env.example \
  -x 'database/*.sqlite*' 'public/hot' 'public/storage' \
     'public/storage/*' 'bootstrap/cache/*.php'
```

Perintah ini ditujukan untuk deployment pertama dari source. ZIP tidak memuat `.env`, `.git`, `node_modules`, runtime `.tools`, data SQLite lokal, cache konfigurasi lokal, atau file upload lama. File `.htaccess` dalam `public` tetap ikut. Dependensi `vendor` akan dipasang di server. Folder runtime storage akan dibuat di langkah berikutnya.

Di **File Manager**, buat folder `vanilindo` di home `/home/indone57`, upload ZIP ke sana, lalu **Extract** ke folder tersebut. Hasilnya harus memiliki `/home/indone57/vanilindo/artisan` dan `/home/indone57/vanilindo/public/index.php`, bukan folder `vanilindo/vanilindo` tambahan. Aktifkan tampilan hidden files jika perlu melihat `.htaccess` dan `.env`.

## 5. Isi konfigurasi produksi di server

Salin `.env.example` menjadi `.env` di `/home/indone57/vanilindo`. Sesuaikan baris berikut, dan isi kredensial database sendiri:

```dotenv
APP_NAME=Vanilindo
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://indonesiavanillas.com

LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database_lengkap_dari_cpanel
DB_USERNAME=nama_user_lengkap_dari_cpanel
DB_PASSWORD="password_database_anda"

FILESYSTEM_DISK=public
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database
```

`FILESYSTEM_DISK=public` sesuai dengan disk yang digunakan form unggahan admin dan pemanggilan URL gambar di website. Sesuaikan sintaks dotenv jika password mengandung karakter khusus; simpan hanya di server. `APP_KEY` akan diisi oleh Artisan pada langkah berikutnya. Pengaturan secure cookie digunakan setelah sertifikat HTTPS tepercaya sudah aktif.

## 6. Pasang dependensi dan inisialisasi aplikasi

Gunakan Terminal cPanel atau SSH yang telah dikonfirmasi support. Perintah di bawah mengasumsikan `php` menunjuk PHP 8.3 dan Composer memakai runtime yang sesuai:

```sh
cd /home/indone57/vanilindo
php -v
composer --version
mkdir -p storage/app/public storage/app/private \
  storage/framework/cache/data storage/framework/sessions \
  storage/framework/views storage/logs bootstrap/cache
composer install --no-dev --optimize-autoloader
composer check-platform-reqs --no-dev
chmod -R u+rwX storage bootstrap/cache
chmod 600 .env
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan vanilindo:create-admin
php artisan optimize
php artisan filament:optimize
php artisan vanilindo:preflight
```

Hentikan urutan jika ada perintah gagal. Bila `php -v` masih 7.4, gunakan executable PHP 8.3 dan cara menjalankan Composer yang diberikan support sebelum melanjutkan; versi web dan CLI dapat berbeda. Jangan memakai `--ignore-platform-reqs` untuk menutupi ketidakcocokan.

Jika menggunakan paket lengkap hasil audit, vendor sudah terpasang; `composer install` tidak wajib diulang. Tetap verifikasi PHP/extensions server melalui preflight, serta jalankan migrasi, storage link, pembuatan admin, dan optimasi. Jangan memakai `--local` pada preflight hosting.

Perintah create-admin meminta nama, email, password minimal 12 karakter, dan konfirmasi melalui terminal interaktif. Isi sendiri. Seeder project mengisi konten awal; tidak menyediakan akun admin otomatis. Pastikan direktori `storage` dan `bootstrap/cache` writable oleh proses PHP sesuai ownership host; tidak perlu permission 777.

`key:generate` di atas hanya untuk aplikasi baru dengan APP_KEY kosong. Pertahankan APP_KEY pada deployment berikutnya. Jangan memakai `migrate:fresh` pada database yang sudah berisi data.

## 7. Aktifkan dan cek website

1. Pastikan document root yang benar sudah aktif dan sertifikat valid untuk domain utama serta `www`.
2. Setelah HTTPS tepercaya berfungsi, aktifkan **Force HTTPS Redirect** di **Domains**, atau minta support mengaturnya jika tombol belum tersedia.
3. Buka `https://indonesiavanillas.com` dan periksa halaman, layout, font, gambar, dan tautan WhatsApp.
4. Buka `/admin`, login, edit satu konten, kemudian upload gambar. Pastikan perubahan dan gambar benar-benar muncul di website.
5. Uji di HP dan periksa tidak ada peringatan sertifikat atau mixed content. Route `/up` dapat memeriksa aplikasi berhasil boot, tetapi tidak menggantikan uji login, database, dan upload.
6. Unduh/simpan backup database dan file media di luar kuota hosting, lalu pantau pertumbuhan disk. Kuota 500 MB juga harus menampung dependensi PHP, database, media, log, dan arsip upload.

## Jika SSH/Terminal tidak diizinkan

Paket lengkap dengan `vendor --no-dev` dan aset Filament sudah dibuat di Mac PHP 8.3 dan lulus boot/preflight rehearsal lokal.

Namun upload `vendor` saja belum menyelesaikan migrasi, pembuatan admin interaktif, dan symlink storage. Minta support menjalankan langkah server tersebut atau menyediakan metode CLI yang didukung. Jangan membuat endpoint PHP publik untuk menjalankan Artisan atau memasang kredensial admin. Jika support tidak bisa mengakomodasi, metode deployment harus disesuaikan sebelum aktivasi; jangan menganggap menu Softaculous Laravel akan memasang aplikasi Vanilindo yang sudah ada.

## Sumber

- [Laravel deployment: PHP, public directory, permissions, optimization, debug](https://laravel.com/framework/docs/deployment)
- [cPanel: mengelola domain dan batas document root Main Domain](https://docs.cpanel.net/cpanel/domains/domains/manage-the-domain/)
- [cPanel: MultiPHP Manager bersama CloudLinux PHP Selector](https://support.cpanel.net/hc/en-us/articles/360052492693-Using-the-MultiPHP-Manager-with-CloudLinux-s-PHP-Selector)
- Repository: `composer.json`, `composer.lock`, `.env.example`, `config/filesystems.php`, `database/seeders/DatabaseSeeder.php`, dan `app/Console/Commands/CreateAdminUser.php`.
