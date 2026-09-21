# 3.3 Read → Break → Fix → Build

__Nama : Fabyo Nathanael Suoth\
NIM : 10241027__

---

## READ

### 1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.
Sesuai arahan untuk dikosongkan

---

### 2. Untuk setiap foreign key, tentukan perilaku `onDelete`-nya dan tuliskan alasannya.
- courses.lecturer_id > users.id
Menggunakan restrictOnDelete(). Artinya, data dosen tidak dapat dihapus apabila masih digunakan sebagai pengampu mata kuliah. Hal ini bertujuan agar mata kuliah yang masih tersimpan tidak kehilangan informasi dosen yang mengajarnya.

- course_user.course_id > courses.id
Menggunakan cascadeOnDelete(). Jika sebuah mata kuliah dihapus, data pendaftaran mahasiswa pada mata kuliah tersebut juga akan ikut terhapus karena data pada tabel pivot hanya berfungsi sebagai penghubung antara user dan mata kuliah.

- course_user.user_id > users.id
Menggunakan cascadeOnDelete(). Ketika user dihapus, seluruh data pendaftarannya pada tabel course_user ikut dihapus karena data tersebut tidak diperlukan lagi tanpa user yang bersangkutan.

- materials.course_id > courses.id
Menggunakan cascadeOnDelete(). Jika mata kuliah dihapus, seluruh materi yang berkaitan dengan mata kuliah tersebut juga dihapus karena materi merupakan bagian dari mata kuliah tersebut.

- assignments.course_id > courses.id
Menggunakan cascadeOnDelete(). Ketika mata kuliah dihapus, tugas-tugas yang berada di dalam mata kuliah tersebut juga ikut dihapus karena tugas tersebut bergantung pada mata kuliah.

- assignments.created_by > users.id
Tidak menggunakan aturan cascadeOnDelete() atau restrictOnDelete() secara langsung. Penghapusan user dapat ditolak oleh database apabila masih terdapat tugas yang dibuat oleh user tersebut, sehingga hubungan antara tugas dan pembuatnya tetap terjaga.

- submissions.assignment_id > assignments.id
Menggunakan cascadeOnDelete(). Apabila tugas dihapus, seluruh submission atau jawaban mahasiswa untuk tugas tersebut juga akan terhapus karena submission tersebut tidak lagi memiliki tugas yang menjadi acuannya.

- submissions.user_id > users.id
Tidak memiliki aturan cascadeOnDelete() atau restrictOnDelete() secara langsung. User yang masih mempunyai submission terkait tidak dapat dihapus begitu saja apabila penghapusan tersebut melanggar hubungan foreign key.

- grades.submission_id > submissions.id
Menggunakan cascadeOnDelete() dan unique(). cascadeOnDelete() menyebabkan nilai ikut terhapus ketika submission dihapus, sedangkan unique() memastikan satu submission hanya memiliki satu data nilai.

- grades.graded_by > users.id
Tidak memiliki aturan penghapusan secara langsung. Hal ini menjaga agar user yang memberikan nilai tetap memiliki keterkaitan dengan data nilai yang sudah dibuat.

---

### 3. Jawab: kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya? Kenapa dirancang begitu?
Jika menggunakan `onDelete('cascade')` pada `courses.lecturer_id`, maka mata kuliah yang dimiliki dosen tersebut juga akan terhapus ketika dosennya dihapus, hal ini dirancang karena mata kuliah tersebut memiliki hubungan langsung dengan dosen. Jika dosen sudah tidak ada dalam sistem, data mata kuliah yang hanya bergantung pada dosen tersebut tidak perlu dipertahankan.

---

### 4. Jawab: kenapa `grades.submission_id` bersifat unique, bukan sekadar index biasa?
Pada `grades.submission_id` digunakan `unique()` karena satu submission hanya boleh memiliki satu nilai, dengan adanya `unique` database akan mencegah satu submission memiliki lebih dari satu data nilai.

---

## BREAK

### 1. Hapus `unique(['course_id','user_id'])` dari `course_user`, lalu daftarkan mahasiswa yang sama dua kali 
Prediksi: Jika constraint `unique(['course_id','user_id'])` dihapus, kemungkinan mahasiswa yang sama dapat didaftarkan lebih dari satu kali pada mata kuliah yang sama, database tidak lagi memiliki aturan untuk mencegah kombinasi `course_id` dan `user_id` yang sama.

Hasil: Data pendaftaran yang sama dapat masuk lebih dari satu kali tanpa adanya pesan error dari database.

---

### 2. Tambahkan `role` ke `$fillable` model `User`, lalu kirim request pembuatan user dengan `role=admin` lewat form yang tidak punya field role
Prediksi: Karena `role` sudah ditambahkan ke dalam `$fillable`, Laravel akan mengizinkan atribut tersebut diisi melalui request meskipun pada form tidak tersedia input untuk `role`.

Hasil: User baru dapat dibuat dengan `role=admin`, walaupun pada form tidak terdapat pilihan untuk menentukan `role`, field yang tidak tersedia pada frontend tetap dapat dikirim secara langsung melalui request, selama atribut tersebut terdapat dalam `$fillable`, Laravel dapat memprosesnya melalui mass assignment.

---

### 3. Ganti seluruh `$fillable` dengan `protected $guarded = [];` lalu ulangi nomor 2
Prediksi: Penggunaan `$guarded = []` berarti tidak ada atribut yang dilindungi dari mass assignment, bukan hanya `role` tetapi seluruh kolom pada tabel `users` berpotensi dapat diisi melalui request.

Hasil: Mass assignment menjadi lebih terbuka karena semua atribut pada model dapat diisi tanpa perlindungan, `$guarded = []` tidak aman digunakan karena menghilangkan pembatasan terhadap atribut yang dapat diisi, berbeda dengan `$fillable` yang hanya mengizinkan kolom tertentu melalui mekanisme whitelist.

---

### 4. Kosongkan isi `down()` di satu migrasi, lalu jalankan `php artisan migrate:refresh`
Prediksi: Saat method `down()` dikosongkan, migration tersebut tidak mempunyai instruksi untuk mengembalikan atau menghapus perubahan yang dibuat oleh `up()`, ketika `migrate:refresh` dijalankan proses rollback kemungkinan gagal.

Hasil: Migration tidak dapat melakukan rollback dengan benar karena method `down()` tidak memiliki instruksi untuk membatalkan perubahan yang dibuat pada `up()`.

---

### 5. Ubah `restrictOnDelete` pada `lecturer_id` menjadi `cascadeOnDelete`, lalu hapus satu dosen
Prediksi: Pas `restrictOnDelete()` diganti menjadi `cascadeOnDelete()`, penghapusan dosen tidak lagi ditolak ketika dosen tersebut masih memiliki mata kuliah, sebaliknya mata kuliah yang menggunakan dosen tersebut sebagai `lecturer_id` akan ikut terhapus.

Hasil: Dosen berhasil dihapus dan mata kuliah yang memiliki `lecturer_id` dosen tersebut ikut terhapus secara otomatis.

---