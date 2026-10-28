# Materi, navbar, dan notifikasi — BUILD Milestone M3

Penyesuaian mengikuti modul Minggu 9–12, khususnya BUILD Milestone M3, dengan cakupan bagian materi, navbar, dan notifikasi. Form penilaian dan rekap nilai merupakan bagian terpisah. Integrasi pemberian nilai memakai alur API yang sudah ada.

## Pemetaan kebutuhan

| Ketentuan modul | Implementasi pada bagian ini |
| --- | --- |
| Minggu 9: unggah/akses materi | PDF/PPTX/DOCX maksimal 20 MB atau tautan; nama fisik acak di disk `local`; download melalui `MaterialController` dan `MaterialPolicy`; pembersihan berkas melalui `DeletesStoredFiles`, termasuk cascade course. |
| M3.1: Event + Listener + Notification | `AssignmentPublished` → `SendAssignmentNotification` → `NewAssignmentNotification`; `GradeGiven` → `SendGradeNotification` → `GradeGivenNotification`. |
| M3.2: queue database | `.env.example` dan konfigurasi lokal memakai `QUEUE_CONNECTION=database`. Cara menjalankan worker ada di README. |
| M3.3: kegagalan job | Migrasi bawaan sudah menyediakan `jobs` dan `failed_jobs`. Kedua listener memakai `ShouldQueue`, `tries = 3`, `backoff = 10`, dan `failed()` untuk log ID serta kelas exception. |
| M3.4: lonceng | `NotificationBell`: jumlah belum dibaca, badge maksimal `99+`, polling Livewire 30 detik. |
| M3.5: halaman notifikasi | `/mahasiswa/notifications`: 15 item per halaman, terbaru dahulu, tandai satu/semua dibaca, pembatasan pemilik di server. |
| M3.6: integrasi hasil penilaian | Nilai yang disimpan melalui API memicu event. Notifikasi membuka halaman submission yang sudah menampilkan nilai dan feedback dengan policy. Form input nilai web bukan cakupan bagian ini. |
| M3.7: rekap nilai | Di luar cakupan bagian ini; perlu diintegrasikan oleh bagian penilaian/rekap. |
| M3.8: payload aman | Payload tugas hanya `assignment_id`, `title`; payload nilai hanya `submission_id`, `title`. Nilai dan feedback tidak disalin ke notifikasi. Event dalam queue hanya membawa ID. |

## Pemicu dan penerima

- Tugas dibuat sebagai `published`, atau berubah dari `draft` ke `published`: notifikasi dikirim kepada mahasiswa aktif yang masih terdaftar saat worker memprosesnya. Simpan draft atau edit judul tanpa perubahan status tidak mengirim ulang. Publikasi ulang setelah kembali ke draft menghasilkan pemberitahuan baru.
- Nilai pertama disimpan atau score/feedback berubah: notifikasi dikirim hanya kepada pemilik submission, selama masih terdaftar dan tugas masih published. Perubahan timestamp saja tidak mengirim ulang.
- Event dipicu oleh model `Assignment` dan `Grade`, sehingga jalur web dan API memakai perilaku yang sama. Operasi SQL langsung atau mass update melalui query builder tidak memicu event model.
- Event menunggu commit transaksi. Rollback tidak meninggalkan job. Penghapusan tugas atau pengembalian ke draft sebelum worker berjalan menyebabkan job tugas dilewati.
- Materi tidak lagi memicu notifikasi tambahan. Ini menggantikan implementasi awal yang mengirim notifikasi materi secara sinkron; pemicu wajib M3 adalah publikasi tugas dan pemberian nilai.

## Mengapa asinkron

Request menyimpan tugas/nilai lalu memasukkan satu job listener ke tabel `jobs`. Worker menjalankan listener dan menyimpan notifikasi database. Notification tidak mengimplementasikan `ShouldQueue` lagi karena pengirimannya sudah berlangsung di dalam listener yang diantrekan: satu event menghasilkan satu job listener, bukan job tambahan per penerima.

Penerima tugas diambil per 100 mahasiswa, tanpa query relasi per penerima. Insert notifikasi tetap satu per penerima. Seluruh insert untuk satu pengiriman tugas berada dalam transaksi agar kegagalan di tengah proses tidak meninggalkan sebagian penerima sebelum retry. Queue menyediakan pemrosesan *at least once*: penghentian proses tepat setelah commit tetapi sebelum job dihapus masih dapat menyebabkan pengiriman ulang.

