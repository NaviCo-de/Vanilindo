# Audit kesiapan produksi Vanilindo — 10 Oktober 2026

**Status terbaru 12 Oktober: website publik dan halaman login admin aktif melalui HTTPS serta lulus pemeriksaan HTTP live; akun admin sudah dibuat oleh user dan login, pembuatan draft, upload gambar serta edit tersimpan sudah diverifikasi melalui Safari.** Tes aplikasi lulus; pengecualian CSP admin diterima user. User menyetujui kelanjutan dengan pengecualian khusus admin setelah penjelasan tradeoff Filament/Alpine pada 10 Oktober 2026. Hasil ini bukan klaim bahwa semua alert scanner sudah hilang.

Desain dipertahankan. CSS dan JavaScript situs publik sama persis dengan `HEAD`; perubahan Blade hanya mengamankan URL tautan dan memastikan URL gambar menggunakan disk publik. Tidak ada perubahan layout, warna, font, teks produk, atau pengurangan fitur/carousel.

## Pembaruan 12 Oktober 2026

Informasi di bagian audit 10 Oktober di bawah adalah catatan historis. Kondisi terbaru:

- DNS domain utama dan www sudah resolve ke `103.166.157.172`; hold domain sudah terlepas pada pemeriksaan RDAP sebelumnya pada 12 Oktober. Support mengonfirmasi hold sebelumnya akibat verifikasi domain belum dilakukan.
- **SSL Let’s Encrypt sudah diterbitkan dan dipasang** melalui cPanel SSL/TLS Certificates → Wizard setelah user menyetujui Terms of Service. cPanel menampilkan AutoSSL Domain Validated untuk domain utama dan www, berlaku sampai 10 Januari 2027 serta akan diperpanjang melalui AutoSSL. Verifikasi independen dengan certificate validation normal lulus untuk kedua hostname; issuer Let's Encrypt YR1, SAN domain utama dan www. Tidak ada bypass sertifikat, pembelian SSL, atau upgrade hosting. Homepage Laravel Vanilindo kini merespons HTTPS 200 dengan judul Indonesian Vanilla | Vanilindo; sertifikat tepercaya tetap valid.
- Support mengonfirmasi paket cPanel **tidak menyediakan SSH**. Alur deployment disesuaikan: source/vendor tetap di `/home/indone57/vanilindo`; isi public dipasang di `/home/indone57/public_html`; front controller menunjuk source privat dan memakai public path yang benar. Disk public mendukung `PUBLIC_STORAGE_PATH=/home/indone57/public_html/storage`, sedangkan log, cache, session dan file privat tetap di source privat. Tidak dibuat endpoint web untuk Artisan.
- Paket tambahan `dist/cpanel/private-patch.zip` dan `public-html.zip` sudah dibangun dan lolos CRC. Semua aset public selain front controller byte-identical dengan release yang diaudit; layout/CSS/JS/gambar/font tidak berubah. Folder media mendapat aturan Apache untuk menolak file selain raster image dan nama executable. Directory listing media 403 di hosting; upload gambar admin live sudah lulus. Penolakan Apache terhadap file nonimage/executable yang benar-benar ada belum diverifikasi; kontrol File Manager terinterupsi perubahan Safari oleh user sebelum fixture kosong selesai dibuat. Ini tetap batas verifikasi server, bukan hasil lulus.
- **36 tes lulus, 272 assertions**, Pint lulus. Rehearsal lokal memakai struktur public/private cPanel yang sebenarnya: semua halaman/aset yang diperiksa merespons 200, sedangkan `.env`, vendor, Composer, log dan source privat merespons 404. Redirect juga bekerja. Bukti ada di `docs/audit-evidence/2026-10-12/`.
- SQL deployment bersih dibuat dari migrasi dan seeder lokal MariaDB: 10 migrasi, 1 site setting, 17 content block; users/products/articles/session/cache/jobs kosong. `dist/cpanel/initial-database.sql` tidak memuat data audit, akun, atau DROP TABLE. Impor hanya ke database baru yang kosong. Kredensial admin tetap terpisah dan privat; akun produksi dibuat melalui import SQL terpisah setelah schema ini diimpor.
- Database dan user aplikasi `indone57_vanilindo` sudah dibuat, dengan grant hanya pada database aplikasi. phpMyAdmin mengonfirmasi import `initial-database.sql` berhasil, 55 query; 13 tabel dan jumlah data sesuai paket bersih (10 migrasi, 1 site setting, 17 content block, users/products/articles kosong). Bukti `hosting-database-import.json`.
- User sudah mengisi password DB pada `dist/cpanel/production.env`; parsing dotenv dan pemeriksaan konfigurasi produksi lulus tanpa menampilkan secret. File sudah diunggah ke source privat, diberi permission 600, dan di-rename `.env`; daftar File Manager mengonfirmasi nama/ukuran/permission. Patch privat sudah diekstrak, database tersambung dan homepage aplikasi berhasil boot.
- **Website sudah aktif dan HTTP live lulus:** public archive sudah diekstrak setelah backup folder awal ke ZIP privat. Pemeriksaan awal menemukan mode ZIP 600; generator diperbaiki menjadi file 644, user menyelesaikan upload ulang dan paket diekstrak kembali. Seluruh 78 aset public cocok hash release dan merespons 200. Homepage, `/up`, login, redirect section serta apex/www HTTPS berhasil. HTTP otomatis mengarah HTTPS. Jalur env/vendor/Composer/log/Git tertutup (403/404), directory listing media 403, CSP publik tidak memakai eval, login no-store, cookie session Secure/HttpOnly/SameSite=lax, versi PHP tidak diekspos. Bukti `live-http.json` dengan `passed: true`; mode file/CRC diverifikasi saat packaging. Homepage dan klik navigasi produk telah dilihat langsung di Safari. User sudah mengimpor SQL admin melalui phpMyAdmin: 1 query berhasil, 1 row inserted, id 1, `adminvanilindo` / `vanilindo@gmail.com`, is_admin=1. Nilai credential tidak dicatat. User mengonfirmasi login berhasil, lalu sesi admin terautentikasi diverifikasi langsung di Safari. Produk `Deployment verification (draft)` (id 1) dibuat dengan Visible on website dan Featured off, gambar diunggah dan tersimpan; gambar HTTPS 200 cocok hash file asli serta memakai `nosniff`. Alt text diedit dan nilainya tetap tersimpan setelah reload. Draft tidak muncul di homepage dan dipertahankan tidak terbit sebagai bukti uji; desain dan konten publik tetap sama. Bukti `hosting-admin-import.json` dan `live-admin.json`.
- Host palsu pada server hosting merespons 200 dari situs lampion yang tidak terkait Vanilindo karena pemilihan virtual host Apache. Ini bukan respons aplikasi dan tidak membuktikan middleware TrustedHosts Vanilindo dijalankan live. Penolakan Host palsu sudah lulus pada tes lokal; penolakan di lapisan provider tidak tersedia pada probe ini. Bukti `live-host-check.json`.

