# Dokumentasi Keamanan KampusLMS

## 1. Tujuan

Dokumen ini mencatat titik rawan Insecure Direct Object Reference (IDOR),
kontrol otorisasi yang digunakan untuk mencegah akses tanpa izin, dan test
otomatis yang menjadi bukti pengamanan pada aplikasi KampusLMS.

Dokumentasi ini berfokus pada otorisasi berbasis tiga peran:
- `admin`
- `dosen`
- `mahasiswa`

## 2. Prinsip Pengamanan

1. **Autentikasi:** permintaan ke halaman atau endpoint yang dilindungi harus
   berasal dari pengguna yang telah login.
2. **Otorisasi di server:** controller harus memeriksa hak akses menggunakan
   Policy atau pemeriksaan otorisasi yang sesuai. `@can` di Blade hanya
   mengatur tampilan; bukan pengganti pemeriksaan di server.
3. **Penyaringan query:** daftar data harus dibatasi sebelum hasil dikirim
   kepada pengguna, bukan mengambil seluruh data lalu menyaringnya di view.
4. **Kepemilikan data:** identitas pemilik dan relasi mata kuliah ditentukan
   dari pengguna yang terautentikasi dan resource pada route yang telah
   diperiksa, bukan dipercaya begitu saja dari input pengguna.
5. **Pengujian jalur penolakan:** test perlu memastikan pengguna yang tidak
   berhak ditolak, bukan hanya membuktikan bahwa pengguna yang berhak berhasil.

## 3. Tabel Titik Rawan IDOR

