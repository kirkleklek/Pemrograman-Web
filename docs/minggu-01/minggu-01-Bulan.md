  # **Week 1**

**Mata Kuliah: Pemrograman Web**
**Pertemuan: 1**
**Tanggal: 3 September 2026**
**Topik: Laravel Basic**

---

## **READ**

### **1. Bagian `bootstrap/app.php`**

Pada file `bootstrap/app.php` terdapat beberapa bagian utama yang berfungsi dalam proses awal aplikasi Laravel. `withRouting` digunakan untuk mengatur routing, `withMiddleware` digunakan untuk mengelola middleware, sedangkan `withExceptions` berfungsi untuk menangani exception atau error.

Secara sederhana, file ini menjadi salah satu tempat Laravel menyiapkan konfigurasi aplikasi sebelum sebuah request diproses lebih lanjut.

### **2. Route halaman selamat datang**

Pada file `routes/web.php`, terdapat route berikut:

```php
Route::get('/', function () {
    return view('welcome');
});
```

Route tersebut berarti ketika pengguna mengakses alamat utama `/`, Laravel akan memanggil dan menampilkan view bernama `welcome`.

Saya kemudian mengubah isi teks pada file `resources/views/welcome.blade.php` menjadi **"Selamat Datang di LMS Eagan"**. Setelah halaman dimuat kembali, teks yang tampil ikut berubah sesuai dengan isi file yang telah diedit.

### **3. `php artisan route:list`**

Saya juga mencoba menjalankan perintah:

```bash
php artisan route:list
```

Namun, saat praktik terdapat kendala pada terminal ketika berpindah ke direktori project sehingga output route belum berhasil ditampilkan.

Walaupun begitu, berdasarkan isi file `routes/web.php`, dapat diketahui bahwa terdapat route dengan method `GET` pada URL `/` yang mengarah ke view `welcome`. Jadi secara struktur, route tersebut memang sudah terdaftar pada file routing Laravel.

---

## **BREAK**

1. Ketika file `.env` diubah namanya menjadi `.env.bak`, Laravel tidak dapat membaca konfigurasi environment yang dibutuhkan oleh aplikasi. Akibatnya, beberapa pengaturan seperti database, API, dan konfigurasi aplikasi lainnya tidak dapat digunakan dengan benar.

2. Jika nilai `APP_KEY` dihapus, fitur Laravel yang berhubungan dengan enkripsi dapat terganggu. Hal ini dapat memengaruhi session, cookie, maupun data lain yang membutuhkan proses enkripsi.

3. Apabila `DB_DATABASE` diisi dengan nama yang salah atau dikosongkan, Laravel tidak dapat menentukan database yang akan digunakan. Akibatnya, koneksi ke database dapat gagal dan aplikasi akan menampilkan error.

4. Jika `APP_DEBUG=false`, Laravel tidak akan menampilkan informasi error secara lengkap kepada pengguna. Biasanya yang terlihat hanya pesan error umum seperti halaman 500. Pengaturan ini lebih aman digunakan pada production, tetapi kurang membantu saat proses pengembangan.

Menurut saya, bagian nomor 4 cukup penting dalam proses belajar. Ketika `APP_DEBUG=true`, informasi error dapat terlihat lebih detail sehingga sumber masalah lebih mudah ditemukan dan diperbaiki.
