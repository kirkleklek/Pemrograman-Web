# 2.3 Read → Break → Fix → Build

__Nama : Fabyo Nathanael Suoth\
NIM : 10241027__

---

## Ringkasan


---

## READ

1. Baris mana di `routes/web.php` yang menangkapnya?
2. Kalau ditangani controller, berkas dan method mana?
3. View mana yang dikembalikan? Di path apa persisnya?
4. Layout apa yang membungkusnya?
5. Jalankan `php artisan route:list --path=tentang`. Cocok dengan analisis Anda?
---

### 1. `routes/web.php`
Route `/tentang` terdapat di folder `routes/web.php` tepatnya di baris kode:
```
Route::get('/tentang', function () {
    return view('tentang');
});
```

---

### 2. `controller`
Pada repository ini, `/tentang` tidak ditangani oleh Controller. Route tersebut langsung menggunakan Closure function:
```
function () {
    return view('tentang');
}
```

---

### 3. View yang dikembalikan
View yang dikembalikan adalah `return view('tentang');`, tepatnya laravel akan mencari view tentang di path `resources/views/tentang.blade.php` 

---

### 4. Layout yang membungkusnya
View `tentang.blade.php` menggunakan komponen Blade,
```
<x-layout title="Tentang">
    ...
</x-layout>
```
Jadi layout yang membungkusnya `x-layout`.

---

### 5. `php artisan route:list --path=tentang`
Setelah menjalankan perintah `php artisan route:list --path=tentang`, diperoleh route GET|HEAD tentang closure yang berada pada `routes/web.php`. Hasil tersebut sesuai dengan analisis sebelumnya bahwa request `/tentang` ditangani langsung oleh route pada `routes/web.php` dan tidak menggunakan controller.
    
---

## BREAK

### 1. Ubah `Route::get` menjadi `Route::post` pada route daftar mata kuliah
Prediksi awal saya bahwa perubahan `Route::get` menjadi `Route::post` pada route daftar mata kuliah akan menyebabkan route /courses hanya menerima request dengan metode POST. Sementara itu, ketika halaman /courses dibuka melalui browser, browser secara otomatis mengirimkan request menggunakan metode GET.
`routes/web.php`
```
Route::post('/courses', [CourseController::class, 'index'])
    ->name('courses.index');
```

Ketika program dijalankan dan saya membuka:
```
http://127.0.0.1:8000/courses
```
Laravel akan menolak request tersebut karena browser mengirimkan GET, sedangkan route hanya menerima POST. Halaman diprediksi menampilkan:

---

### 2. Ubah nama view di `return view(...)` menjadi yang tidak ada
Prediksi awal saya bahwa jika nama view pada `CourseController` diubah menjadi nama view yang tidak tersedia, Laravel akan tetap berhasil menjalankan route dan controller, tetapi akan mengalami masalah ketika controller mencoba menampilkan view tersebut.
`CourseController.php`
```
return view('courses.hancur', compact('courses'));
```

Ketika program dijalankan controller akan dipanggil terlebih dahulu, tetapi Laravel gagal menemukan file, sehingga akan muncul exception.

---

### 3. Hapus `->name('courses.show')`, lalu muat halaman yang memakai `route('courses.show')`
Prediksi awal saya bahwa menghapus `->name('courses.show')` tidak akan menghapus route `/courses/{id}` itu sendiri. Namun, nama route `courses.show` tidak lagi tersedia. 

Ketika program dijalankan Laravel akan memproses halaman daftar mata kuliah sampai menemukan kode karena Error muncul karena dalam view course.index itu memanggil course.show sedangkan di web.php itu cours.show tidak ada.

---

### 4. `APP_DEBUG=false`, lalu ulangi nomor 3
Prediksi awal saya yaitu `/courses/create` akan dianggap sebagai `/courses/{id}` karena route dinamis berada lebih dulu. Kemudian saat saat dijalankan program akan membuka `/courses/create`, Laravel akan masuk ke route detail dan kemungkinan menghasilkan 404 karena create bukan ID mata kuliah.

---

### 5. Ganti `{{ $nama }}` menjadi `{!! $nama !!}`, isi ``$nama dengan `<script>alert('XSS')</script>`
Prediksi awal saya dimana jika `$nama` berisi `<script>alert('XSS')</script>`, penggunaan `{!! !!}` akan menampilkan HTML tanpa escaping. 

Ketika program dijalankan hasilnya alert XSS benar-benar muncul, dengan `{{ }}`, Blade melakukan escaping sehingga script tidak dijalankan, namun {!! !!} menampilkan HTML mentah sehingga tag `<script>` diproses oleh browser dan menyebabkan XSS.

---

### 6. Hapus `@vite(...)` dari layout
Prediksi awal saya itu halaman akan kehilangan tampilan atau styling karena `@vite` digunakan untuk memuat CSS dan JavaScript dari aplikasi.

Ketika program dijalankan hasilnya halaman tetap dapat dibuka, tetapi CSS dan JavaScript dari Vite tidak lagi dimuat. Akibatnya tampilan menjadi tidak memiliki styling seperti sebelumnya dan fitur JavaScript tertentu tidak dapat berjalan.

---

### 7. Hentikan npm run dev lalu muat ulang halaman
Prediksi awal saya halaman Laravel masih dapat dibuka karena `php artisan serve` merupakan server aplikasi Laravel yang berbeda dari Vite. Namun, asset frontend yang disediakan oleh development server Vite tidak akan diperbarui atau tidak dapat dimuat seperti ketika `npm run dev` masih berjalan.

Ketika program dijalankan, Laravel masih dapat mengirimkan halaman, tetapi asset dari development server Vite tidak lagi dapat diambil. Akibatnya tampilan halaman dapat berubah atau asset tidak termuat.

---

### 8. Panggil route('courses.show') tanpa mengirim parameter
Prediksi awal saya akan terjadi error karena route `courses.show`.

Hasilnya Laravel memang tidak dapat membuat URL dan menghasilkan error `syntax error, unexpected token ";", expecting ")"`, karena route `courses.show` membutuhkan parameter ID.

---