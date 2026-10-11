# Week 5

**Mata Kuliah: Pemrograman Web**  
**Pertemuan: 5**  
**Topik: Routing Lanjutan, Route Model Binding, IDOR, dan Middleware**

---

Catatan: Catatan ini merangkum laporan Eagan. Daftar route diperbarui dari proyek saat ini. BREAK menjelaskan simulasi kondisi awal lab, belum merupakan hasil percobaan langsung di browser.

## READ

### **1. Jalankan `php artisan route:list --except-vendor`**

Perintah berikut menampilkan 61 route pada proyek saat ini:

```bash
php artisan route:list --except-vendor
```

```text
GET|HEAD        / ......................................................................... root › routes/web.php:14
  GET|HEAD        admin/courses ......................................... admin.courses.index › CourseController@index
  POST            admin/courses ......................................... admin.courses.store › CourseController@store
  GET|HEAD        admin/courses/create ................................ admin.courses.create › CourseController@create
  GET|HEAD        admin/courses/{course} .................................. admin.courses.show › CourseController@show
  PUT|PATCH       admin/courses/{course} .............................. admin.courses.update › CourseController@update
  DELETE          admin/courses/{course} ............................ admin.courses.destroy › CourseController@destroy
  GET|HEAD        admin/courses/{course}/edit ............................. admin.courses.edit › CourseController@edit
  GET|HEAD        admin/users ............................................... admin.users.index › UserController@index
  POST            admin/users ............................................... admin.users.store › UserController@store
  GET|HEAD        admin/users/create ...................................... admin.users.create › UserController@create
  GET|HEAD        admin/users/{user} .......................................... admin.users.show › UserController@show
  PUT|PATCH       admin/users/{user} ...................................... admin.users.update › UserController@update
  DELETE          admin/users/{user} .................................... admin.users.destroy › UserController@destroy
  GET|HEAD        admin/users/{user}/edit ..................................... admin.users.edit › UserController@edit
  POST            api/v1/assignments .................................................. Api\AssignmentController@store
  PUT|PATCH       api/v1/assignments/{assignment} .................................... Api\AssignmentController@update
  DELETE          api/v1/assignments/{assignment} ................................... Api\AssignmentController@destroy
  GET|HEAD        api/v1/assignments/{assignment}/submissions ......................... Api\SubmissionController@index
  POST            api/v1/assignments/{assignment}/submissions ......................... Api\SubmissionController@store
  POST            api/v1/auth/login ......................................................... Api\AuthController@login
  POST            api/v1/auth/logout ....................................................... Api\AuthController@logout
  GET|HEAD        api/v1/courses .......................................................... Api\CourseController@index
  GET|HEAD        api/v1/courses/{course} .................................................. Api\CourseController@show
  GET|HEAD        api/v1/courses/{course}/assignments ................................. Api\AssignmentController@index
  GET|HEAD        api/v1/courses/{course}/materials ..................................... Api\MaterialController@index
  GET|HEAD        api/v1/me .................................................................... Api\AuthController@me
  GET|HEAD        api/v1/notifications .............................................. Api\NotificationController@index
  POST            api/v1/notifications/{notification}/read ........................... Api\NotificationController@read
  PUT             api/v1/submissions/{submission}/grade ................................... Api\GradeController@update
  GET|HEAD        dashboard ............................................................ dashboard › routes/web.php:65
  GET|HEAD        dosen/assignments/{assignment} .................. dosen.assignments.show › AssignmentController@show
  PUT|PATCH       dosen/assignments/{assignment} .............. dosen.assignments.update › AssignmentController@update
  DELETE          dosen/assignments/{assignment} ............ dosen.assignments.destroy › AssignmentController@destroy
  GET|HEAD        dosen/assignments/{assignment}/edit ............. dosen.assignments.edit › AssignmentController@edit
  GET|HEAD        dosen/assignments/{assignment}/submissions dosen.assignments.submissions.index › SubmissionControll…
  GET|HEAD        dosen/courses/{course}/assignments .... dosen.courses.assignments.index › AssignmentController@index
  POST            dosen/courses/{course}/assignments .... dosen.courses.assignments.store › AssignmentController@store
  GET|HEAD        dosen/courses/{course}/assignments/create dosen.courses.assignments.create › AssignmentController@c…
  GET|HEAD        dosen/courses/{course}/materials .......... dosen.courses.materials.index › MaterialController@index
  POST            dosen/courses/{course}/materials .......... dosen.courses.materials.store › MaterialController@store
  GET|HEAD        dosen/courses/{course}/materials/create . dosen.courses.materials.create › MaterialController@create
  GET|HEAD        dosen/materials/{material} .......................... dosen.materials.show › MaterialController@show
  PUT|PATCH       dosen/materials/{material} ...................... dosen.materials.update › MaterialController@update
  DELETE          dosen/materials/{material} .................... dosen.materials.destroy › MaterialController@destroy
  GET|HEAD        dosen/materials/{material}/edit ..................... dosen.materials.edit › MaterialController@edit
  GET|HEAD        dosen/submissions/{submission} .................. dosen.submissions.show › SubmissionController@show
  GET|HEAD        forgot-password ......................... password.request › Auth\PasswordResetLinkController@create
  POST            forgot-password ............................ password.email › Auth\PasswordResetLinkController@store
  GET|HEAD        login ........................................... login › Auth\AuthenticatedSessionController@create
  POST            login ...................................... login.store › Auth\AuthenticatedSessionController@store
  POST            logout ........................................ logout › Auth\AuthenticatedSessionController@destroy
  GET|HEAD        mahasiswa/assignments/{assignment} .......... mahasiswa.assignments.show › AssignmentController@show
  POST            mahasiswa/assignments/{assignment}/submissions mahasiswa.assignments.submissions.store › Submission…
  GET|HEAD        mahasiswa/courses/{course}/assignments mahasiswa.courses.assignments.index › AssignmentController@i…
  GET|HEAD        mahasiswa/courses/{course}/materials .. mahasiswa.courses.materials.index › MaterialController@index
  GET|HEAD        mahasiswa/materials/{material} .................. mahasiswa.materials.show › MaterialController@show
  GET|HEAD        mahasiswa/submissions/{submission} .......... mahasiswa.submissions.show › SubmissionController@show
  POST            reset-password ................................... password.store › Auth\NewPasswordController@store
  GET|HEAD        reset-password/{token} .......................... password.reset › Auth\NewPasswordController@create
  GET|HEAD        tentang ................................................................ tentang › routes/web.php:18

                                                                                                   Showing [61] routes
```

