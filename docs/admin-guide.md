# Panduan admin Vanilindo

1. Buka `/admin` dan masuk dengan akun admin yang dibuat melalui `php artisan vanilindo:create-admin` pada terminal interaktif. Jangan kirim password lewat chat atau simpan di repository.
2. **Content Blocks** mengubah judul, paragraf, dan gambar untuk bagian tetap di Home, About, Products, Blog, dan Contact. Kunci serta label blok tidak perlu diubah. Ganti semua teks `[Temporary]` sebelum rilis.
3. **Products** untuk menambah produk, mengubah nama, slug URL, ringkasan, deskripsi, foto, dan SEO. Produk baru tidak terlihat publik sampai **Visible on website** dinyalakan. Gunakan slug huruf kecil, angka, dan tanda hubung; mengganti slug mengubah URL.
4. **Blog** (`/admin/articles`) untuk mengelola kartu blog. Klik **Tambah blog**, isi **Judul**, **Link blog** (diawali `https://` atau `http://`), unggah **Gambar**, lalu isi **Deskripsi**. Aktifkan **Tampilkan di website**; **Jadwal tayang** boleh dikosongkan untuk langsung tampil atau diisi waktu mendatang dalam WIB. Simpan. Slug dibuat otomatis, sehingga tidak perlu mengisi URL internal atau artikel panjang. Klik baris blog untuk mengedit, matikan status tampil untuk mengarsipkan, atau gunakan **Hapus blog** pada halaman edit untuk menghapus dengan konfirmasi.
5. **Site Settings** untuk nama merek, logo, email, dua nomor WhatsApp, alamat, tautan Instagram, dan deskripsi SEO umum.

Unggah JPG, PNG, atau WebP. Batas form: 4 MB per gambar konten/produk/artikel, 2 MB untuk logo. Isi deskripsi alternatif gambar (alt text) agar lebih mudah diakses. File disimpan pada disk `public`; `php artisan storage:link` harus berhasil pada server agar gambar dapat ditampilkan.

Blog yang aktif tampil sebagai satu kartu besar dengan gambar horizontal, judul, dan deskripsi sesuai gaya Canva. Semua blog yang sudah tayang bisa dibuka dengan panah kiri/kanan, swipe di HP, atau tombol panah keyboard saat slider difokuskan. Klik kartu langsung menuju link blog dalam tab yang sama. Judul dan deskripsi panjang diringkas secara visual agar ukuran kartu tetap rapi; teks lengkap tetap tersimpan di admin. Panah disembunyikan jika hanya ada satu blog.

Artikel lama tanpa link tetap dapat dibaca melalui halaman detailnya. Untuk mengubahnya menjadi kartu bertautan, edit melalui menu Blog dan lengkapi empat isian utama. Produk dapat diarsipkan dengan mematikan status publikasi. Editor konten tidak bisa menambah atau menghapus struktur section tetap, sehingga tata letak tetap konsisten.
