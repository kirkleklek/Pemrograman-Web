# Week 1

**Mata Kuliah: Pemrograman Web**\
**Pertemuan: 3**\
**Tanggal: 14 September 2026**\
**Topik: Database dan CRUD**\

---

## Ringkasan

### Migrasi: riwayat versi untuk struktur database
Migrasi adalah kode yang mendeskripsikan perubahan struktur database yang menjadi solusi permaslaahan isi database yang tidak sama dalam satu proyek yang sama. Dengan migrasi, struktur database ikut ke repository. Sehingga anggota lain hanya perlu menjalankan `php artisan migrate:fresh --seed` dan akan mendapatkan database yang sama persis satu sama lain.

Dalam migration sendiri memiliki yang namanya `up()` dan `down()`, yang dimana isi `up()` dan isi `down()` saling berlawanan. Misalnya jikalau `up()` adalah membuat tabel, maka isi `down()` adalah menghapus tabel. Jika salah satu tidak memiliki isi, migrasi tesebut akan dianggap sebagai migrasi rusa dan akan ketahuan saat CI menjalankan `migrate:refresh`

### Foreign key dan perilaku penghapusan
| Perilaku | Artinya | Kapan dipakai di KampusLMS |
|------|-----|-------|
| `cascadeOnDelete()` | Penghapusan berantai | Mata kuliah dihapus → materi & tugasnya ikut. Memang tidak berguna lagi. |
| `restrictOnDelete()` | 	Tolak penghapusan selama masih ada anak | Dosen tidak boleh dihapus selama masih mengampu mata kuliah. |
| `nullOnDelete()` | Hapus induk → kolom anak jadi NULL | Jarang dipakai di proyek ini. |

### Unique composite: aturan bisnis yang ditegakkan database
```php
$table->unique(['course_id', 'user_id']);        // di course_user
$table->unique(['assignment_id', 'user_id']);    // di submissions
```
Yang pertama mencegah biar mahasiswa nggak terdaftar 2 kali dalam matkul yang sama, yang kedua agar mencegah satu mahasiswa mengirimkan 2 pengumpulan tugas yang sama

Aplikasi untuk pesan error, database untuk jaminan

### Eloquent dan relasi
`$fillable` dan mass assignment
```php
Course::create($request->all());
```
`$request->all()` berisi semua yang user kirim termasuk `field` yang tidak ada di formulir Anda. Kalau model `User` punya kolom role dan `$fillable`-nya longgar, seorang mahasiswa bisa menambahkan role=admin ke request pendaftaran, dan ia menjadi admin.

Pertahanannya:

* `$fillable` hanya berisi kolom yang memang boleh diisi user. Kolom seperti `role`, `is_verified`, `lecturer_id` sebaiknya tidak dimasukkan di `$fillable`, atau diisi eksplisit di controller.

* Jangan pakai `$request->all()`. Gunakan `$request->validated()` atau `$request->only([...])`.

* Jangan pernah memakai `protected $guarded = [];` itu mematikan perlindungan sepenuhnya.

### Casting di Laravel 12

Di Laravel 11+, casting ditulis sebagai method, bukan properti
```php
protected function casts(): array
{
    return [
        'due_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```
Kalau masih menggunakan `protected $casts = [...]` sebagai properti, berarti masih pakai gaya Laravel 10. Masih bisa jalan, tapi bukan konvensi Laravel 12.

### Factory dan Seeder
Factory adalah pabrik data dummy; seeder yang menjalankan factory.

Contoh : 
```php
// database/factories/CourseFactory.php
public function definition(): array
{
    return [
        'code' => 'SI' . fake()->unique()->numerify('#######'),
        'name' => fake()->randomElement(['Basis Data', 'Pemrograman Web', 'Jaringan Komputer']),
        'sks' => fake()->numberBetween(2, 4),
        'status' => 'active',
    ];
}
```
---
## READ
1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.

<p align="center">
  <img src="./image/Read-01.png" width="300">
  <br>
  <b>Gambar 1 : ERD Database</b>
</p>

2. Untuk setiap foreign key, tentukan perilaku onDelete-nya dan tuliskan alasannya.


3. View yang dikembalikan adalah view `/tentang` di path `tentang.blade.php`

4. Layout yang membungkus tentang adalah file `layout.blade.php`, karna bisa dilihat di file `tentang.blade.php` layout di bungkus menggunakan `<x-layout>`

5. sesuai dengan gambar
![Deskripsi gambar](foto/image.png)

bisa dilihat bahwa hasil dari `php artisan route:list --path=tentang` sama seperti yang saya telah tulis di nomor 1


---

## Break

1. Prediksi awal : Method `POST` digunakan untuk menambah, sedangkan method tersebut terletak di route index yang harusnya menampilkan bukan menambah data sehingga akan menampilkan pesan error.

Hasil : 405 Error muncul ketika mencoba mengkases `course.index`, hal tersebut terjadi karena browser itu selalu mengirimkan method `GET` ke laravel ketika kita mengakses suatu laman atau mengklik suatu button. Nah karena tidak serasi antara request method browser dengan mehtod route, Laravel menampilkan pesan bahwa program error.

2. Prediksi awal : Laravel akan memunculkan pesan error karena tujuan viewnya tidak ada atau tidak ditemukan didalam struktur file

Hasil : Kurang lebih sama dengan prediksi saya, yaitu laravel akan menampilkan error berupa viewargumentexception berupa `View [] not found.`.

3. Prediksi awal : Laman yang menampilkan `course.index` akan error dikarenakan dalam view `index.blade.php` terdapat button yang ngedirect ke view `show.blade.php`. Ketika route `course.show` tidak didefiniskan maka laravel akan bingung.

Hasil : Error muncul karena dalam view `course.index` itu memanggil `course.show` sedangkan di `web.php` itu `cours.show` tidak ada.

4. Prediksi awal : error dikarenakan route `course.create` akan dibaca sebagai id oleh sistem, sehingga sistem mencari data dengan id `create` 

Hasil : Error 404 muncul, dikarenakan file laravel membaca route dari urutan atas ke bawah. Nah karena `course.show` berada di atas `course.create` dan `course.show` menggunakan parameter dinamis yaitu`{id}`, maka nilai `create` dibaca sebagai id oleh laravel.

5. Prediksi awal : ketika `{{  }}` diganti menjadi `{!!  !!}` dan diisi degan script js, program akan membaca itu sebagai HTML mentah dari program dan menjalankan script tersebut.

Hasil : `{{ }}` melakukan escape sehingga HTML/JavaScript dari variabel `$nama` tidak di eksekusi, kalau pakai `{!! !!}` laravel bakal menganggap HTML mentah. Karna mengandung `<script>` dan menggunakan `{!! !!}`, browser menjalankannya dan menyebabkan XSS.

6. Prediksi awal : ketika `@vite` dihapus, design tidak akan muncul dikarenakan pemanggilnya yaitu `@vite` tidak ada di file `layout.blade.php`

Hasil : Sama dengan prediksi, design tidak akan muncul dikarenakan pemanggilnya yaitu `@vite` tidak ada di file `layout.blade.php`

7. Prediksi awal : Design tidak akan ke update, namun selagi sudah `npm run build` maka design tersimpan

Hasil : Kurang lebih sama dengan prediksi, design yang belum di `npm run build ` tidak ke update.

8. Prediksi awal : Akan error karena laravel tidak tau parameter yang dipanggil user

Hasil : `syntax error, unexpected token ";", expecting ")"`, nah disini nilai`id` tidak ada.