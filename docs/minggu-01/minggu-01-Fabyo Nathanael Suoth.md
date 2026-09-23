# 1.3 Read → Break → Fix → Build

__Nama : Fabyo Nathanael Suoth\
NIM : 10241027__

---

## Ringkasan
Dari eksplorasi berkas-berkas inti Laravel, ternyata alur kerja aplikasi ini dimulai dari public/index.php sebagai pintu masuk atau entry point setiap request yang masuk. Berkas ini bertugas memuat Composer lalu menjalankan proses bootstrap aplikasi lewat bootstrap/app.php, sebelum akhirnya request tersebut diproses hingga menghasilkan response untuk dikirim balik ke browser. Masuk ke bootstrap/app.php, terlihat jelas pembagian tugasnya: ada bagian yang mengarahkan ke berkas routing (routes/web.php dan routes/console.php), ada bagian withMiddleware yang khusus mengatur middleware, dan ada bagian withExceptions yang menangani exception atau error yang mungkin terjadi. Ketiganya dipisah rapi supaya konfigurasi aplikasi lebih terstruktur. Kemudian di routes/web.php, ditemukan route utama yang menampilkan halaman selamat datang lewat view('welcome'). Setelah teksnya diubah dan browser dimuat ulang, tampilan halaman pun ikut berubah, membuktikan bahwa route inilah yang mengontrol apa yang tampil di halaman utama. Hal ini juga dikonfirmasi lewat perintah php artisan route:list, yang menampilkan daftar route yang sudah terdaftar dan dikenali Laravel, termasuk route / yang merujuk ke routes/web.php baris ke-5.

Setelah memahami alurnya, dilanjutkan dengan tahap Break, yaitu sengaja merusak beberapa konfigurasi untuk melihat bagaimana Laravel bereaksi. Saat mengganti nama .env menjadi .env.bak, Laravel jadi kehilangan seluruh konfigurasi pentingnya seperti akses database dan kunci API, sehingga aplikasi gagal total dijalankan, sesuai dugaan awal bahwa tanpa file environment, program otomatis tidak bisa berjalan. Selanjutnya, saat kolom APP_KEY dikosongkan, website langsung tidak bisa diakses karena kunci ini ternyata dipakai sebagai dasar enkripsi untuk mekanisme internal seperti session dan cookie, jadi begitu kosong maka mekanisme tersebut ikut lumpuh; awalnya dikira APP_KEY cuma alat pengaman data biasa, tapi nyatanya perannya jauh lebih krusial dari itu. Kemudian ketika DB_DATABASE dibiarkan kosong, sistem jadi kehilangan arah dalam menentukan database mana yang harus dipakai untuk menyimpan maupun mengambil data, akibatnya muncul error koneksi database yang jelas menunjukkan tidak ada database yang terpilih, yaitu SQLSTATE[3D000]: Invalid catalog name: 1046 No database selected (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: , SQL: select * from sessions where id = RBfWIYHlFgagrQVvFigQb9lXoAWd7sdznd0zdJRZ limit 1). Terakhir, setelah APP_DEBUG diubah menjadi false lalu percobaan nomor 3 diulangi, layar tidak lagi menunjukkan rincian kesalahan yang sebenarnya terjadi, baik itu karena masalah database, konfigurasi, atau bug lainnya, semuanya disembunyikan dari tampilan; dampaknya proses debugging jadi terasa lebih sulit dan memakan waktu dibanding saat mode debug menyala, dan yang tampil di layar hanyalah pesan singkat 500 Server Error tanpa informasi teknis apa pun.

---

## READ

1. `public/index.php`. Baca dari atas ke bawah. Tulis dalam 3 kalimat apa yang dilakukan berkas ini.
2. `bootstrap/app.php`. Identifikasi bagian mana yang mengurus route, mana yang mengurus middleware, mana yang mengurus exception.
3. `routes/web.php`. Temukan route yang menghasilkan halaman selamat datang. Ubah teksnya, muat ulang browser, pastikan berubah.
4. Jala php artisan route:list. Cocokkan keluarannya dengan `routes/web.php`.
---

