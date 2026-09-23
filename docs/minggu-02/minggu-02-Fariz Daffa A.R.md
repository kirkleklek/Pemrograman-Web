### Nama : Fariz Daffa Abbiyu Rahmatullah

### NIM  : 10241029

----

### READ

### 1. Baris mana di routes/web.php yang menangkapnya?

Route /tentang ditangkap di baris 10 pada routes/web.php, sekarang juga sudah diberi nama route 'tentang' lewat ->name('tentang'):

```php
   Route::get('/tentang', function () {
       return view('tentang');
   })->name('tentang');
```
 
### 2. Kalau ditangani controller, berkas dan method mana?

Route ini belum ditangani oleh controller, masih menggunakan closure langsung di dalam web.php. Belum ada file atau method controller yang terlibat.

### 3. View mana yang dikembalikan? Di path apa persisnya?

View yang dikembalikan adalah 'tentang', yang arahnya ke file resources/views/tentang.blade.php.

### 4. Layout apa yang membungkusnya?

Layout yang membungkus tentang.blade.php adalah komponen Blade <x-layout title="Tentang">, merujuk ke resources/views/components/layout.blade.php. Ini beda dari sistem @extends('layouts.app') yang biasa dibahas di tutorial, karena kami pakai Blade Component (x-layout), bukan @extends/@section. Layout ini isinya struktur HTML dasar (head, navbar) yang sama buat semua halaman, dan konten khusus halaman tentang (judul, tabel anggota) dimasukkan ke slot layout itu.

### 5. Jalankan php artisan route:list --path=tentang. Cocok dengan analisis Anda?

Setelah dijalankan, php artisan route:list --path=tentang menunjukkan 1 route: GET|HEAD tentang, dengan NAME tentang, sumbernya dari routes/web.php:10. Hasil ini cocok dengan analisis di poin 1, baris, method, dan URI-nya sama persis. Kolom NAME juga sudah terisi 'tentang', sesuai dengan ->name('tentang') yang baru ditambahkan setelah pull update dari branch layout-navigation.

---

### BREAK

### 1. Ubah Route::get menjadi Route::post pada route daftar mata kuliah**

Prediksi: karena browser pasti mengakses halaman lewat method GET, kalau route /courses diubah jadi Route::post, permintaan GET dari browser tidak akan cocok dengan route manapun.

Hasil: sesuai prediksi, ini bakal memunculkan error 405 Method Not Allowed. Ini membuktikan Laravel benar-benar mencocokkan method HTTP, bukan cuma URL-nya saja.

### 2. Ubah nama view di return view(...) menjadi yang tidak ada

Prediksi: Laravel akan mencari file blade dengan nama yang salah tersebut di resources/views, tidak menemukannya, lalu melempar exception.

Hasil: sesuai prediksi, muncul InvalidArgumentException: View [nama] not found. Ini nunjukkin Laravel benar-benar mengecek keberadaan file view secara fisik, bukan cuma nebak dari nama fungsi.

### 3. Hapus ->name('courses.show'), lalu muat halaman yang memakai route('courses.show')

Prediksi: file courses/index.blade.php memanggil route('courses.show', $course['id']) untuk bikin link "Lihat Detail". Kalau nama route-nya dihapus dari routes/web.php, Laravel tidak tahu route mana yang dimaksud.

Hasil: sesuai prediksi, muncul RouteNotFoundException: Route [courses.show] not defined. Ini nunjukkin kenapa nama route itu wajib ada kalau dipakai di tempat lain lewat helper route(), bukan sekadar penamaan opsional.

### 4. Pindahkan /courses/{id} ke atas /courses/create, lalu buka /courses/create

Prediksi: urutan asli sudah benar, courses.create ada sebelum courses/{id}, jadi /courses/create bisa diakses normal. Kalau dibalik, Laravel akan mencocokkan /courses/create ke route courses.show duluan, dan kata "create" dianggap sebagai isi parameter {id}.

Hasil: di method show(), ada (int) $id yang mengubah "create" jadi 0 (PHP otomatis konversi string non-angka ke 0). Karena tidak ada course dengan id 0, $course jadi null, dan abort_if(!$course, 404) langsung trigger halaman 404. Ini membuktikan urutan route menentukan, route spesifik seperti /courses/create harus didaftarkan sebelum route umum berparameter seperti /courses/{id}.

### 5. Ganti {{ $nama }} menjadi {!! $nama !!}, isi $nama dengan XSS

Prediksi: proyek saya pakai {{ $course['nama'] }} di courses/show.blade.php, kalau diubah jadi {!! $course['nama'] !!} dan datanya diisi <script>alert('XSS')</script>, maka karena {!! !!} tidak melakukan escape terhadap HTML/JavaScript, script itu akan benar-benar dieksekusi oleh browser.

Hasil: sesuai prediksi, ini akan memunculkan popup alert sungguhan di layar, bukan cuma tulisan script sebagai teks. Ini pembuktian nyata kenapa {{ }} (yang otomatis escape) adalah default yang aman, sedangkan {!! !!} hanya boleh dipakai untuk data yang sudah pasti aman/terpercaya, bukan data mentah dari input.

### 6. Hapus @vite(...) dari layout

Prediksi: baris @vite([...]) di layout berfungsi memuat file CSS dan JS hasil build Vite ke halaman. Kalau dihapus, browser tidak lagi menerima link ke file-file itu.