## Lingkungan dan cakupan

- PHP 8.3.33, Laravel 13.33.0, Filament 5.8.4, Livewire 4.4.6.
- Uji otomatis menggunakan SQLite in-memory; rehearsal migrasi, preflight, browser dan beban menggunakan MariaDB 10.5.29.
- Data audit lokal: 1.000 produk terbit dan 250 artikel terbit, dengan konten panjang; satu artikel tambahan dibuat melalui browser untuk uji upload/edit. Akun audit dan data sintetis tidak dimasukkan ke paket deployment.
- Server audit loopback memakai 8 worker PHP, memory limit 128 MB per worker. Ini tidak mengukur kapasitas server bersama KunciHost.
- ZAP 2.17.0: spider kedalaman 1 menemukan 310 URL, scan aktif terbatasi pada halaman publik selama sekitar satu menit, termasuk pemeriksaan pasif halaman login. Scan aktif tidak melakukan audit lengkap semua aksi admin terautentikasi; akses admin diuji melalui tes aplikasi dan browser.

## Hasil verifikasi

| Pemeriksaan | Hasil |
| --- | --- |
| Tes fitur, otorisasi, XSS, upload, CSRF, Host, dan preflight | **34 lulus, 262 assertions** |
| Composer audit | **0 advisory; 0 abandoned package** |
| npm audit | **0 vulnerability** |
| Build frontend produksi | Lulus |
| PHP syntax lint | 69 berkas, 0 error |
| Pint berkas PHP yang berubah | Lulus |
| Pola secret berisiko tinggi pada 147 berkas tracked | Tidak ditemukan; ini pemindaian pola terbatas |
| Desktop 1440 px dan mobile 390 px | Tidak ada overflow horizontal atau gambar gagal; menu dan carousel berfungsi |
| Admin browser | Login, upload, publish, edit dengan gambar tersimpan, semua resource, dan login throttle lulus; 0 error console/page |
| HTTP negatif | `.env`, `.git/config`, Composer, vendor dan log tidak dapat diakses; tamu diarahkan ke login; Host palsu mendapat 400 |
| Rehearsal migrasi/optimasi/preflight MariaDB | Lulus |
| Paket dengan vendor `--no-dev` | Platform requirements, boot/routes, optimasi, preflight dan HTTP smoke lulus |
| OWASP ZAP | 0 high; **1 jenis alert medium masih ada**, dijelaskan di bawah |

