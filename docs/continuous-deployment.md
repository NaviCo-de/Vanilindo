# CD cPanel tanpa SSH

Workflow berada di `.github/workflows/cpanel-cd.yml`. Deployment diaktifkan lewat repository variable `CPANEL_CD_ENABLED` setelah credential dan kemampuan FTPS hosting diverifikasi. Perubahan CD tidak mengubah source desain; pembaruan blog dan widget WhatsApp mengikuti `main` terbaru.

Verifikasi lokal 1 November 2026 setelah sinkronisasi `main` (`2344ab1`): 40 tes aplikasi / 294 assertion dan 31 tes CD lulus; audit Composer/npm bersih; workflow lolos actionlint 1.7.12. Paket produksi berisi 14.384 file tanpa vendor development. Rehearsal nyata berhasil membuka `/up`, `/`, `/admin/login`, memeriksa 79 aset, serta memastikan SQLite dan runtime menggunakan folder bersama. Ini belum merupakan hasil eksekusi GitHub Actions atau deployment FTPS ke KunciHost.

## Alur setelah diaktifkan nanti

PR ke `main` menjalankan audit dependency, tes aplikasi, tes transaksi deployment, build frontend, lalu membuat paket produksi tanpa dependency development. Paket tersebut juga dijalankan pada server PHP loopback dengan SQLite sementara, menggunakan susunan folder cPanel yang sama.

Push/merge ke `main` menjalankan pemeriksaan yang sama. Deployment hanya berjalan jika repository variable `CPANEL_CD_ENABLED` bernilai `true` dan environment `production` mengizinkannya. `workflow_dispatch` juga harus menggunakan branch `main`. PR tidak memperoleh credential hosting atau melakukan deployment.

Deployment memakai explicit FTPS dengan pemeriksaan sertifikat dan enkripsi koneksi data. Tidak memerlukan SSH, Composer, atau Node di hosting. Tidak memasang endpoint web untuk menjalankan Artisan.

1. Validasi seluruh file paket dengan manifest SHA-256; tolak `.env`, SQL, SQLite, runtime, symlink, dan file yang tidak terdaftar.
2. Ambil lock deployment dan bandingkan fingerprint migration dengan baseline server. Perbedaan schema ditahan sebelum upload aplikasi.
3. Upload dependency dan source produksi ke direktori release privat yang baru. Website lama tetap melayani pengunjung selama tahap ini.
4. Simpan backup file publik yang akan diganti ke direktori privat. Aktifkan maintenance singkat, pasang aset, lalu ganti `public_html/index.php` terakhir.
5. Periksa HTTPS `/up`, halaman utama, login admin, identitas release, hash aset publik, serta penolakan akses file privat. Ada retry terbatas untuk keterlambatan OPcache.
6. Bila pemeriksaan gagal, coba pulihkan file dan index sebelumnya. Keberhasilan menyimpan state baru; dua release terkelola terbaru dipertahankan. Sebelum upload berikutnya, CD membersihkan release paling lama yang tidak aktif, agar tidak menumpuk tiga vendor sekaligus pada kuota 500 MB. Source aktif selalu dipertahankan selama aktivasi/rollback; source manual lama di luar `.cd` tidak dihapus.

`.env`, APP_KEY, database, akun admin, sesi, log, dan foto upload yang sudah ada tidak diganti. CD hanya mengelola `storage/.htaccess` pada direktori media. Aset lama di luar paket tidak dihapus, agar pengunjung yang masih membuka halaman lama tidak kehilangan aset.

## Persiapan aktivasi, dilakukan nanti

**Dukungan FTPS KunciHost belum diuji langsung.** Sebelum mengaktifkan CD, pastikan akun FTPS yang dipakai dapat melihat **kedua folder sejajar** `vanilindo` dan `public_html`, mempunyai izin tulis pada keduanya, dan mendukung MLSD, SITE CHMOD, serta rename lintas direktori secara atomik dengan penggantian file tujuan. Akun yang dibatasi hanya ke `public_html` tidak cukup. Pastikan passive TLS data transfer bekerja dengan client Python ini; jangan menonaktifkan verifikasi sertifikat jika ada masalah.

Nama host harus sesuai sertifikat TLS server FTP, biasanya hostname server dari cPanel → FTP Accounts → Configure FTP Client; jangan mengasumsikan nama domain website juga benar untuk FTPS. FTPS explicit biasanya memakai port 21, berbeda dari SFTP/SSH.

