# Week 2

**Mata Kuliah: Pemrograman Web**  
**Pertemuan: 2**  
**Tanggal: 10 September 2026**  
**Topik: Route Laravel**

---

## READ

### 1. Baris mana di routes/web.php yang menangkapnya?

Route /tentang ditangkap oleh baris 9 di file routes/web.php, yaitu:

```php
Route::get('/tentang', function () {
    return view('tentang');
});
```

Route tersebut menggunakan method GET. Saat halaman /tentang dibuka, Laravel menjalankan fungsi tersebut dan menampilkan view tentang.

### 2. Kalau ditangani controller, berkas dan method mana?

Route /tentang saat ini tidak ditangani oleh controller, tetapi langsung menggunakan closure di file routes/web.php. Oleh karena itu, belum ada berkas controller dan method controller yang digunakan untuk route tersebut.

### 3. View mana yang dikembalikan? Di path apa persisnya?

View yang dikembalikan adalah view `tentang`, sesuai dengan kode `return view('tentang');`. File view tersebut berada di path:

`resources/views/tentang.blade.php`

### 4. Layout apa yang membungkusnya?

View `tentang.blade.php` tidak dibungkus oleh layout Blade apa pun. File tersebut berdiri sendiri sebagai HTML lengkap dan tidak menggunakan `@extends` atau `@include`.

### 5. Jalankan php artisan route:list --path=tentang. Cocok dengan analisis Anda?

Ya, cocok. Hasilnya menunjukkan:

GET|HEAD tentang ... routes/web.php:10

Artinya route /tentang menggunakan method GET dan didefinisikan pada baris 9 di web.php, sesuai analisis sebelumnya.

## BREAK

### 1. Ubah Route::get menjadi Route::post pada route daftar mata kuliah

Prediksi: akan terjadi error saat membuka halaman daftar mata kuliah melalui link.

Realita: route daftar mata kuliah sekarang menggunakan `POST`, bukan `GET`.

```text
POST courses ... courses.index -> CourseController@index
```

Saat link dibuka, browser mengirim `GET`. Namun, route hanya menerima `POST`. Karena method-nya berbeda, Laravel menampilkan error `405 Method Not Allowed`.

### 2. Ubah nama view di return view(...) menjadi yang tidak ada

prediksi: akan muncul eror karena view akan bingung akan membuka file yang mana

realita: muncul error `500 | Server Error` karena Laravel tidak menemukan file view yang dipanggil.

### 3. Hapus ->name('courses.show'), lalu muat halaman yang memakai route('courses.show')

prediksi: akan mengalami eror akibat ketidakselarasan

realita: karena route mencarinya berdasarkan nama, tapi karena nama dari rute tersebut dihapus maka terjadi eror

### 4. Pindahkan route `/courses/{course}` ke atas `/courses/create`, lalu buka `/courses/create`

prediksi: halaman create mungkin tetap terbuka karena Laravel bisa membedakan URL statis dan URL yang memiliki parameter.

realita: route `/courses/{course}` menangkap kata `create` sebagai nilai parameter `course`. Akibatnya, request tidak masuk ke route create, tetapi diproses oleh method `show`. Karena tidak ada course dengan ID `create`, halaman akhirnya menampilkan error `404 Not Found`. Urutan route ternyata sangat berpengaruh.

### 5. Ganti `{{ $nama }}` menjadi `{!! $nama !!}`, lalu isi `$nama` dengan `<script>alert('XSS')</script>`

prediksi: saya mengira alert tidak akan muncul karena Blade tetap akan menganggap isi `$nama` sebagai teks biasa.

realita: prediksi saya sedikit meleset. Dengan sintaks `{!! !!}`, Blade tidak melakukan escaping terhadap HTML. Tag `<script>` benar-benar diproses oleh browser sehingga alert XSS muncul di layar. Ini menunjukkan bahwa `{!! !!}` harus digunakan dengan sangat hati-hati, terutama jika datanya berasal dari pengguna.

### 6. Hapus `@vite(...)` dari layout

prediksi: halaman mungkin menjadi rusak total atau tidak bisa dibuka karena pemanggilan `@vite` dihapus.

realita: halaman tetap bisa dibuka, tetapi file CSS dan JavaScript dari Vite tidak lagi dimuat. Akibatnya, tampilan kembali tanpa styling atau fitur JavaScript yang bergantung pada aset tersebut. Jadi, yang bermasalah adalah aset tampilannya, bukan route atau proses render Blade.

### 7. Hentikan `npm run dev`, lalu muat ulang halaman

prediksi: halaman akan menampilkan error server karena Laravel tidak bisa menemukan aset dari Vite.

realita: Laravel masih dapat mengirim halaman, tetapi browser gagal mengambil aset dari development server Vite yang sudah dihentikan. Biasanya muncul peringatan atau error terkait koneksi ke server Vite, sehingga tampilan halaman tidak lagi sama seperti saat `npm run dev` masih berjalan. Ini berbeda dengan hasil `npm run build` yang menggunakan aset hasil build di folder `public/build`.

### 8. Panggil `route('courses.show')` tanpa mengirim parameter

prediksi: Laravel akan tetap membuat URL detail, tetapi nilai ID-nya mungkin kosong.

realita: Laravel langsung menampilkan error `Missing required parameter` karena route `courses.show` membutuhkan satu parameter, yaitu ID course. URL tidak dapat dibuat sebelum parameter tersebut diberikan, misalnya dengan `route('courses.show', $course['id'])`.