| No. | Titik rawan / skenario serangan | Kontrol pengamanan | Bukti test otomatis |
| --- | --- | --- | --- |
| 1 | Mahasiswa mengganti ID mata kuliah untuk membuka mata kuliah yang tidak diikutinya. | `CoursePolicy` memeriksa hak melihat mata kuliah; daftar course dibatasi berdasarkan peran dan relasi pengguna pada query. | `CourseApiTest`: mahasiswa hanya melihat mata kuliah yang diikuti dan ditolak saat membuka mata kuliah yang tidak diikuti. `PolicyAuthorizationTest`: `test_only_enrolled_students_can_view_course`. |
| 2 | Dosen mengganti ID course untuk melihat atau mengubah mata kuliah milik dosen lain. | `CoursePolicy` membatasi tampilan berdasarkan kepemilikan dosen dan pengelolaan course kepada admin. Controller juga harus menjalankan otorisasi. | `CourseApiTest`: `dosen cannot view course owned by another dosen`. `CourseManagementTest`: `non admin cannot create update or delete courses`. |
| 3 | Mahasiswa yang tidak terdaftar membuka materi melalui ID atau URL langsung. | `MaterialPolicy` memeriksa keanggotaan mata kuliah untuk akses mahasiswa. Controller menggunakan pemeriksaan otorisasi; daftar materi harus mengikuti cakupan course yang diakses. | `MaterialApiTest`: mahasiswa yang tidak terdaftar tidak dapat melihat materi. `MaterialWebTest`: `only enrolled students can read and students cannot manage materials`. `PolicyAuthorizationTest`: `test_unenrolled_students_cannot_view_materials`. |
| 4 | Dosen membuka, membuat, mengubah, atau menghapus materi pada course milik dosen lain. | `MaterialPolicy` mengizinkan dosen pemilik mengelola materi course sendiri dan menolak dosen lain. Controller menggunakan Policy untuk pemeriksaan akses. | `MaterialWebTest`: `other lecturer cannot create edit update or delete materials`. `PolicyAuthorizationTest`: `test_admin_and_course_owner_can_manage_materials`. |
| 5 | Mahasiswa mencoba melihat tugas yang belum dipublikasikan, termasuk dengan mengubah filter atau pagination. | `AssignmentPolicy` membatasi akses detail tugas mahasiswa; query daftar tugas mahasiswa hanya menampilkan status `published`. Filter tidak boleh menjadi cara untuk melewati otorisasi. | `AssignmentApiTest`: `students never receive drafts even with status filter or pagination`; `draft filter and counts do not disclose unpublished assignments` pada `Week6RegressionTest`. `AssignmentWebTest`: `enrolled student can only read published assignments and cannot manage them`. |
| 6 | Dosen mengakses atau mengubah tugas milik course dosen lain. | `AssignmentPolicy` memeriksa kepemilikan course dan hak atas tugas. Controller harus memeriksa Policy pada tindakan baca dan tulis; daftar tugas dibatasi sesuai peran. | `AssignmentWebTest`: `other lecturer cannot list create edit update publish or delete`. `AssignmentApiTest`: test penolakan dosen yang bukan pemilik. |
| 7 | Pengguna memalsukan `course_id` atau informasi kepemilikan saat membuat atau memperbarui materi maupun tugas. | Resource tujuan ditentukan dari route yang telah diotorisasi. Field kepemilikan tidak boleh dipercaya hanya karena dikirim oleh client. Validasi dan proses penyimpanan harus menggunakan data yang diizinkan. | `IdorIntegrationTest`: `lecturer ownership payload cannot move resources to foreign course`; `AssignmentWebTest`: `owner can create publish update and delete without trusting ownership fields`. |
| 8 | Mahasiswa A mengganti ID submission agar dapat membaca submission mahasiswa B. | `SubmissionPolicy` membatasi mahasiswa pada submission miliknya dan memeriksa relasi enrollment yang diperlukan. Controller wajib mengotorisasi resource yang diminta. | `IdorIntegrationTest`: `student submission ownership in shared course`; `ResourceOwnershipTest`: `student cannot view another students submission`. |
| 9 | Mahasiswa atau dosen yang tidak berwenang membuka daftar submission suatu tugas. | `SubmissionPolicy::viewAny` membatasi daftar submission kepada admin dan dosen pemilik course sesuai aturan aplikasi. Otorisasi dilakukan di controller, tidak cukup menyembunyikan tautan di Blade. | `SubmissionApiTest`: dosen pemilik dapat melihat daftar submission, dosen lain ditolak, dan mahasiswa ditolak. |
| 10 | Mahasiswa memalsukan `user_id` agar submission tercatat atas nama orang lain, atau mengirimkan submission untuk tugas yang berbeda. | Identitas mahasiswa diambil dari pengguna yang login dan assignment berasal dari resource route. Pemeriksaan enrollment, status publikasi, batas waktu, dan submission duplikat tetap diterapkan. | `IdorIntegrationTest`: `student submission payload uses authenticated user and route assignment`; `SubmissionApiTest`: penolakan mahasiswa yang tidak terdaftar, submission duplikat, tugas draft, dan submission terlambat yang tidak diizinkan. |
| 11 | Mahasiswa membuka nilai orang lain atau pengguna yang tidak berwenang mencoba memberi maupun mengubah nilai. | `GradePolicy` membatasi pembacaan nilai mahasiswa pada nilainya sendiri dan operasi penilaian kepada dosen pemilik course. `GradeController` memeriksa izin sebelum membuat atau memperbarui nilai. | `GradeApiTest`: dosen bukan pemilik ditolak, mahasiswa tidak dapat memberi nilai, dan nilai di luar rentang ditolak. `IdorIntegrationTest`: `student role cannot create update delete or grade`. |
| 12 | Pengguna yang tidak berhak mendaftarkan atau mengeluarkan mahasiswa dari course. | Pemeriksaan peran dan kepemilikan course pada alur pengelolaan enrollment harus dijalankan di server. Validasi juga memastikan pengguna yang didaftarkan memang mahasiswa. | `EnrollmentManagementTest`: dosen lain tidak dapat mengelola enrollment; mahasiswa tidak dapat mengelola enrollment; pengguna non-mahasiswa ditolak. |
| 13 | Pengguna mengganti UUID notifikasi untuk membaca atau menandai notifikasi orang lain sebagai sudah dibaca. | Query dan pemeriksaan kepemilikan notifikasi harus terikat pada pengguna yang sedang terautentikasi, bukan hanya pada UUID yang dikirim. | `IdorIntegrationTest`: `notification uuid is authorized for each of four identities`; `NotificationApiTest`: pengguna hanya melihat notifikasinya sendiri dan tidak dapat menandai notifikasi orang lain. |
| 14 | Admin yang mempunyai izin membaca course mencoba melakukan operasi tulis tugas atau penilaian melalui API tanpa hak yang sesuai. | Hak membaca course tidak otomatis memberikan izin menulis tugas atau memberi nilai. Pemeriksaan write tetap diterapkan pada controller dan Policy sesuai kontrak API. | `AccessIntegrationTest`: `admin read permissions do not grant assignment or grading writes`; `AssignmentApiTest`: mahasiswa dan admin tidak dapat membuat, mengubah, menghapus, atau memublikasikan tugas melalui API. |
| 15 | Pengguna mencoba mengubah role, identitas sensitif, atau data pemilik dengan manipulasi field request. | Gunakan validasi dan daftar field yang diizinkan. Field sensitif harus ditangani secara eksplisit di server, bukan diterima mentah melalui mass assignment. | `UserManagementTest`: `manipulated sensitive fields are ignored when updating user`, `manipulated sensitive fields are ignored when creating user`, serta test penolakan perubahan role oleh non-admin. |
| 16 | Pengguna tanpa autentikasi mengakses endpoint atau halaman yang dilindungi. | Middleware autentikasi melindungi route; API dan web menggunakan respons yang sesuai untuk permintaan tanpa sesi atau token yang valid. | `AccessIntegrationTest`: guest API requests ditolak tanpa data atau penulisan; guest web requests diarahkan ke login. `WebAuthenticationTest`: guest diarahkan ke login dari dashboard. |