### Uji beban lokal

Kriteria sebelum uji: nol respons gagal atau body salah; p95 maksimal 2.000 ms. Enam route dicampur, mencakup homepage/paginasi, detail produk/artikel, login, dan redirect. Respons redirect yang diharapkan dihitung sebagai berhasil.

| Concurrency | Total request | Error | p95 | Request/detik |
| --- | --- | --- | --- | --- |
| 1 | 120 | 0 | 85,22 ms | 27,80 |
| 5 | 300 | 0 | 230,58 ms | 51,73 |
| 10 | 600 | 0 | 427,16 ms | 54,84 |
| 20 | 1.000 | 0 | 721,37 ms | 54,12 |
| 20, setelah perbaikan akhir | 1.000 | 0 | 730,16 ms | 57,14 |

Ini uji beban/stress terbatas, bukan uji sampai server gagal, soak test berjam-jam, atau bukti kapasitas hosting. Batas entry process hosting yang terlihat adalah 20; RAM 1 GB, kuota disk 500 MB. Hosting sudah lulus smoke kecil 30 request, concurrency 3, 0 error, p95 1.978,2 ms pada 12 Oktober (`live-bounded-load.json`). Ini tidak mengukur kapasitas maksimum dan belum ada stress test besar terhadap server hosting.

## Perbaikan yang dilakukan

1. Memperbarui dependency npm yang terkena advisory shell-quote dan source-map-js. Audit npm setelah perubahan mencatat nol vulnerability.
2. Menolak URL Instagram dengan scheme selain HTTP(S), baik di form admin maupun saat merender data impor.
3. Memperketat semua field upload gambar: whitelist ekstensi JPG/JPEG/PNG/WebP selain validasi MIME/ukuran yang sudah ada, serta proteksi manipulasi path file Filament. Pengujian menolak ekstensi executable, payload PHP yang menyamar, SVG, file terlalu besar, dan penetapan path milik record lain.
4. Memastikan gambar/logo menggunakan `Storage::disk('public')` meskipun default disk privat.
5. Menambahkan Trusted Hosts dan security headers. CSP publik membatasi script ke origin sendiri/nonce, memblokir object/frame pihak lain dan eval. Nonce ditambahkan ke **kode template tepercaya sebelum data pengguna dirender**, serta dihubungkan ke Vite/Livewire. Header admin menggunakan `no-store`.
6. Mempertahankan worker upload, avatar bawaan, dan style dinamis Filament agar desain serta fungsi admin tetap bekerja. Apache `.htaccess` menghapus X-Powered-By dan memberi `nosniff` pada aset statis.
7. Menambahkan `php artisan vanilindo:preflight` untuk menolak konfigurasi debug, HTTP/insecure cookie, key tidak valid, database/migrasi/admin/aset/storage yang belum siap. Flag `--local` tidak boleh melonggarkan HTTP pada hostname remote.

## Alert scanner yang masih perlu dibaca

