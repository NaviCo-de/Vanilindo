# Panduan admin Vanilindo

1. Buka `/admin` dan masuk dengan akun admin yang dibuat melalui `php artisan vanilindo:create-admin` pada terminal interaktif. Jangan kirim password lewat chat atau simpan di repository.
2. **Content Blocks** mengubah judul, paragraf, dan gambar untuk bagian tetap di Home, About, Products, Blog, dan Contact. Kunci serta label blok tidak perlu diubah. Ganti semua teks `[Temporary]` sebelum rilis.
3. **Products** untuk menambah produk, mengubah nama, slug URL, ringkasan, deskripsi, foto, dan SEO. Produk baru tidak terlihat publik sampai **Visible on website** dinyalakan. Gunakan slug huruf kecil, angka, dan tanda hubung; mengganti slug mengubah URL.
4. **Articles** untuk berita/artikel. Tulis body dalam editor Markdown. Artikel hanya muncul bila **Visible on website** aktif dan tanggal terbitnya tidak di masa depan.
5. **Site Settings** untuk nama merek, logo, email, dua nomor WhatsApp, alamat, tautan Instagram, dan deskripsi SEO umum.

Unggah JPG, PNG, atau WebP. Batas form: 4 MB per gambar konten/produk/artikel, 2 MB untuk logo. Isi deskripsi alternatif gambar (alt text) agar lebih mudah diakses. File disimpan pada disk `public`; `php artisan storage:link` harus berhasil pada server agar gambar dapat ditampilkan.

Hapus produk/artikel tidak disediakan dalam panel awal; matikan status publikasi untuk mengarsipkannya. Editor konten tidak bisa menambah atau menghapus struktur section tetap, sehingga tata letak tetap konsisten.