## 4. Perbedaan `@can` dan Otorisasi Server

Direktif Blade seperti `@can` digunakan untuk menyembunyikan tombol atau tautan
yang tidak boleh digunakan oleh pengguna. Namun, pengguna masih dapat mencoba
mengakses URL secara langsung atau mengirim request sendiri.

Karena itu, controller tetap harus menjalankan otorisasi, misalnya melalui
`Gate::authorize()` atau pemeriksaan Policy yang setara. Penyaringan data pada
query diperlukan agar daftar tidak membocorkan resource yang tidak boleh
dilihat pengguna.

## 5. Menjalankan Pengujian

Jalankan seluruh test aplikasi pada lingkungan testing:

```bash
php artisan test --env=testing
```

Test terkait otorisasi dan IDOR dapat diperiksa secara khusus:

```bash
php artisan test --env=testing tests/Feature/PolicyAuthorizationTest.php
php artisan test --env=testing tests/Feature/IdorIntegrationTest.php
php artisan test --env=testing tests/Feature/ResourceOwnershipTest.php
```

## 6. Hasil Pengujian Terakhir

Pada pengujian lokal terakhir sebelum pembaruan kebijakan nilai mahasiswa:

- `PolicyAuthorizationTest`: 5 test lulus, 35 assertions.
- Seluruh suite: 253 test lulus, 3.774 assertions.
- `scripts/test-authz.sh`: 216 test lulus, 3.443 assertions.

Angka tersebut merupakan hasil pengujian lokal pada saat dicatat. Jalankan kembali pengujian setelah perubahan kode dan perbarui angka jika hasilnya berubah.

## 7. Catatan Audit Lanjutan

Route CRUD web untuk materi dan tugas admin telah didaftarkan dan pengujian integrasinya telah lulus melalui `AssignmentWebTest` dan `MaterialWebTest`.

Pemeriksaan tetap perlu dilakukan jika route, middleware, Policy, controller, atau cakupan test berubah. Kelulusan test lokal tidak dengan sendirinya membuktikan bahwa CI GitHub telah lulus.