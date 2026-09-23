# **Week 3**

**Mata Kuliah: Pemrograman Web**
**Pertemuan: 3**
**Tanggal: 15 September 2026**
**Topik: ERD, Relasi Database, dan Mass Assignment**

---

## **READ**

### **1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.**

Nomor ini saya skip terlebih dahulu sesuai instruksi.

### **2. Untuk setiap foreign key, tentukan perilaku `onDelete`-nya dan jelaskan alasannya.**

Berdasarkan migrasi yang ada pada project:

- `courses.lecturer_id`
    - Menggunakan `restrictOnDelete()`.
    - Artinya, dosen tidak bisa dihapus jika masih memiliki mata kuliah yang diajar. Tujuannya agar mata kuliah tidak kehilangan data dosen pengampunya.

- `materials.course_id`
    - Menggunakan `cascadeOnDelete()`.
    - Jika mata kuliah dihapus, semua materi pada mata kuliah tersebut juga ikut dihapus karena materi tidak lagi dibutuhkan.

- `assignments.course_id`
    - Menggunakan `cascadeOnDelete()`.
    - Jika mata kuliah dihapus, tugas yang ada di dalamnya juga ikut dihapus karena tugas tersebut merupakan bagian dari mata kuliah.

- `assignments.created_by`
    - Tidak memiliki aturan `cascadeOnDelete()` atau `restrictOnDelete()` secara langsung.
    - Database biasanya akan menolak penghapusan user jika masih ada tugas yang dibuat oleh user tersebut.

- `submissions.assignment_id`
    - Menggunakan `cascadeOnDelete()`.
    - Jika tugas dihapus, submission atau jawaban mahasiswa untuk tugas tersebut juga ikut dihapus.

- `submissions.user_id`
    - Tidak memiliki aturan `cascadeOnDelete()` atau `restrictOnDelete()` secara langsung.
    - User biasanya tidak bisa dihapus jika masih memiliki submission yang tersimpan.

- `grades.submission_id`
    - Menggunakan `unique()` dan `cascadeOnDelete()`.
    - `unique()` memastikan satu submission hanya memiliki satu nilai. Jika submission dihapus, nilai tersebut juga ikut dihapus.

- `grades.graded_by`
    - Tidak memiliki aturan penghapusan secara langsung.
    - User yang memberikan nilai biasanya tidak bisa langsung dihapus jika masih terhubung dengan data nilai.

### **3. Kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya? Kenapa dirancang begitu?**

Pada `courses.lecturer_id` terdapat `restrictOnDelete()`.

Artinya, dosen tidak bisa dihapus jika masih memiliki mata kuliah yang diajar. Database akan menolak proses penghapusan sampai mata kuliah tersebut dipindahkan ke dosen lain atau dihapus terlebih dahulu.

Aturan ini digunakan agar tidak ada mata kuliah yang kehilangan dosen pengampu dan supaya hubungan antar data tetap jelas.

### **4. Kenapa `grades.submission_id` bersifat unique, bukan sekadar index biasa?**

Karena satu submission seharusnya hanya memiliki satu nilai.

Jika hanya menggunakan index biasa, satu `submission_id` masih bisa muncul berkali-kali di tabel `grades`. Akibatnya, satu submission bisa memiliki lebih dari satu nilai.

Dengan `unique()`, database memastikan bahwa satu submission hanya dapat memiliki satu grade.

---

## **BREAK**

### **1. Hapus `unique(['course_id', 'user_id'])` dari `course_user`, lalu daftarkan mahasiswa yang sama dua kali**

#### **Prediksi**

Saya memprediksi mahasiswa yang sama bisa didaftarkan dua kali ke mata kuliah yang sama karena aturan `unique` sudah dihapus.

#### **Hasil yang nyata**

Hasilnya sesuai prediksi. Mahasiswa yang sama berhasil didaftarkan lebih dari satu kali ke mata kuliah yang sama.

#### **Kesimpulan**

`unique(['course_id', 'user_id'])` berfungsi untuk mencegah mahasiswa terdaftar dua kali pada mata kuliah yang sama. Jika aturan ini dihapus, data duplikat dapat masuk ke database.