**Medium: `CSP: script-src unsafe-eval` pada admin/login.** Filament menggunakan ekspresi JavaScript Alpine yang memerlukan evaluator tersebut. Halaman publik tidak mengizinkannya; script inline tetap harus memiliki nonce. Menghapus allowance tanpa mengganti evaluator/framework akan merusak admin. Dokumentasi [Livewire CSP](https://livewire.laravel.com/docs/4.x/csp) menjelaskan batas parser CSP-safe; [plugin CSP Filament](https://filamentphp.com/plugins/asign-csp-nonce) mencatat ketidakcocokan parser tersebut dengan ekspresi Filament. Plugin itu hanya dibaca sebagai bukti kompatibilitas, tidak dipasang. Temuan ini belum dinyatakan terselesaikan atau disembunyikan. User telah menerima pengecualian khusus admin ini setelah penjelasan. Alert tetap dicatat; acceptance ini tidak menghapus hasil scanner.

Alert low/informational lainnya:

- Cookie `XSRF-TOKEN` tidak HttpOnly: token CSRF memang dibaca JavaScript; cookie sesi autentikasi tetap HttpOnly/SameSite. Produksi harus mengaktifkan Secure setelah HTTPS tepercaya.
- “Big Redirect”: body hanya halaman redirect generik ke anchor/login, tanpa data privat; bukan kebocoran konten admin pada pengujian.
- X-Powered-By dan `nosniff` pada robots.txt: berasal dari server PHP built-in lokal yang tidak membaca `.htaccess`. Paket mengandung konfigurasi Apache untuk keduanya. Server rehearsal paket dengan `expose_php=Off` sudah tidak mengirim header versi PHP; konfigurasi Apache hosting masih harus diperiksa live.
- Comment mencurigakan/modern app/session identification: komentar library dan identifikasi perilaku aplikasi; tidak membuktikan exploit.
- “User-controlled HTML attribute” untuk `products_page=111`: scanner mencocokkan angka 111 dengan label carousel `111 of 250`. Nomor slide dihasilkan loop server; pengujian XSS/escaping dan scan tidak menemukan eksekusi payload.

## Paket deployment

Paket tersedia di `dist/vanilindo-release-2026-10-10.zip`: sekitar 31,9 MiB compressed, 73,9 MiB extracted; 14.387 entry. SHA-256 dan metadata berada di `dist/release-manifest.json`. Archive lolos CRC dan dibuat di staging terpisah memakai vendor produksi saja. Tidak ada `.env`, `.git`, node_modules, PHPUnit/dev vendor, database lokal, symlink storage, atau cache konfigurasi dengan secret. Data upload harus disimpan terpisah pada server dan tidak ditimpa deployment berikutnya.

Rehearsal paket dilakukan **setelah** archive dibuat, dengan env/media audit privat yang hanya ditambahkan ke staging; archive tetap tanpa credential/data audit.

Paket tersebut sudah diunggah melalui Safari/cPanel ke `/home/indone57/vanilindo` dan diekstrak di sana. UI upload menunjukkan 100% / 31,93 MB complete; hasil ekstraksi dan daftar folder menunjukkan `app`, `bootstrap`, `config`, `database`, `public`, `resources`, `routes`, `storage`, `vendor`, `artisan`, serta Composer. Source berada di luar `/public_html`; pada tahap 10 Oktober domain belum melayani aplikasi. Aktivasi 12 Oktober dijelaskan di atas. CRC dan SHA-256 archive lokal diperiksa ulang sebelum upload. Belum ada pemeriksaan checksum atau preflight melalui CLI server.

## Catatan historis penghalang aktivasi pada 10 Oktober (status terbaru di atas)

- Domain `indonesiavanillas.com` memakai document root `/public_html` dan merupakan Main Domain. Seluruh source Laravel tidak boleh diletakkan terbuka di root ini; root perlu diarahkan ke `/home/indone57/vanilindo/public`, atau bootstrap public_html harus disesuaikan dengan source privat.
- cPanel menampilkan sertifikat **self-signed**, sehingga produksi HTTPS tepercaya belum lulus. Belum ada sertifikat baru yang diterbitkan/dibeli dan tidak ada peringatan browser yang dilewati.
- Terminal/SSH Access/Cron Jobs tidak terlihat pada daftar Tools; akses CLI perlu dikonfirmasi support untuk migrasi, storage symlink, dan pembuatan admin.
- PHP Selector sudah diterapkan ke **8.3 (current)**. Per Domain Settings menunjukkan `indonesiavanillas.com` memakai **8.3 (account default)** melalui CloudLinux PHP Selector. Display errors Off, log errors On, memory limit 128M, upload 16M dan post 32M. OPcache diaktifkan; Selector melaporkan pdo_mysql yang konflik dilewati karena nd_pdo_mysql tersedia. PHP CLI dan boot aplikasi sebenarnya tetap perlu diverifikasi.
- Belum ada database hosting. Pembuatan password DB/admin melalui UI memerlukan user mengisi dan submit sendiri sesuai aturan Computer Use; password tidak perlu dikirim lewat chat.

Akun produksi yang diminta user: nama `adminvanilindo`, email `vanilindo@gmail.com`. Password acak 192-bit dan SQL hash bcrypt cost 12 disiapkan privat pada `dist/admin-credentials.txt` dan `dist/create-admin.sql` (permission 600, diabaikan Git, tidak termasuk ZIP). Akun belum dibuat pada database hosting. SQL hanya boleh diimpor ke database aplikasi yang sudah dimigrasi dan tidak mempromosikan akun yang sudah ada.

Kontrol Safari sempat gagal berulang dengan `timeoutReached`, lalu pulih setelah user memfokuskan tab cPanel. Pemeriksaan Google Public DNS menghasilkan NXDOMAIN (status 3) untuk domain utama dan www. Registry resmi Verisign .com kemudian mengonfirmasi domain telah terdaftar pada 21 September 2026, expires 21 September 2027, memakai NS5.KUNCIHOST.COM dan NS6.KUNCIHOST.COM, tetapi berstatus **client hold** serta client transfer prohibited. [ICANN](https://www.icann.org/resources/pages/epp-status-codes-2014-06-16-en) menjelaskan clientHold mencegah domain aktif di DNS dan perlu diselesaikan dengan registrar. Penyebab hold belum diketahui. Klien Area KunciHost menampilkan domain Aktif dan peringatan email akun belum diverifikasi; belum terbukti bahwa peringatan email akun itu merupakan penyebab hold domain.

Panduan tersedia pada [kuncihost-deployment-guide.md](kuncihost-deployment-guide.md); draft tiket konkret tersedia pada [kuncihost-support-ticket.md](kuncihost-support-ticket.md) dan sudah diisikan ke formulir Technical Support di Safari, tanpa password. **Tiket belum dikirim; user meminta penjelasan/draft terlebih dahulu.** Pemilihan layanan terkait belum terverifikasi setelah Safari berpindah jendela. UI terakhir sempat tertahan pemeriksaan persetujuan otomatis karena kuota, kemudian dapat dibuka lagi setelah user meminta lanjut. Situs belum diaktifkan karena prasyarat hosting belum beres. Gate aplikasi yang diuji telah lulus dengan pengecualian CSP admin yang diterima user.

## Bukti dan pengulangan

Bukti tanpa password ada di `docs/audit-evidence/2026-10-10/`: tes/build/lint/audit dependency, HTTP probes, screenshot, hasil beban, preflight, dan laporan ZAP lengkap. Credential audit, APP_KEY, file env, serta session database ZAP tidak disalin ke sana.

```sh
# Gunakan PHP 8.3 dan Composer yang terverifikasi.
php artisan optimize:clear
php artisan test
php vendor/bin/pint --test app/Models/SiteSetting.php app/Providers/AppServiceProvider.php \
  app/Http/Middleware/SecurityHeaders.php app/Console/Commands/ProductionPreflight.php \
  app/Filament/Resources/SiteSettings/Schemas/SiteSettingForm.php \
  tests/Feature/SecurityTest.php tests/Feature/ProductionPreflightTest.php bootstrap/app.php
php .tools/composer.phar audit
npm audit
npm run build
php artisan vanilindo:preflight --local
# Hanya pada server/data audit loopback, bukan database produksi:
python3 scripts/load_test.py --concurrency 20 --requests 1000 --output /tmp/load.json
node scripts/browser_smoke.cjs
python3 scripts/build_release.py
```

Browser smoke memerlukan Playwright/Chrome dan fixture account lokal dalam file privat `browser-credentials.json`, bukan akun hosting. Generator beban membutuhkan slug/data fixture yang dijelaskan di laporan ini.