### 1. `public/index.php`
Berkas ini merupakan titik awal ketika Laravel menerima request dari browser atau entry point. Berkas ini juga memuat Composer dan menjalankan proses bootstrap aplikasi melalui bootstrap/app.php. Setelah itu request dari browser ditangkap dan diteruskan hingga menghasilkan response yang dikirim kembali ke browser.

---

### 2. `bootstrap/app.php`
`commands: __DIR__.'/../routes/console.php',` Bagian ini mengatur route ke `routes/web.php`.

`->withMiddleware(function (Middleware $middleware): void {
        //
    })` Bagian ini yang mengurus middleware.

`->withExceptions(function (Exceptions $exceptions): void {
        //
    })` Bagian ini yang mengurus exception.

---

### 3. `routes/web.php`
`Route::get('/', function () {
    return view('welcome');
});` Teks awal yaitu welcome atau sambutan selamat datang, kemudian saya ubah menjadi `Route::get('/', function () {
    return view('Hallo Pengguna');
});` dan tampilan di browser juga ikut berubah menjadi sambutan untuk pengguna.

---

### 4. `php artisan route:list`

```
GET|HEAD  / ........................................................................................ routes/web.php:5
POST      _boost/browser-logs ............. boost.browser-logs › vendor/laravel/boost/src/BoostServiceProvider.php:98
GET|HEAD  storage/{path} storage.local › vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemServiceProvide…
PUT       storage/{path} storage.local.upload › vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemService…
GET|HEAD  up ............ vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:219
```

Terlihat keluaran dari artisan route:list ini ` GET|HEAD  / ........................................................................................ routes/web.php:5` yang mana terdaftar dan dikenali oleh laravel.

---

## BREAK

### 1. Ganti `.env` men `.env.bak`
Ketika file .env saya ubah namanya menjadi .env.bak, Laravel jadi kehilangan konfigurasi atau environtment utamanya yang menyimpan pengaturan penting seperti akses database, kunci API, dan sejenisnya tidak lagi terbaca oleh sistem, alhasil program gagal total untuk dijalankan, ini persis seperti dugaan saya sebelumnya tanpa file environment, program otomatis tidak akan mau berjalan.

---

### 2. Kosongkan n `APP_KEY` `.env`
Ketika kolom APP_KEY saya kosongkan, website langsung tidak bisa diakses, rupanya kunci ini dipakai sebagai dasar enkripsi untuk berbagai mekanisme internal seperti session dan cookie, jadi begitu kosong maka mekanisme tersebut ikut lumpuh, sebelumnya saya pikir APP_KEY cuma sekadar alat pengaman data biasa, tapi ternyata fungsinya jauh lebih krusial daripada itu.

---

### 3. `DB_DATABASE` menjadi nama yang tidak ada
Ketika DB_DATABASE dibiarkan kosong, sistem jadi kehilangan arah dalam menentukan database mana yang harus dipakai untuk menyimpan maupun mengambil data, akibatnya muncul error koneksi database yang jelas menunjukkan tidak ada database yang terpilih.

```
SQLSTATE[3D000]: Invalid catalog name: 1046 No database selected (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: , SQL: select * from `sessions` where `id` = RBfWIYHlFgagrQVvFigQb9lXoAWd7sdznd0zdJRZ limit 1)
```

---

### 4. `APP_DEBUG=false`, lalu ulangi nomor 3
Setelah APP_DEBUG saya false kan, layar tidak lagi menunjukkan rincian kesalahan yang sebenarnya terjadi baik itu karena masalah database, konfigurasi, atau bug lainnya, semuanya disembunyikan dari tampilan, dampaknya proses debugging jadi terasa lebih sulit dan memakan waktu dibanding saat mode debug menyala dan yang tampil di layar hanyalah pesan singkat `500 Server Error`, tanpa informasi teknis apa pun.