---

### **2. Tambahkan `role` ke `$fillable` model `User`, lalu kirim request dengan `role=admin` melalui form yang tidak memiliki field role**

#### **Prediksi**

Saya memprediksi `role=admin` tetap bisa masuk karena `role` sudah diperbolehkan dalam `$fillable`.

#### **Hasil yang nyata**

Hasilnya sesuai prediksi. User baru dapat memiliki `role=admin` meskipun pada form tidak terdapat input untuk role.

Hal ini terjadi karena request dapat dimanipulasi dan nilai `role` tetap diterima oleh model.

#### **Kesimpulan**

Tampilan form saja tidak cukup untuk melindungi data. `$fillable` juga harus diatur dengan hati-hati agar atribut penting seperti `role` tidak bisa diubah sembarangan melalui request.

---

### **3. Ganti seluruh `$fillable` dengan `protected $guarded = [];`, lalu ulangi nomor 2**

#### **Prediksi**

Saya memprediksi `role=admin` tetap dapat masuk karena semua atribut sekarang diperbolehkan untuk mass assignment.

#### **Hasil yang nyata**

Hasilnya sesuai prediksi. Setelah menggunakan `protected $guarded = [];`, semua atribut dapat dimasukkan melalui mass assignment, termasuk `role`.

#### **Kesimpulan**

`protected $guarded = [];` membuat semua atribut terbuka untuk mass assignment. Hal ini berisiko jika request dari pengguna tidak dikontrol dengan baik, terutama untuk data penting seperti role dan hak akses.

---

### **4. Kosongkan isi `down()` di satu migrasi, lalu jalankan `php artisan migrate:refresh`**

#### **Prediksi**

Saya memprediksi proses `migrate:refresh` akan bermasalah karena Laravel tidak memiliki perintah untuk mengembalikan perubahan dari migrasi tersebut.

#### **Hasil yang nyata**

Hasilnya sesuai prediksi. Proses rollback tidak berjalan dengan benar karena fungsi `down()` kosong.

#### **Kesimpulan**

Fungsi `down()` penting untuk mengembalikan perubahan yang dibuat oleh fungsi `up()`. Jika `down()` kosong, migrasi menjadi sulit di-rollback dan dapat menyebabkan masalah saat melakukan `migrate:refresh` atau testing.

---

### **5. Ubah `restrictOnDelete` pada `lecturer_id` menjadi `cascadeOnDelete`, lalu hapus satu dosen**

#### **Prediksi**

Saya memprediksi ketika dosen dihapus, mata kuliah yang diajar oleh dosen tersebut juga ikut terhapus.

#### **Hasil yang nyata**

Hasilnya sesuai prediksi. Setelah menggunakan `cascadeOnDelete()`, penghapusan dosen menyebabkan mata kuliah yang terhubung dengannya ikut terhapus.

Data lain yang terhubung dengan mata kuliah tersebut juga dapat ikut terhapus jika menggunakan `cascadeOnDelete()`.

#### **Kesimpulan**

`cascadeOnDelete()` memang mempermudah penghapusan data yang saling berhubungan. Namun, penggunaannya harus hati-hati karena satu penghapusan dapat menyebabkan banyak data lain ikut hilang.

---

## **Ringkasan**

Dari pembelajaran minggu ini, saya memahami bahwa:

- `onDelete` menentukan apa yang terjadi pada data lain ketika sebuah data dihapus.
- `unique()` digunakan untuk mencegah data yang seharusnya hanya satu menjadi duplikat.
- `$fillable` dan `$guarded` penting untuk mengatur atribut yang boleh masuk melalui mass assignment.
- Fungsi `down()` diperlukan agar migrasi dapat dikembalikan atau di-rollback.
- `cascadeOnDelete()` harus digunakan dengan hati-hati karena dapat menghapus banyak data yang saling berhubungan.

Jadi, desain database dan model Laravel tidak hanya menentukan hubungan antar data, tetapi juga berpengaruh pada keamanan dan konsistensi data dalam aplikasi.
