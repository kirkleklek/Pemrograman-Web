### Nama : Fariz Daffa Abbiyu Rahmatullah

### NIM  : 10241029

----

### READ

php artisan route:list --except-vendor sekarang menghasilkan 39 route, jauh lebih banyak dari sebelumnya karena sudah ada controller untuk Assignment, Material, dan Submission, dan strukturnya sudah dipisah per peran lewat prefix URL (admin/, dosen/, mahasiswa/).

### Daftar Titik Rawan IDOR

| Route | Method | Siapa boleh akses | Apa yang mencegah orang lain sekarang |
|---|---|---|---|
| admin/courses/{course} (show) | GET | Admin | Prefix admin/ di URL, belum tentu ada pengecekan role beneran di middleware |
| admin/courses/{course} (update) | PUT/PATCH | Admin | Sama seperti di atas |
| admin/courses/{course} (destroy) | DELETE | Admin | Sama seperti di atas |
| admin/users/{user} (show) | GET | Admin | Sama seperti di atas |
| admin/users/{user} (update) | PUT/PATCH | Admin | Sama seperti di atas |
| admin/users/{user} (destroy) | DELETE | Admin | Sama seperti di atas |
| dosen/assignments/{assignment} (show) | GET | Dosen pengampu mata kuliah itu | Belum ada pengecekan kepemilikan, dosen lain bisa buka assignment yang bukan miliknya asal tau ID-nya |
| dosen/assignments/{assignment} (update) | PUT/PATCH | Dosen pengampu mata kuliah itu | Belum ada pengecekan kepemilikan |
| dosen/assignments/{assignment} (destroy) | DELETE | Dosen pengampu mata kuliah itu | Belum ada pengecekan kepemilikan |
| dosen/assignments/{assignment}/submissions (index) | GET | Dosen pengampu mata kuliah itu | Belum ada pengecekan kepemilikan, dosen lain bisa lihat daftar submission tugas yang bukan miliknya |
| dosen/courses/{course}/assignments (index, store, create) | GET/POST | Dosen pengampu course itu | Belum ada pengecekan kepemilikan terhadap course |
| dosen/courses/{course}/materials (index, store, create) | GET/POST | Dosen pengampu course itu | Belum ada pengecekan kepemilikan terhadap course |
| dosen/materials/{material} (show, update, destroy, edit) | GET/PUT/DELETE | Dosen pengampu mata kuliah itu | Belum ada pengecekan kepemilikan |
| dosen/submissions/{submission} (show) | GET | Dosen yang ngajar mata kuliah terkait tugas itu | Belum ada pengecekan kepemilikan, dosen lain bisa lihat submission mahasiswa dari mata kuliah lain |
| mahasiswa/assignments/{assignment} (show) | GET | Mahasiswa yang terdaftar di course itu | Belum ada pengecekan apakah mahasiswa beneran terdaftar di course tersebut |
| mahasiswa/assignments/{assignment}/submissions (store) | POST | Mahasiswa yang terdaftar di course itu | Belum ada pengecekan pendaftaran |
| mahasiswa/courses/{course}/assignments (index) | GET | Mahasiswa yang terdaftar di course itu | Belum ada pengecekan pendaftaran |
| mahasiswa/courses/{course}/materials (index) | GET | Mahasiswa yang terdaftar di course itu | Belum ada pengecekan pendaftaran |
| mahasiswa/materials/{material} (show) | GET | Mahasiswa yang terdaftar di course terkait materi itu | Belum ada pengecekan pendaftaran |
| mahasiswa/submissions/{submission} (show) | GET | Mahasiswa pemilik submission itu sendiri | Belum ada pengecekan kepemilikan, ini yang paling rawan, mahasiswa lain bisa lihat submission orang lain cuma dengan ganti angka ID di URL |