Kegagalan pengiriman oleh worker tidak membatalkan tugas atau nilai yang sudah tersimpan. Setelah tiga percobaan gagal, job tercatat di `failed_jobs` dan `failed()` menulis log. Infrastruktur Laravel juga menyimpan exception kegagalan; jangan gunakan nilai/feedback sebagai pesan exception.

## Akses mahasiswa

- Klik lonceng untuk membuka daftar. Badge dan daftar polling setiap 30 detik pada tab aktif; Livewire mengurangi polling di tab latar belakang.
- **Tandai sudah dibaca** mengubah satu notifikasi. **Tandai semua sudah dibaca** mencakup seluruh notifikasi belum dibaca milik akun, termasuk halaman lain.
- Event `notifications-read` memperbarui lonceng setelah tindakan baca. Membuka daftar atau tautan tidak otomatis menandai dibaca.
- Komponen memeriksa autentikasi dan role setiap render/tindakan. Semua query melalui relasi akun login, termasuk ID dan tipe pemilik. UUID orang lain ditolak sebagai tidak ditemukan. API yang sudah ada menolak tindakan baca milik orang lain dengan 403.
- Isi ditampilkan dengan escaping Blade. URL tujuan dibangun dari route aplikasi, bukan URL bebas dari payload. Halaman tugas/submission tetap memeriksa policy; notifikasi bukan pemberian izin akses.

## Demo M3 dan pemeriksaan worker

1. Jalankan migrasi yang sudah ada: `php artisan migrate`. Jangan membuat migrasi kedua untuk `jobs`, `failed_jobs`, atau `notifications`.
2. Pastikan `QUEUE_CONNECTION=database`; jalankan `php artisan config:clear` jika baru mengubah konfigurasi. Setelah menambah listener, bersihkan cache event lama dengan `php artisan event:clear`.
3. Periksa `php artisan event:list`: kedua event harus memiliki satu listener masing-masing.
4. Hentikan worker sementara. Sebagai dosen, publikasikan tugas pada mata kuliah yang memiliki mahasiswa terdaftar. Tugas tersimpan, job ada di `jobs`, tetapi notifikasi belum bertambah.
5. Jalankan `php artisan queue:work database --sleep=3 --tries=3 --backoff=10`. Job diproses; mahasiswa menerima notifikasi. Tunggu polling atau buka daftar untuk melihatnya.
6. Beri nilai melalui alur API `PUT /api/v1/submissions/{submission}/grade` yang sudah ada. Pemilik submission menerima notifikasi dan dapat membuka **Lihat nilai dan feedback**.
7. Coba UUID notifikasi mahasiswa lain melalui API/komponen. Tindakan baca harus ditolak dan status pemilik asli tidak berubah.
8. Periksa `php artisan queue:failed` dan `storage/logs/laravel.log` saat ada kegagalan. Setelah penyebab diperbaiki, jalankan `php artisan queue:retry <uuid>` untuk job yang dipilih. Restart worker setelah perubahan kode dengan `php artisan queue:restart`.

## Pengujian

Pengujian queue memakai database terisolasi dan worker Laravel yang dipanggil oleh test, bukan hanya `Queue::fake`. Mencakup job tertunda, penerima, payload, rollback, tugas draft/hilang, tiga percobaan, backoff, `failed_jobs`, dan log kegagalan.

```powershell
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
php vendor/phpunit/phpunit/phpunit tests/Feature/MilestoneNotificationTest.php tests/Feature/NotificationWebTest.php tests/Feature/NotificationApiTest.php tests/Feature/MaterialWebTest.php
npm.cmd run build
```

Variabel pengujian berlaku pada sesi terminal tersebut; gunakan terminal terpisah untuk worker aplikasi agar koneksi database tidak ikut berubah.

Hasil verifikasi 23 Oktober 2026: seluruh suite PHPUnit pada SQLite di memori lulus **306 tes, 4.052 assertion**. Build Vite, pemeriksaan format PHP pada berkas implementasi, dan `git diff --check` berhasil. `event:list` menampilkan masing-masing satu listener antrean untuk `AssignmentPublished` dan `GradeGiven`. Pengujian visual browser belum terverifikasi karena koneksi tool browser tidak tersedia pada sesi sebelumnya.