Masukkan secret berikut lewat Settings → Secrets and variables → Actions. Jika admin repository menyiapkan environment **production**, secret dapat dipindahkan ke environment tersebut. Workflow hanya memberikan secret pada langkah deployment, setelah build selesai:

| Secret | Nilai |
| --- | --- |
| `CPANEL_FTPS_HOST` | Host FTPS dengan sertifikat valid, tanpa `https://` |
| `CPANEL_FTPS_USER` | Username FTP yang melihat direktori induk kedua folder |
| `CPANEL_FTPS_PASSWORD` | Password akun FTP; jangan taruh di source atau chat |

Repository variables:

| Variable | Nilai |
| --- | --- |
| `CPANEL_CD_ENABLED` | Awalnya tidak diisi atau `false`; ubah `true` setelah seluruh persiapan selesai |
| `CPANEL_FTPS_PORT` | Opsional, default `21` |
| `CPANEL_FTP_ROOT` | Opsional, default `/` jika folder sejajar terlihat langsung dari root login FTP |

Jika FTP memperlihatkan filesystem absolut, root biasanya `/home/indone57`; verifikasi dari directory listing terlebih dahulu. Adapter ini sengaja memakai nama folder `vanilindo` dan `public_html` sesuai deployment sekarang. Private `.env` harus tetap mempunyai `PUBLIC_STORAGE_PATH=/home/indone57/public_html/storage` dan storage runtime yang dapat ditulis PHP. CD tidak mengunggah atau membaca isi `.env`.

GitHub otomatis membuat environment `production` ketika job deployment pertama berjalan. Mengatur proteksi environment melalui API memerlukan akses admin; akun CLI saat persiapan ini hanya mempunyai akses write. Admin repository dapat menambahkan pembatasan branch environment ke `main` dan aturan review yang sesuai. Workflow sendiri tetap membatasi deployment ke `main`. Pin action memakai commit SHA dan permission token hanya `contents: read`. Cek kuota menit/storage GitHub Actions sebelum aktivasi; konfigurasi CD ini tidak mengubah paket atau billing apa pun.

Pastikan backup penuh cPanel/database tersedia dan kapasitas disk cukup untuk source manual lama, release aktif, release staging, serta backup publik. Release sebelumnya yang sudah tidak aktif dibersihkan sebelum staging berikutnya. cPanel saat persiapan 1 November 2026 mencatat sekitar 184 dari 500 MB terpakai; vendor baru sekitar 57 MiB payload dan sekitar 97 MiB alokasi lokal. Vendor berisi ribuan file: upload FTPS bisa beberapa menit. Upload privat memakai tiga koneksi worker dan satu koneksi kontrol. Durasi maintenance hanya tahap aktivasi aset publik, bukan seluruh upload vendor; ini bukan jaminan zero downtime.

Jalankan build saja terlebih dahulu dengan `CPANEL_CD_ENABLED` tetap nonaktif. Setelah hasil hijau dan kemampuan FTPS sudah diverifikasi, aktifkan variable lalu jalankan workflow dari `main`. Verifikasi login, CRUD, dan upload admin pada deployment pertama. Kredensial admin yang sudah ada tetap digunakan.

## Batas migrasi database

Tanpa SSH atau fasilitas perintah server yang sudah diverifikasi, CD ini **tidak menjalankan migration/seed** di produksi. Perubahan isi admin tidak membutuhkan deployment. Perubahan kode biasa dengan fingerprint migration yang sama bisa otomatis; perubahan schema memerlukan langkah manual.

Pada deploy pertama, baseline berasal dari file `vanilindo/database/migrations/*.php` yang sudah terpasang. Setelahnya baseline berasal dari `vanilindo/.cd/state.json`. Fingerprint file membatasi perubahan kode migration; ia bukan bukti audit schema database.

Jika migration berubah, biarkan CD nonaktif. Backup database, siapkan dan review SQL migration berdasarkan implementasi Laravel, jalankan melalui phpMyAdmin atau fasilitas perintah resmi hosting, lalu verifikasi schema dan tabel `migrations`. Setelah hasil terverifikasi dan perubahan kompatibel dengan kode lama, operator dapat memperbarui `migration_hash` dalam state privat dengan nilai `manifest.json` dari paket yang sama. Untuk deployment pertama tanpa state, baseline file migration lama perlu diselaraskan setelah verifikasi database. Jangan sekadar menyalin hash untuk melewati gate. Jalankan deploy lagi dengan run attempt/release ID baru. Migration destruktif memerlukan rencana rollback database tersendiri; rollback kode tidak membatalkan SQL.