Hasil: halaman tetap terbuka normal tanpa error PHP, karena ini cuma soal aset visual, bukan logic backend. Tapi semua styling Tailwind (warna, spacing, layout rapi) hilang, tampilan jadi HTML polos tanpa gaya. Ini membuktikan @vite cuma jembatan penghubung HTML ke aset CSS/JS, bukan bagian dari logic aplikasi, jadi menghapusnya tidak bikin error, cuma bikin tampilan jadi berantakan.

### 7. Hentikan npm run dev, lalu muat ulang halaman

Prediksi: saat npm run dev berjalan, Vite menyediakan dev server sendiri (biasanya di port 5173) yang menyajikan CSS/JS secara langsung tanpa perlu di-build dulu, plus fitur hot-reload otomatis tiap ada perubahan kode. Directive @vite di layout akan otomatis mendeteksi dev server ini dan mengarahkan halaman ke situ.

Hasil: kalau dihentikan, dev server mati dan belum ada file build permanen di public/build, jadi Laravel melempar error ViteManifestNotFoundException saat halaman dimuat ulang. Ini bukti bedanya mode dev dan mode build. Kalau mode dev itu cepat, auto-reload, tapi butuh server tetap nyala, sedangkan kalau mode build hasil file statisnya permanen, cocok untuk production.

### 8. Panggil route('courses.show') tanpa mengirim parameter

Prediksi: route courses.show didefinisikan dengan parameter wajib {id} di URL-nya (/courses/{id}), tanpa nilai default. Kalau helper route('courses.show') dipanggil tanpa argumen id sama sekali, Laravel tidak akan bisa membentuk URL yang lengkap.

Hasil: sesuai prediksi, ini akan memunculkan error Missing required parameter for [Route: courses.show] [URI: courses/{id}]. Ini nunjukkin kalau route dengan parameter wajib harus selalu diberi nilai saat dipanggil lewat helper route(), tidak bisa dikosongkan begitu saja.

---

### FIX LMS-Broken

Setelah dilihat isi dari routes/web.php, CourseController.php, dan view-view di folder courses, saya menemukan 6 masalah yang disebutkan di soal. Berikut penjelasan masing-masing beserta risikonya.


### 1. Route yang saling menutupi ada di routes/web.php

```php
Route::get('/courses/{id}/delete', ...)->name('courses.destroy.broken');
Route::get('/courses/{id}', ...)->name('courses.show');
Route::get('/courses', ...)->name('courses.index');
Route::get('/courses/create', ...)->name('courses.create');
```

Masalahnya ada di route /courses/{id} yang didefinisikan sebelum /courses/create. Karena Laravel mencocokkan route dari atas ke bawah, begitu saya akses /courses/create, Laravel akan menganggap ini cocok dengan /courses/{id} duluan, dan kata "create" dibaca sebagai isi dari $id, bukan diarahkan ke halaman tambah mata kuliah yang seharusnya. Risikonya, halaman tambah mata kuliah jadi tidak bisa diakses sama sekali karena keburu "ditangkap" oleh route lain di atasnya.

### 2. Method HTTP salah

```php
Route::get('/courses/{id}/delete', [CourseController::class, 'destroy'])
```

Aksi menghapus data mata kuliah di sini pakai method GET, padahal aksi yang mengubah/menghapus data seharusnya pakai method seperti POST atau DELETE. Ini juga kelihatan dari view index yang bikin tombol hapus jadi cuma `<a href="...">`, bukan form. Risikonya lumayan bahaya: link GET bisa ter-trigger tanpa sengaja, misalnya kalau ada bot/crawler yang mengikuti semua link di halaman, atau link ini di-share dan orang lain klik tanpa sadar itu aksi hapus, datanya bisa langsung terhapus tanpa konfirmasi yang aman.

### 3 dan 4. Dua URL hardcode

Di courses/index.blade.php, saya temukan dua tempat yang link-nya ditulis manual sebagai string:

```php
<a href="/courses/{{ $course['id'] }}" ...>
<a href="/courses/{{ $course['id'] }}/delete" ...>
```

Seharusnya pakai helper route() seperti route(courses.show, $course[id]), bukan ditulis manual begini. Risikonya, kalau suatu saat struktur URL di routes/web.php diubah (misalnya prefix-nya jadi /mata-kuliah/ bukan /courses/), semua link hardcode ini jadi rusak dan harus dicari manual satu-satu di semua view, padahal kalau pakai route() akan otomatis menyesuaikan.

### 5. XSS

Di courses/show.blade.php:

```php
{!! $course['description'] !!}
```

Data deskripsi ditampilkan pakai {!! !!} yang artinya tidak di-escape oleh Blade, padahal ini data yang nantinya bisa saja diisi user lewat form create. Risikonya, kalau ada user yang mengisi deskripsi dengan script seperti <script>alert(XSS)</script>, script itu akan benar-benar dieksekusi oleh browser siapa pun yang membuka halaman detail mata kuliah tersebut. Seharusnya pakai {{ }} biasa supaya karakter HTML/script otomatis di-escape dan cuma tampil sebagai teks biasa, bukan dieksekusi.

### 6. Logika query di view

Di courses/index.blade.php:

```php
@php
    $activeCourses = array_filter($courses, function($c) {
        return $c['status'] === 'active';
    });
@endphp
```

Proses filter data mata kuliah yang statusnya "active" ini ditulis langsung di dalam file view pakai blok @php, padahal logic seperti ini seharusnya ada di controller, bukan di view. Risikonya, view jadi bercampur antara tampilan dan logic, susah dites terpisah, dan kalau logic yang sama dibutuhkan di halaman lain jadi harus copy-paste kode yang sama lagi, bukan tinggal panggil ulang dari satu tempat.

---