Hampir semua route di atas masih rawan IDOR karena baru dipisah lewat prefix URL (admin/, dosen/, mahasiswa/) dan kemungkinan middleware role, belum ada pengecekan kepemilikan per objek (misalnya apakah assignment ini beneran punya dosen yang sedang login, atau submission ini beneran punya mahasiswa yang sedang akses). Projek kami juga belum punya sistem login beneran, jadi peran saat ini ditentukan lewat prefix URL, bukan session user yang login. Pengecekan kepemilikan objek secara menyeluruh baru akan dirapikan pakai Policy di minggu 7.

---

### BREAK Minggu 5

### 1. Login sebagai mahasiswa A, buka submission milik mahasiswa B dengan mengubah angka di URL

Prediksi: kalau controller cuma pakai route model binding, submission mahasiswa B bisa kebuka cuma dengan ganti angka ID di URL.

Hasil: belum bisa diperagakan lewat browser karena aplikasi kami belum punya halaman login (baru dikerjakan minggu 7). Dari kode SubmissionController@show, IDOR-nya tidak terjadi karena ada abort_unless($submission->user_id === $user->id ..., 403), jadi mahasiswa A akan kena 403. Tanpa baris itu, route mahasiswa/submissions/{submission} cuma mengecek datanya ada, sehingga submission mahasiswa B akan kebuka.

### 2. Buka /courses/1/assignments/99 di mana tugas 99 milik mata kuliah lain

Prediksi: tanpa scoping, Laravel cuma mengecek course 1 ada dan assignment 99 ada, tapi tidak mengecek hubungan keduanya, jadi URL itu lolos.

Hasil: di aplikasi kami URL itu tidak ada (404) karena route assignment memakai shallow(), jadi satu tugas dibuka lewat dosen/assignments/{assignment} tanpa course di URL. Secara konsep, kalau route nested lengkap dipakai tanpa scopeBindings(), assignment 99 tetap akan kebuka walau bukan milik course 1.

### 3. Aktifkan Route::scopeBindings(), ulangi nomor 2

Prediksi: dengan scopeBindings(), Laravel memastikan assignment adalah anak dari course yang ada di URL. Kalau bukan, hasilnya 404.

Hasil: scopeBindings() di routes/web.php kami sudah aktif di group dosen dan mahasiswa. Bedanya dengan nomor 2, tanpa scoping assignment 99 lolos, dengan scoping akan ditolak 404 karena tidak ditemukan di dalam course 1. Di route kami efeknya terbatas karena shallow() sudah menghilangkan course dari URL tugas, jadi scopeBindings() berperan sebagai lapisan jaga-jaga.

### 4. Daftarkan middleware di app/Http/Kernel.php seperti tutorial lama

Prediksi: file itu tidak ada di Laravel 12.

Hasil: app/Http/Kernel.php memang tidak ada. Gejalanya, kalau kita ikuti tutorial lama dan membuat file itu manual, middleware tidak terdaftar, dan saat route memakai role:dosen biasanya muncul error Target class [role] does not exist. Di Laravel 12 alias middleware didaftarkan di bootstrap/app.php lewat $middleware->alias([...]).

### 5. Pasang role:admin pada grup, lalu akses sebagai dosen

Prediksi: EnsureUserHasRole mengecek role user dengan in_array, dosen tidak ada di daftar admin jadi ditolak.

Hasil: dosen akan kena 403 dari middleware, dan yang tampil adalah halaman 403 kustom (resources/views/errors/403.blade.php) yang kami buat. Request tidak pernah sampai ke controller.

### 6. Sebagai dosen A, edit mata kuliah milik dosen B (keduanya lolos role:dosen)

Prediksi: role:dosen cuma mengecek apakah user itu dosen, bukan pemilik datanya, jadi dosen A tetap lolos middleware.

Hasil: middleware saja tidak cukup. Yang menutup celahnya adalah pengecekan kepemilikan di controller, contohnya di SubmissionController@index ada abort_unless(... $course->lecturer_id === auth()->id(), 403). Tanpa pengecekan itu, dosen A bisa mengakses data dosen B. Di aplikasi kami edit mata kuliah hanya ada di area admin, jadi contoh dosennya memakai assignment dan submission. Pengecekan kepemilikan ini nanti dirapikan pakai Policy di minggu 7.