## Pemeriksaan lokal

Dari root repository, dengan PHP 8.3, Composer 2, Node 24, dan Python 3.13:

```sh
composer validate --strict
composer install --prefer-dist --no-interaction
npm ci
composer audit --locked
npm audit
npm run build
php artisan test
python3 -m unittest discover -s tests/deployment -v
```

Pilih release ID berupa SHA commit 40 digit, boleh ditambah `-<run-id>-<attempt>` numerik. Output harus direktori baru:

```sh
python3 scripts/cd/bundle.py --output dist/cd/release-local --release-id "$(git rev-parse HEAD)-1-1"
python3 scripts/cd/deploy.py --bundle dist/cd/release-local --check
python3 scripts/cd/rehearse.py --bundle dist/cd/release-local
```

`--check` dan `rehearse.py` tidak terhubung ke hosting. Rehearsal membuat `.env` dan SQLite sintetis di direktori sementara, memeriksa shared env/storage/public path, menjalankan migrate/seed **hanya pada SQLite sementara**, serta mengakses HTTP loopback. Tes ini tidak menguji Apache/cPanel, MySQL produksi, atau kemampuan FTPS provider.

Untuk Composer PHAR atau PHP yang tidak ada di PATH, tersedia `--composer /path/composer.phar` dan `--php /path/php` pada builder, serta `--php` pada rehearsal. Paket dan hasil build masuk `dist/` yang diabaikan Git. Builder historical `scripts/build_release.py` dan `scripts/build_cpanel_deployment.py` tidak digunakan workflow ini.

## Pemulihan jika koneksi/runner terputus

Kegagalan biasa memicu rollback. Hard kill runner, kehilangan akses server, disk penuh, atau perubahan manual bersamaan dapat menggagalkan pemulihan otomatis. Dalam kondisi itu lock dipertahankan agar deployment berikutnya tidak menimpa bukti pemulihan. Maintenance mungkin masih aktif.

1. Nonaktifkan `CPANEL_CD_ENABLED`; pastikan tidak ada workflow deployment berjalan.
2. Lewat File Manager cPanel, buka `vanilindo/.cd/backups/<release-id>/journal.json`. Nilai `true` berarti file publik mempunyai versi lama di subfolder `public/`; nilai `false` berarti file tersebut baru dibuat. Salin backup file yang tercantum kembali ke path masing-masing dalam `public_html`, dan hapus hanya file baru yang tercantum `false`. Pulihkan `index.php` **terakhir**. Jangan hapus `public_html/storage` atau folder runtime.
3. Bila tersedia, pulihkan `previous-state.json` menjadi `vanilindo/.cd/state.json`. Jika deploy pertama belum memiliki state sebelumnya, hapus state yang mungkin sempat dibuat oleh attempt gagal tersebut.
4. Hapus `vanilindo/storage/framework/maintenance.php` **hanya** bila isinya bertanda `Vanilindo CD <release-id>` milik attempt yang dipulihkan. Jangan menghapus maintenance milik operator lain atau file `down` milik Laravel.
5. Pastikan HTTPS halaman utama, `/up`, login, dan aset sudah normal. Baru hapus folder lock kosong `vanilindo/.cd/lock`. Bersihkan hanya direktori release/backup attempt gagal setelah website terverifikasi. Jangan menghapus source lama `vanilindo/app`, `.env`, database, atau media.

Jika aktivasi belum dimulai dan belum ada journal, source aktif belum diganti; tetap periksa website dan pastikan runner berhenti sebelum menghapus lock. Jangan mengaktifkan lagi pipeline sebelum penyebab kegagalan diketahui.

## Patch dependency yang menyertai implementasi

Audit lokal 1 November 2026 memerlukan patch minimal: Livewire `4.4.6 → 4.4.8`, Symfony Mime `7.4.19 → 7.4.22`, Vite `7.3.6 → 7.3.7`, dan follow-redirects `1.16.0 → 1.16.2`. Manifest requirement utama tetap; lockfile menyimpan versi patch. Tidak ada perubahan template, CSS source, atau susunan desain.

Referensi: [Livewire advisory](https://github.com/advisories/GHSA-mhf7-xp6r-6qrc), [Symfony advisory](https://symfony.com/cve-2026-45070), [Python FTPS](https://docs.python.org/3/library/ftplib.html#ftplib.FTP_TLS), [GitHub deployment environments](https://docs.github.com/en/actions/concepts/workflows-and-actions/deployment-environments), [GitHub secure use](https://docs.github.com/en/actions/reference/security/secure-use).
