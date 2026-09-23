# Week 1

**Mata Kuliah: Pemrograman Web**  
**Pertemuan: 1**  
**Tanggal: 3 September 2026**  
**Topik: Laravel Basic**

---

## READ

### 1. Bagian bootstrap/app.php

Pada file bootstrap/app.php ../bootstrap/app.php, ada tiga bagian penting. withRouting itu yang mengatur route, withMiddleware itu yang mengatur middleware, dan withExceptions itu yang mengatur error atau exception.

Jadi intinya, file ini adalah tempat Laravel mengatur alur aplikasi sebelum request diproses.

### 2. Route halaman selamat datang

Di routes/web.php ../routes/web.php, route yang membuat halaman selamat datang adalah:

```php
Route::get('/', function () {
    return view('welcome');
});
```

Artinya, kalau user membuka halaman utama `/`, Laravel akan menampilkan view `welcome`. Saya lalu ubah teks di resources/views/welcome.blade.php ../resources/views/welcome.blade.php menjadi "Selamat Datang di LMS Eagan", dan halaman berubah sesuai yang saya edit.

### 3. php artisan route:list

Saya mencoba menjalankan perintah php artisan route:list, tapi di terminal ini ada masalah saat pindah ke folder project, jadi outputnya tidak berhasil ditampilkan. Jadi saya tidak bisa bilang sudah valid dari terminal ini.

Namun, dari isi routes/web.php ../routes/web.php, jelas ada route GET / yang mengarah ke halaman welcome. Jadi route yang dibuat memang sesuai dengan file route yang ada.

---

## BREAK

1. Saat file .env diganti nama jadi .env.bak, Laravel tidak bisa menemukan konfigurasi environment yang dibutuhkan. Akibatnya aplikasi tidak tahu database, API, dan pengaturan penting lainnya.

2. Kalau APP_KEY dihapus, fitur seperti session dan cookie bisa rusak. Jadi aplikasi tidak bisa berjalan dengan benar dan login atau data pengguna bisa bermasalah.

3. Jika DB_DATABASE salah atau kosong, Laravel tidak tahu database mana yang harus dipakai. Maka aplikasi akan gagal terhubung ke database dan muncul error.

4. Kalau APP_DEBUG=false, aplikasi tidak akan menampilkan detail error. Jadi kita tidak tahu letak bugnya, dan biasanya hanya muncul halaman 500. Ini aman untuk server produksi, tapi sulit untuk debugging.

Nomor 4 paling penting, karena kalau APP_DEBUG=true, kita bisa melihat error lengkap dan langsung tahu masalahnya. Jadi untuk proses belajar dan pengembangan, APP_DEBUG=true lebih membantu.

---
