### Nama : Fariz Daffa Abbiyu Rahmatullah
### NIM  : 10241029
---

### READ

1.File public/index.php

File ini kayak pintu masuk untuk semua request ke aplikasi Laravel. Pertama, dia mengecek apakah aplikasi sedang dalam mode maintenance, terus memuat autoloader Composer agar semua class bisa dipakai. Terakhir dia memuat bootstrap/app.php buat membangun objek $app, menyerahkan request yang masuk (Request::capture()) ke $app->handleRequest() untuk diproses dan menghasilkan response.

2.File bootstrap/app.php

- Route 

    bagiannya ada di withRouting(...). Dia mendaftarkan berkas-berkas route: web.php untuk route web, console.php untuk perintah artisan, dan health: '/up' untuk endpoint cek kesehatan aplikasi.


- Middleware 

    bagiannya ada di withMiddleware(function (Middleware $middleware) { ... }). Closure ini defaultnya kosong, tapi di sini kita mendaftarkan atau mengatur middleware global/grup.


- Exception 

    bagiannya ada di withExceptions(function (Exceptions $exceptions) { ... }). Closure ini juga kosong secara default, dan di sinilah kita mengatur cara aplikasi menangani/melaporkan exception (misalnya custom error handling).

3.File routes/web.php
Route yang menghasilkan halaman selamat datang ada di :

```php
Route::get('/', function () {
    return view('welcome');
});
``` 

Sudah saya ganti di bagian "return view('welcome');" menjadi return ('halo guys'); dan di Chrome tampilannya juga berubah menjadi hanya tulisan "halo guys"

4.Menjalankan php artisan route:list

Output php artisan route:list menampilkan 6 route, dan cocok dengan isi routes/web.php:

- GET|HEAD / berasal dari routes/web.php:5, yaitu bagian Route::get('/', function () { return view('welcome'); });

- GET|HEAD tentang berasal dari routes/web.php:9, yaitu bagian Route::get('/tentang', function () { return view('tentang'); });

Dua route ini sama dengan yang ada di web.php. Penulisan /tentang di kode muncul sebagai tentang (gaada garis miring) di hasil route:list, karena Laravel otomatis membuang / di depan kalau lagi nampilin daftar.

Sisa route lainnya (up, _boost/browser-logs, storage/{path}) bukan dari web.php, tapi didaftarkan otomatis dari framework dan package yang di install.

---

### BREAK

1.Kalau saya ganti nama .env menjadi .env.bak, halaman langsung error total, gabisa dimuat sama sekali. Laravel ini ternyata butuh file .env untuk tahu APP_KEY, konfigurasi database, dan pengaturan dasar lainnya. Kalo gaada file itu, program gapunya acuan sama sekali buat jalan, jadi langsung gagal dari awal, bukan cuma satu fitur yang error.

2.Kalau APP_KEY saya kosongin, muncul error terkait enkripsi kayak "no application encryption key" atau MAC invalid. APP_KEY dipakai Laravel buat enkripsi session dan cookie yang jalan otomatis di setiap request, jadi pas kosong, hampir semua proses gagal juga.

3.Kalau DB_DATABASE saya ubah ke nama yang tidak ada, muncul QueryException karena Laravel tidak menemukan database yang dimaksud di konfigurasi. Karena APP_DEBUG masih true, errornya ditampilkan lengkap, ada nama exception, path file, bahkan query SQL yang sedang dijalankan waktu error terjadi.

4.Kalau saya ubah APP_DEBUG=false dan ulangi error nomor 3, tampilannya berubah total, cuma muncul halaman kosong bertuliskan "Server Error" tanpa detail sama sekali. Waktu debug true, semua informasi teknis termasuk hal sensitif ikut terlihat, sedangkan waktu false, semuanya disembunyikan dan user cuma lihat pesan generik. Ini kenapa APP_DEBUG wajib false di server produksi, kalau lupa diset true, siapa aja yang memicu error bisa melihat kredensial database dan struktur kode yang harusnya rahasia.

---

### CHECKPOINT

1.Urutan request sampai jadi HTML

Browser - public/index.php (sebagai pintu masuk) - load autoloader Composer - load bootstrap/app.php buat bangun aplikasi - dicocokkan ke routes/web.php - jalankan kode di route itu → karena disini pakai view(), Blade render jadi HTML - HTML dikirim balik ke browser.

2.Kenapa cuma public/ yang boleh diakses

Karena cuma folder itu yang isinya aman untuk publik (asset dan entry point). Kalau seluruh folder di projek kita diekspos, orang bisa langsung baca .env dan source code, data sensitif bisa bocor.

3.Beda .env dan .env.example

- .env.example = template kosong, aman jadi bisa di commit. 

- .env = konfigurasi asli yang isinya data rahasia (password, API key) yang beda tiap komputer, makanya gabisa di commit, biar ga bocor ke publik.

4.Middleware di Laravel 12 didaftarkan di mana

Di bootstrap/app.php, bagian ->withMiddleware(...). Beda dari tutorial lama yang sebut app/Http/Kernel.php, karena di Laravel baru, struktur projek disederhanakan dan Kernel.php dihapus.

5.Risiko APP_DEBUG=true di produksi

Kalau error, halaman debug bakal tampil ke publik lengkap dengan source code, query SQL, bahkan isi .env nya, jadi celah keamanannya besar.

---