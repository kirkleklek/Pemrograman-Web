# Week 1

**Mata Kuliah: Pemrograman Web**\
**Pertemuan: 1**\
**Tanggal: 2 September 2026**\
**Topik: Struktur File Laravel**\

---

## Ringkasan

Ternyata saat kita mengetik suatu alamat, terjadi banyak sekali proses yang harus dilewati dengan waktu yang sangat amat cepat. Misalnya kita ingin mengakses course pada kampuslms yang terjadi awal awal browser yang kita pakai akan mengirimkan HTTP Request, lalu dilanjutkan program akan menyiapkan aplikasinya lewat index.php.\

Namun, sebelum kita ke aplikasinya kita harus melewati pengecekan middleware ibaratnya tahap ini adalah pemeriksaan sebelum kita ke tujuan, ada banyak sekali yang dicek seperti apakah user udh login, apakah request aman, apakah user punya hak akses dan lain lain. \

Setelah di cek, user bakal di arahin ke rute yang sesuai dengan apa yang di request, user akan diarahin lewat file web.php dan lewat file ini halaman web akan ditampilkan, controller akan di jalankan, dan fungsi tertentu akan dijalankan. Nah kalau ada controller yang di route, laravel akan jalanin tuh controller yang sesuai dengan request.\

Nah kalau misal nih controller butuh data dari database yang akan ditampilkan oleh halaman, controller bakal memakai model untuk komunikasi dengan database. Namun nggak selalu pakai model, bisa langsung view kalau emang halaman statis seperti halamaan tentang kami.\

Nah setelah data di ambil dari database, data tersebut akan ditampilkan lewat view. Nah karna laravel tidak bisa memproses HTML, maka dari itu kita menggunakan blade agar laravel membaca blade dan dikirimkan ke browser dalam format html.\

Setelah data ditampilkan lewat view, laravel akan mengirimkan HTTP Response dan browser akan menampilkan htmlnya 

---

## Read

1. File ini diibartkan gerbang masuk untuk user mengkases website. Nah saat user mengkases website, nanti website akan mengirimkan HTTP request lewat public/index.php ini sebelum melewati middleware dan proses lainnya.

2. 
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php', // ini mengatur route ke web.php
        commands: __DIR__.'/../routes/console.php', //ini yang mengatur ke konsol.php
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void { // ini yang mengarahkan ke middleware (pengecekan request)
    })
    ->withExceptions(function (Exceptions $exceptions): void {// ini yang mengatur pengecualian

    })->create();
```

3. sudah, berubah. Yang saya edit adalah file welocme.blade.php yang ada di resource\view.

4. Kurang lebih sama, pas saya coba route list ada menampilkan bahwa web.php membuat/membuka route baru "GET|HEAD"

---

## Break

1. Saat saya ganti .env menjadi .env.bak program laravel tidak dapat menemukan environtment yang dibutuhkan untuk mengetahui seperti database, API, dll nya. Hal ini sama persis dengan yang saya perkirakan di awal, yaitu program tidak akan berjalan dikarenakan terjadinya missing environtment

2. Saat APP_KEY dihapus, kita tidak dapat mengkases websitenya dikarenakan enkripsi key untuk beberapa fungsi sepeerti session dan cookies akan tidak berjalan dengan normal. Namun awalnya saya kira app key ini hanya untuk mengunci dari data saja, ternyata tidak

3. Saat db_database tidak diisi dengan benar ataupun kosong, program akan bingung mencari sumber database nya dan akan mengalami error. Program akan bingung dimana akan menaruh data data penting. Yang muncul adalah SQLSTATE[3D000]: Invalid catalog name: 1046 No database selected (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: , SQL: select * from `sessions` where `id` = RBfWIYHlFgagrQVvFigQb9lXoAWd7sdznd0zdJRZ limit 1)'

4. Jika App_debug dimatikan kita tidak diperlihatkan apa permasalahan yang terjadi pada program kita, apakah itu salah syntax ataupun database. Sehingga hal ini bisa membuat proses debugging memakan waktu lebih lama dibandingkan app_debug berstatus true. Output yang saya dapatkan adalah 500 Server Error.