`Route::resource()` membuat tujuh aksi: `index`, `create`, `store`, `show`, `edit`, `update`, dan `destroy`. `only()` membatasi aksinya, sedangkan `shallow()` membuat route detail cukup memakai ID anak.

### **2. Tandai route yang menerima parameter model**

Route bertanda model adalah semua baris keluaran di atas yang memuat **`{course}`**, **`{assignment}`**, **`{submission}`**, **`{user}`**, **`{material}`**, atau **`{notification}`**. Parameter `{token}` pada reset password bukan parameter model.

Jika nama parameter dan tipe model sesuai, Laravel otomatis mencari data melalui Route Model Binding. Contohnya, `show(Submission $submission)` menerima objek submission dari ID di URL. Data yang tidak ditemukan menghasilkan `404`.

### **3. Siapa yang boleh mengakses dan apa pengamannya?**

Admin mengelola pengguna dan mata kuliah. Dosen mengelola tugas serta materi mata kuliah yang diampunya dan membaca submission terkait. Mahasiswa membaca materi dan tugas sesuai keikutsertaannya, serta membuka submission sendiri.

Pada skenario awal Eagan, belum ada pemeriksaan kepemilikan. Binding hanya menemukan data, bukan memeriksa izin. Pada kode sekarang, middleware memeriksa login dan peran, controller memeriksa pengampu atau peserta, dan materi web memakai `MaterialPolicy`. Rinciannya dikelompokkan dalam tabel berikut.

### **4. Daftar Titik Rawan IDOR**

