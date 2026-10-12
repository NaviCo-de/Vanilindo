# Tiket KunciHost yang disiapkan — belum dikirim

**Departemen:** Technical Support
**Subjek:** Domain clientHold dan persiapan deployment Laravel — indonesiavanillas.com

Halo tim KunciHost,

Saya ingin mengaktifkan aplikasi Laravel 13 + Filament 5 pada domain indonesiavanillas.com, akun cPanel indone57, paket NIAS ID-III 500mb.

Source dan vendor produksi sudah diunggah serta diekstrak di /home/indone57/vanilindo, di luar public_html. PHP Selector web telah dipilih ke 8.3. Aplikasi belum diaktifkan dan belum memiliki konfigurasi produksi atau database.

Mohon bantuan untuk hal berikut:

1. Registry resmi .com saat diperiksa mencatat status clientHold, meskipun Klien Area menampilkan domain Aktif sampai 21 September 2027. Nameserver tercatat NS5.KUNCIHOST.COM dan NS6.KUNCIHOST.COM, tetapi DNS publik masih NXDOMAIN. Mohon jelaskan penyebab hold dan langkah pemilik domain agar hold dapat dilepas. Jika membutuhkan verifikasi email registran, mohon kirimkan ulang petunjuk verifikasinya ke pemilik akun/domain.
2. Mohon konfirmasi apakah Terminal cPanel/SSH tersedia pada paket ini. Menu Terminal, SSH Access, dan Cron Jobs tidak terlihat. Kami memerlukan cara CLI yang didukung untuk PHP 8.3: migrasi database, storage:link, pembuatan admin interaktif, optimasi, dan command vanilindo:preflight. Jika akses tidak tersedia, mohon konfirmasi apakah support dapat membantu menjalankannya setelah konfigurasi privat selesai.
3. Mohon arahkan document root domain utama dan www ke /home/indone57/vanilindo/public. Saat ini Main Domain memakai /public_html. Jika perubahan tersebut tidak didukung, mohon jelaskan alternatif aman dengan source tetap berada di luar public_html.
4. Setelah DNS aktif, mohon pasang SSL tepercaya/AutoSSL untuk domain utama dan www; cPanel saat ini menampilkan sertifikat self-signed. Mohon pastikan symlink public/storage diizinkan, ekstensi Laravel/driver PDO MySQL tersedia, dan PHP CLI juga menggunakan versi 8.3.

Mohon tidak memasang aplikasi Laravel baru melalui Softaculous atau menimpa source yang sudah diunggah. Jangan membuat endpoint web publik untuk menjalankan Artisan. Jika ada biaya atau perubahan paket yang diperlukan, mohon informasikan terlebih dahulu tanpa melakukan pembelian atau perubahan berbayar.

Terima kasih.
