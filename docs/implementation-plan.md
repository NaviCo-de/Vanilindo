# Rencana implementasi Vanilindo

## Tujuan

Membangun website Vanilindo sesuai referensi Canva dan panel admin agar klien dapat mengubah teks, foto, produk, serta artikel tanpa menyentuh kode.

## Halaman publik

- Home: hero, keunggulan, jenis vanilla, nilai perusahaan, ajakan menghubungi.
- About Us: profil Vanilindo dan ANCA Organics, asal vanilla dari Papua, tujuan perusahaan.
- Products: Planifolia dan Tahitensis, daftar produk yang datanya dikelola dari admin.
- Blog: daftar dan detail artikel yang dikelola dari admin.
- Kontak: informasi dan tautan kontak, ditampilkan pada navigasi dan footer.

Desain Canva berisi beberapa teks template hotel dan teks artikel sementara. Semua placeholder perlu diganti sebelum rilis. Struktur halaman dan interaksi akan disesuaikan untuk desktop serta ponsel.

## Panel admin

- Login admin dengan akses terbatas.
- Pengaturan konten tetap untuk Home, About Us, dan informasi kontak.
- Tambah, ubah, terbitkan, dan arsipkan produk serta artikel.
- Unggah dan ganti gambar, termasuk teks alternatif gambar.
- Kolom dasar SEO: judul, deskripsi, slug, dan gambar sosial pada konten yang relevan.

Editor mengubah konten di dalam struktur desain yang sudah dibuat. Perubahan tata letak halaman dan pengelolaan pesanan/pelanggan tidak termasuk lingkup awal.

## Stack dan prasyarat

- Laravel untuk website dan backend.
- Filament untuk panel admin.
- Blade dan Tailwind CSS untuk tampilan publik.
- MySQL pada cPanel untuk data.
- Node.js dan Composer untuk proses build lokal.

Laravel 13 memerlukan PHP 8.3 atau lebih baru. Versi PHP aktif, ekstensi yang tersedia, kuota disk, document root domain, serta akses Terminal/SSH pada akun cPanel harus diperiksa sebelum versi framework dikunci dan deployment dirancang. Jangan arahkan document root Laravel ke akar proyek; hanya direktori `public` yang boleh dilayani web server.

## Tahapan dan commit

1. Audit desain, siapkan repository dan toolchain. Commit dokumentasi dan scaffold.
2. Buat model data, migration, seed contoh, autentikasi, dan admin Filament. Commit setelah alur CRUD diverifikasi.
3. Bangun halaman publik berdasarkan Canva. Commit per kelompok halaman yang selesai.
4. Integrasikan konten admin, media, SEO, dan tujuan kontak. Commit setelah alur publik dan admin terhubung.
5. Uji desktop/mobile, aksesibilitas dasar, akses admin, tautan, serta dokumentasi deployment dan penggunaan admin. Commit dan push hasil akhir.

Commit menggunakan identitas Git lokal pemilik repo. `.env`, kredensial, file sementara, dan konten rahasia tidak boleh dikomit.

## Informasi yang masih diperlukan

- Screenshot cPanel: versi PHP, kuota disk, serta menu Terminal/SSH.
- Logo dan foto produk asli bila tersedia. Teks dan gambar yang tampil di Canva sudah dapat dibaca; sebagian aset gambar hanya tersedia sebagai versi tampilan, sehingga file sumber tetap lebih baik untuk hasil akhir.
- Konfirmasi bahwa teks profil dan produk yang terlihat di Canva sudah final, serta pengganti untuk teks template hotel dan artikel sementara; bahasa utama website.
- Tujuan tombol kontak: WhatsApp, email, atau formulir; nomor/alamat yang disetujui untuk publik.
- Akun admin awal yang akan digunakan saat serah terima. Password dibuat melalui proses aman, bukan dikirim lewat chat atau disimpan dalam Git.