| Route berparameter                                                                                       | Method                                    | Siapa yang berhak                             | Pengaman saat ini                                                 | Risiko tanpa pemeriksaan akses                      |
| -------------------------------------------------------------------------------------------------------- | ----------------------------------------- | --------------------------------------------- | ----------------------------------------------------------------- | --------------------------------------------------- |
| `/admin/courses/{course}`, termasuk `/edit`                                                              | GET, PUT/PATCH, DELETE                    | Admin                                         | `auth` dan `role:admin`                                           | Mata kuliah dibaca, diubah, atau dihapus pihak lain |
| `/admin/users/{user}`, termasuk `/edit`                                                                  | GET, PUT/PATCH, DELETE                    | Admin                                         | `auth` dan `role:admin`                                           | Data pengguna terbaca atau diubah                   |
| `/dosen/courses/{course}/assignments`, termasuk `/create`                                                | GET, POST                                 | Dosen pengampu                                | Login, peran, dan pemeriksaan `lecturer_id`                       | Dosen mengakses atau membuat tugas kelas lain       |
| `/dosen/assignments/{assignment}`, termasuk `/edit`                                                      | GET, PUT/PATCH, DELETE                    | Dosen pengampu                                | Login, peran, dan pemeriksaan pengampu                            | Tugas dosen lain dibaca, diubah, atau dihapus       |
| `/dosen/courses/{course}/materials`, termasuk `/create`; `/dosen/materials/{material}`, termasuk `/edit` | GET, POST, PUT/PATCH, DELETE sesuai route | Dosen pengampu                                | Login, peran, dan `MaterialPolicy`                                | Materi kelas lain dibaca atau diubah                |
| `/dosen/assignments/{assignment}/submissions`; `/dosen/submissions/{submission}`                         | GET                                       | Dosen pengampu                                | Login, peran, dan pemeriksaan pengampu                            | Jawaban serta nilai kelas lain terbaca              |
| `/mahasiswa/courses/{course}/assignments`; `/mahasiswa/assignments/{assignment}`                         | GET                                       | Mahasiswa peserta, untuk tugas terbit         | Login, peran, keikutsertaan, dan status tugas                     | Tugas kelas lain atau tugas draft terbaca           |
| `/mahasiswa/courses/{course}/materials`; `/mahasiswa/materials/{material}`                               | GET                                       | Mahasiswa peserta                             | Login, peran, dan `MaterialPolicy`                                | Materi kelas lain terbaca                           |
| `/mahasiswa/assignments/{assignment}/submissions`                                                        | POST                                      | Mahasiswa peserta                             | Login, peran, keikutsertaan, dan status tugas                     | Pengumpulan tugas pada kelas yang tidak diikuti     |
| `/mahasiswa/submissions/{submission}`                                                                    | GET                                       | Mahasiswa pemilik yang mengikuti kelas        | Login, peran, pemilik, keikutsertaan, dan middleware status tugas | Jawaban dan nilai mahasiswa lain terbaca            |
| `/api/v1/courses/{course}`, termasuk `/materials` dan `/assignments`                                     | GET                                       | Admin, dosen pengampu, atau mahasiswa peserta | `auth:sanctum` dan pemeriksaan akses di controller                | Data kelas lain terbaca                             |
| `/api/v1/assignments/{assignment}`                                                                       | PUT/PATCH, DELETE                         | Dosen pengampu                                | `auth:sanctum`, peran, dan pemeriksaan pengampu                   | Tugas dosen lain diubah atau dihapus                |
| `/api/v1/assignments/{assignment}/submissions`                                                           | GET, POST                                 | GET: dosen pengampu; POST: mahasiswa peserta  | `auth:sanctum` dan pemeriksaan peran serta akses                  | Submission kelas lain dibaca atau dikirim           |
| `/api/v1/submissions/{submission}/grade`                                                                 | PUT                                       | Dosen pengampu                                | `auth:sanctum`, peran, dan pemeriksaan pengampu                   | Nilai mahasiswa kelas lain diubah                   |
| `/api/v1/notifications/{notification}/read`                                                              | POST                                      | Pemilik notifikasi                            | `auth:sanctum` dan pemeriksaan pemilik                            | Notifikasi pengguna lain diubah                     |

Tabel ini menjadi acuan pemeriksaan Policy pada Minggu 7. Prefix URL tidak memberi izin akses dengan sendirinya. Route detail yang memakai `shallow()` juga tetap membutuhkan pemeriksaan kepemilikan.

---

## BREAK

Data simulasi: submission `1` milik Mahasiswa A, submission `2` milik Mahasiswa B, course `1` milik Dosen A, course `2` milik Dosen B, dan assignment `99` milik course `2`. Bagian hasil berikut menjelaskan perilaku konfigurasi lab, bukan bukti pengujian langsung.

### **1. Mahasiswa A membuka submission Mahasiswa B**

#### Prediksi

Submission mahasiswa lain seharusnya ditolak meskipun saya sudah login.

#### Percobaan

Pada controller lab tanpa pemeriksaan pemilik, buka `/submissions/1`, lalu ganti menjadi `/submissions/2` sebagai Mahasiswa A.

#### Hasil

Binding menemukan submission `2` dan controller menampilkannya. Jawaban serta nilai Mahasiswa B dapat terbaca. Pada kode sekarang, `SubmissionController::show()` memeriksa pemilik dan keikutsertaan, sehingga akses tersebut seharusnya ditolak `403`.

#### Kesimpulan

Mengganti ID untuk membaca data orang lain merupakan IDOR. Login dan binding belum cukup; kepemilikan harus diperiksa.

---

### **2. Membuka tugas melalui mata kuliah yang salah**

#### Prediksi

Assignment `99` seharusnya tidak terbuka melalui course `1` karena milik course `2`.

#### Percobaan

Gunakan route lab `/courses/{course}/assignments/{assignment}` tanpa scoping dan pemeriksaan relasi, lalu buka `/courses/1/assignments/99`.

#### Hasil

Laravel mencari kedua model secara terpisah. Karena keduanya ada, tugas tetap bisa tampil meskipun hubungan induk dan anak salah. Route detail proyek sekarang memakai `shallow()`, sehingga URL lab ini bukan URL aktif.

#### Kesimpulan

URL bertingkat belum otomatis menjamin assignment milik course yang disebutkan.

---

### **3. Mengaktifkan `Route::scopeBindings()`**

#### Prediksi

Pasangan course dan assignment yang tidak sesuai akan menghasilkan `404`.

#### Percobaan

Masukkan route nested nomor 2 ke grup `Route::scopeBindings()`, pastikan `Course` memiliki relasi `assignments()`, lalu ulangi URL yang sama.

#### Hasil

Laravel mencari assignment `99` melalui relasi course `1`. Karena tugas itu milik course `2`, hasilnya `404`, berbeda dari konfigurasi tanpa scoping.

#### Kesimpulan

Scoping memeriksa hubungan induk dan anak. Izin pengguna tetap harus diperiksa, terutama pada route `shallow()` yang hanya membawa ID anak.

---

### **4. Mendaftarkan middleware melalui `app/Http/Kernel.php`**

#### Prediksi

Alias middleware bisa ditambahkan di `Kernel.php` seperti tutorial lama.

#### Percobaan

Cari `app/Http/Kernel.php`, lalu bandingkan dengan konfigurasi di `bootstrap/app.php`.

#### Hasil

Pada struktur Laravel 12 proyek ini, `Kernel.php` tidak ada. Alias `role` didaftarkan melalui `withMiddleware()` dan `$middleware->alias()` di `bootstrap/app.php`.

#### Kesimpulan

Tutorial harus disesuaikan dengan versi dan struktur Laravel yang digunakan.

---

### **5. Mengakses grup `role:admin` sebagai dosen**

#### Prediksi

Akun dosen akan ditolak karena route hanya mengizinkan admin.

#### Percobaan

Gunakan grup bermiddleware `auth` dan `role:admin`, lalu buka `/admin/courses` sebagai dosen.

#### Hasil

Middleware menolak peran dosen dengan `403 Forbidden` sebelum request masuk ke controller.

#### Kesimpulan

`auth` memeriksa login, sedangkan middleware peran menentukan siapa yang boleh masuk ke grup route.

---

### **6. Dosen A mengedit mata kuliah Dosen B**

#### Prediksi

Saya seharusnya tidak bisa mengedit mata kuliah dosen lain.

#### Percobaan

Pada konfigurasi lab, pasang `auth` dan `role:dosen` pada resource courses tanpa pemeriksaan pengampu. Sebagai Dosen A, buka `/courses/2/edit`, ubah data, lalu simpan.

#### Hasil

Kedua dosen lolos middleware karena perannya sama. Tanpa pemeriksaan `lecturer_id`, Dosen A bisa membuka form dan menyimpan perubahan milik Dosen B. Pada proyek sekarang, pengelolaan courses berada di area admin, sehingga skenario ini membutuhkan konfigurasi lab tersebut.

#### Kesimpulan

Middleware peran tidak memeriksa pemilik objek. Pemeriksaan kepemilikan atau Policy harus berlaku saat membuka form maupun menyimpan perubahan; menyembunyikan tombol Edit saja tidak cukup.
