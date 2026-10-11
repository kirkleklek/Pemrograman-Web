# Week 5

**Mata Kuliah: Pemrograman Web**  
**Pertemuan: 5**  
**Tanggal: 29 September 2026**  
**Topik: Routing Lanjutan, Route Model Binding, IDOR, dan Middleware**

---

Catatan: Saya memakai kondisi awal modul Minggu 5 sebagai skenario lab dalam laporan ini. Bagian BREAK merupakan simulasi sesuai instruksi modul, bukan hasil pengujian browser yang sudah diverifikasi pada kode terbaru. Saat saya memeriksa proyek sekarang, route sudah memakai prefix peran, `scopeBindings()`, dan beberapa controller sudah mengecek kepemilikan. URL pendek seperti `/submissions/2` dipakai untuk menjelaskan skenario awal, bukan daftar URL aktif proyek sekarang.

## READ

### **1. Jalankan `php artisan route:list --except-vendor`**

Saya menjalankan perintah berikut dari folder proyek:

```bash
php artisan route:list --except-vendor
```

Perintah berhasil dan menampilkan 54 route. Saya merapikan sebagian hasilnya dalam tabel supaya lebih mudah dibaca:

| Method | URI | Nama Route | Controller / Method |
| --- | --- | --- | --- |
| GET\|HEAD | admin/courses | admin.courses.index | CourseController@index |
| POST | admin/courses | admin.courses.store | CourseController@store |
| GET\|HEAD | admin/courses/create | admin.courses.create | CourseController@create |
| GET\|HEAD | admin/courses/{course} | admin.courses.show | CourseController@show |
| GET\|HEAD | admin/courses/{course}/edit | admin.courses.edit | CourseController@edit |
| PUT\|PATCH | admin/courses/{course} | admin.courses.update | CourseController@update |
| DELETE | admin/courses/{course} | admin.courses.destroy | CourseController@destroy |
| GET\|HEAD | dosen/courses/{course}/assignments | dosen.courses.assignments.index | AssignmentController@index |
| POST | dosen/courses/{course}/assignments | dosen.courses.assignments.store | AssignmentController@store |
| GET\|HEAD | dosen/courses/{course}/assignments/create | dosen.courses.assignments.create | AssignmentController@create |
| GET\|HEAD | dosen/assignments/{assignment} | dosen.assignments.show | AssignmentController@show |
| GET\|HEAD | dosen/assignments/{assignment}/edit | dosen.assignments.edit | AssignmentController@edit |
| PUT\|PATCH | dosen/assignments/{assignment} | dosen.assignments.update | AssignmentController@update |
| DELETE | dosen/assignments/{assignment} | dosen.assignments.destroy | AssignmentController@destroy |
| GET\|HEAD | dosen/assignments/{assignment}/submissions | dosen.assignments.submissions.index | SubmissionController@index |
| GET\|HEAD | dosen/submissions/{submission} | dosen.submissions.show | SubmissionController@show |
| GET\|HEAD | mahasiswa/courses/{course}/assignments | mahasiswa.courses.assignments.index | AssignmentController@index |
| GET\|HEAD | mahasiswa/assignments/{assignment} | mahasiswa.assignments.show | AssignmentController@show |
| POST | mahasiswa/assignments/{assignment}/submissions | mahasiswa.assignments.submissions.store | SubmissionController@store |
| GET\|HEAD | mahasiswa/submissions/{submission} | mahasiswa.submissions.show | SubmissionController@show |

Saya melihat satu `Route::resource()` lengkap menghasilkan tujuh aksi: `index`, `create`, `store`, `show`, `edit`, `update`, dan `destroy`. Jadi saya tidak perlu menulis tujuh route satu per satu. Request `PUT` dan `PATCH` sama-sama menuju `update()`.

Di proyek sekarang, resource assignments ditulis bertingkat dan memakai `shallow()`:

```php
Route::resource('courses.assignments', AssignmentController::class)
    ->shallow();
```

Artinya, daftar, form tambah, dan proses simpan masih membawa `{course}`. Detail, edit, update, dan hapus cukup membawa `{assignment}`. Untuk submissions, `only(['index', 'show'])` di grup dosen dan `only(['show', 'store'])` di grup mahasiswa membatasi aksi yang dibuat.

### **2. Tandai setiap route yang menerima parameter model**

Saya menandai route modul courses, assignments, dan submissions dari tabel tadi sebagai berikut:

| Parameter | Route yang menangkap parameter |
| --- | --- |
| `{course}` | `GET`, `PUT/PATCH`, dan `DELETE /admin/courses/{course}`; `GET /admin/courses/{course}/edit` |
| `{course}` | `GET` dan `POST /dosen/courses/{course}/assignments`; `GET /dosen/courses/{course}/assignments/create`; `GET /mahasiswa/courses/{course}/assignments` |
| `{assignment}` | `GET`, `PUT/PATCH`, dan `DELETE /dosen/assignments/{assignment}`; `GET /dosen/assignments/{assignment}/edit`; `GET /mahasiswa/assignments/{assignment}` |
| `{assignment}` | `GET /dosen/assignments/{assignment}/submissions`; `POST /mahasiswa/assignments/{assignment}/submissions` |
| `{submission}` | `GET /dosen/submissions/{submission}` dan `GET /mahasiswa/submissions/{submission}` |

Di luar tiga modul itu, route web juga menangkap `{user}` untuk pengelolaan user dan `{material}` untuk materi. Route API memakai `{course}`, `{assignment}`, `{submission}`, dan `{notification}`. Kurung kurawal berarti bagian URL itu berubah sesuai data yang diminta; supaya menjadi model otomatis, nama parameter dan tipe model pada method juga harus sesuai.

Contohnya:

```php
public function show(Submission $submission)
{
    return view('submissions.show', compact('submission'));
}
```

Saat URL berisi `/submissions/2`, Laravel otomatis mencarikan submission dengan ID `2` di database, lalu menyerahkan objeknya ke `$submission`. Saya tidak perlu mengetik `Submission::findOrFail($id)` manual. Kalau datanya tidak ada, Laravel mengembalikan `404 Not Found`.

Saya baru memahami bahwa fitur ini memudahkan pencarian data, tetapi belum menentukan apakah saya berhak membukanya. Penjelasan binding dan pembatasan relasi ini juga ada di [dokumentasi routing Laravel 12](https://laravel.com/docs/12.x/routing#route-model-binding).

### **3. Analisis: siapa yang boleh mengakses vs kondisi saat ini**

Menurut saya, akses yang ideal adalah:

- Mata kuliah boleh dikelola admin. Dosen hanya boleh mengubah mata kuliah yang diampunya, kalau fitur tersebut memang diberikan kepada dosen.
- Tugas boleh dikelola dosen pengampu. Mahasiswa yang terdaftar di mata kuliah boleh membaca tugas sesuai aturan publikasinya.
- Submission dan nilainya hanya boleh dilihat mahasiswa pemilik serta dosen pengampu. Mahasiswa lain tidak boleh membaca jawaban temannya.

Pada kondisi awal yang dipakai dalam skenario Minggu 5, saya harus mengakui bahwa **belum ada pengaman apa pun untuk kepemilikan data**. Laravel baru sebatas mengecek apakah datanya ada di database, tetapi belum mengecek siapa yang sedang membukanya. Login saja juga belum menjawab pertanyaan itu.

Saya awalnya mengira kalau objek sudah ditemukan oleh Laravel, berarti aman untuk ditampilkan. Ternyata ada dua pertanyaan berbeda: “datanya ada?” dan “saya boleh melihat data ini?” Route Model Binding menjawab pertanyaan pertama.

Untuk kode terbaru yang saya baca, kondisi itu sudah berubah. `SubmissionController::show()` mengecek pemilik submission atau dosen pengampu, dan `AssignmentController` juga mengecek pengampu atau keikutsertaan mahasiswa. Jadi catatan “belum ada pengaman” di sini mengacu pada skenario awal lab, bukan klaim tentang seluruh aplikasi terbaru.

### **4. Daftar Titik Rawan IDOR**

Saya mencatat titik rawan pada skenario awal berikut. IDOR saya pahami sebagai kejadian ketika mengganti ID membuat saya bisa mengakses data yang bukan hak saya.

| No | URL / Route | Parameter | Siapa yang Berhak | Risiko Kebocoran Data | Rencana Perbaikan |
| --- | --- | --- | --- | --- | --- |
| 1 | `GET /submissions/{submission}` | `{submission}` | Mahasiswa pemilik dan dosen pengampu | Jawaban, catatan, dan nilai mahasiswa lain terbaca | `SubmissionPolicy::view` di Minggu 7 |
| 2 | `GET /courses/{course}/assignments/{assignment}` | `{course}`, `{assignment}` | Dosen pengampu dan mahasiswa peserta sesuai aturan tugas | Tugas mata kuliah lain muncul lewat induk URL yang salah | Scope Bindings untuk relasi; `AssignmentPolicy::view` di Minggu 7 untuk hak akses |
| 3 | `GET /courses/{course}` | `{course}` | Admin, dosen pengampu, dan mahasiswa peserta sesuai fitur | Detail mata kuliah yang bukan hak pengguna terbaca | `CoursePolicy::view` di Minggu 7 |
| 4 | `GET /courses/{course}/edit` dan `PUT/PATCH /courses/{course}` | `{course}` | Admin atau dosen pengampu yang diberi izin mengelola | Data milik dosen lain terbaca dan bisa diubah | `CoursePolicy::update` di Minggu 7 |
| 5 | `GET /assignments/{assignment}/submissions` | `{assignment}` | Dosen pengampu | Daftar jawaban dan identitas mahasiswa kelas lain terbaca | Policy untuk akses daftar submission di Minggu 7 |
| 6 | `DELETE /courses/{course}/assignments/{assignment}` | `{course}`, `{assignment}` | Dosen pengampu yang berhak menghapus tugas | Tugas kelas lain bisa dihapus | Scope Bindings dan `AssignmentPolicy::delete` di Minggu 7 |

Saya tidak bisa mengandalkan Scope Bindings untuk semua baris. Route yang cuma membawa satu ID, termasuk route detail hasil `shallow()`, tidak punya pasangan induk-anak di URL yang bisa diperiksa. Hak akses ke objeknya tetap perlu diperiksa.

---

## BREAK

Saya memakai data contoh: submission `1` milik Mahasiswa A, submission `2` milik Mahasiswa B, course `1` milik Dosen A, course `2` milik Dosen B, dan assignment `99` milik course `2`. Angka ini adalah asumsi data lab. Hasil di bawah menjelaskan perilaku pada konfigurasi simulasi tersebut.

### **1. Login sebagai Mahasiswa A, lalu buka submission Mahasiswa B**

#### Prediksi

Saya memprediksi submission Mahasiswa B akan ditolak karena saya masuk sebagai Mahasiswa A. Awalnya saya mengira status sudah login cukup untuk membedakan data masing-masing mahasiswa.

#### Percobaan

Dalam skenario ini, saya login sebagai Mahasiswa A, membuka submission sendiri, lalu mengganti angka di address bar:

```text
http://kampuslms.test/submissions/1
http://kampuslms.test/submissions/2
```

Controller lab hanya menerima `Submission $submission` dan mengembalikan view, tanpa pemeriksaan `user_id`.

#### Hasil yang nyata

Pada controller tanpa pemeriksaan pemilik tersebut, submission `2` tetap ditampilkan. Jawaban Mahasiswa B beserta nilainya bisa terbaca jika view menampilkan data itu.

Saya kaget karena celahnya sesederhana itu: cuma modal ganti angka di URL, data orang lain langsung kelihatan. Laravel menemukan ID `2`, tetapi controller tidak bertanya apakah submission itu milik akun saya.

#### Kesimpulan

Saya memahami bahwa ini contoh IDOR. Binding otomatis bukan pemeriksaan kepemilikan. Saya perlu membandingkan pemilik submission dengan pengguna yang login atau memakai Policy untuk memutuskan siapa yang boleh melihatnya.

---

### **2. Buka tugas milik mata kuliah lain melalui URL bertingkat**

#### Prediksi

Saya memprediksi tugas `99` tidak akan muncul karena URL menyebut course `1`, sedangkan tugas tersebut milik course `2`.

#### Percobaan

Untuk skenario ini, route lab dibuat tanpa `shallow()` dan tanpa Scope Bindings:

```php
Route::get('/courses/{course}/assignments/{assignment}',
    [AssignmentController::class, 'show']);
```

Method lab menerima `Course $course` dan `Assignment $assignment`, lalu menampilkan tugas tanpa mengecek relasi maupun hak akses. Saya membuka:

```text
http://kampuslms.test/courses/1/assignments/99
```

#### Hasil yang nyata

Tanpa pembatasan relasi, Laravel dapat menemukan course `1` dan assignment `99` secara terpisah. Keduanya ada di database, jadi tugas dari course `2` tetap muncul walaupun induk URL-nya salah.

Saya baru sadar bahwa URL yang terlihat bertingkat belum otomatis membuat pencarian datanya ikut bertingkat.

#### Kesimpulan

Saya perlu memastikan assignment memang bagian dari course di URL. Keberadaan dua data saja belum cukup untuk membuktikan bahwa keduanya berhubungan.

---

### **3. Aktifkan `Route::scopeBindings()`, lalu ulangi percobaan nomor 2**

#### Prediksi

Saya memprediksi Laravel akan menolak assignment `99` karena tugas itu tidak ditemukan dalam course `1`.

#### Percobaan

Saya membandingkan konfigurasi sebelumnya dengan konfigurasi lab berikut:

```php
Route::scopeBindings()->group(function () {
    Route::get('/courses/{course}/assignments/{assignment}',
        [AssignmentController::class, 'show']);
});
```

Method tetap menerima kedua model. Model `Course` juga harus mempunyai relasi `assignments()`:

```php
public function assignments(): HasMany
{
    return $this->hasMany(Assignment::class);
}
```

URL yang dibuka tetap sama:

```text
http://kampuslms.test/courses/1/assignments/99
```

#### Hasil yang nyata

Dengan Scope Bindings, hasil untuk pasangan data contoh itu menjadi `404 Not Found`. Laravel mencari assignment `99` melalui relasi `assignments()` milik course `1`. Karena tugas itu milik course `2`, pencarian tidak menemukan hasil.

Tugasnya sebenarnya ada, tetapi tidak ada di dalam mata kuliah yang saya minta.

#### Kesimpulan

Saya memahami bahwa `scopeBindings()` menjaga kecocokan induk dan anak pada route bertingkat. Namun, course dan assignment yang cocok belum tentu boleh saya akses. Pemeriksaan pengguna tetap perlu ditambahkan.

---

### **4. Coba daftarkan middleware di `app/Http/Kernel.php` mengikuti tutorial lama**

#### Prediksi

Saya memprediksi tinggal membuka `app/Http/Kernel.php`, lalu menambahkan alias `role` seperti contoh tutorial lama.

#### Percobaan

Saya memeriksa lokasi yang disebut tutorial:

```text
app/Http/Kernel.php
```

Lalu saya membandingkannya dengan lokasi konfigurasi proyek ini:

```text
bootstrap/app.php
```

#### Hasil yang nyata

Awalnya saya bingung mencari file `Kernel.php`. Ternyata pada struktur aplikasi Laravel 12 yang dipakai proyek ini, berkas `app/Http/Kernel.php` sudah tidak ada. Jadi langkah tutorial lama tidak bisa langsung saya ikuti.

Alias middleware pada proyek ini didaftarkan melalui `withMiddleware()` di `bootstrap/app.php`:

```php
use Illuminate\Foundation\Configuration\Middleware;

// Bagian dari rangkaian Application::configure(...)
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \App\Http\Middleware\EnsureUserHasRole::class,
    ]);
})
```

Setelah alias tersedia, route bisa memakai `->middleware('role:admin')`. Cara pendaftaran ini sesuai [dokumentasi middleware Laravel 12](https://laravel.com/docs/12.x/middleware#middleware-aliases).

#### Kesimpulan

Saya belajar untuk mengecek versi Laravel sebelum mengikuti tutorial. Untuk struktur proyek ini, alias middleware didaftarkan di `bootstrap/app.php`. Saya juga perlu menyiapkan class middleware-nya; menulis alias saja belum membuat aturan pemeriksaan peran.

---

### **5. Pasang `role:admin` pada grup route, lalu akses sebagai Dosen**

#### Prediksi

Saya memprediksi akun Dosen akan ditolak karena grup route tersebut hanya mengizinkan admin.

#### Percobaan

Konfigurasi grup yang saya pelajari adalah:

```php
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('courses', CourseController::class);
    });
```

Dalam simulasi, saya masuk sebagai Dosen lalu membuka:

```text
http://kampuslms.test/admin/courses
```

Middleware `role` harus memeriksa peran akun dan menjalankan `abort(403)` jika perannya tidak sesuai.

#### Hasil yang nyata

Dengan pemeriksaan itu, akun Dosen mendapat `403 Forbidden`. Akunnya sudah login, tetapi perannya bukan `admin`, sehingga tidak diizinkan masuk ke area tersebut.

Saya melihat fungsi middleware seperti penjaga pintu gerbang. Sebelum urusan data di controller diproses, pengguna harus memenuhi aturan masuk dulu.

#### Kesimpulan

Saya memahami bahwa `auth` memeriksa apakah pengguna sudah login, sedangkan `role:admin` membatasi peran yang boleh masuk. Error `403` berarti akses ditolak, berbeda dari `404` yang berarti data atau route yang diminta tidak ditemukan.

---

### **6. Sebagai Dosen A, buka dan edit mata kuliah milik Dosen B**

#### Prediksi

Saya memprediksi form edit course `2` akan ditolak karena mata kuliah itu milik Dosen B, bukan milik saya sebagai Dosen A.

#### Percobaan

Untuk membedakan peran dan kepemilikan, skenario lab memakai grup dosen berikut, tanpa pengecekan `lecturer_id` di controller:

```php
Route::middleware(['auth', 'role:dosen'])->group(function () {
    Route::resource('courses', CourseController::class);
});
```

Sebagai Dosen A, saya membuka form edit mata kuliah Dosen B:

```text
http://kampuslms.test/courses/2/edit
```

Dalam simulasi, saya mengganti nama mata kuliah lalu menekan Simpan. Form mengirim `PUT/PATCH /courses/2` dengan token CSRF dan data yang lolos validasi.

#### Hasil yang nyata

Jika controller lab hanya memakai binding dan validasi input, form edit tetap bisa dibuka dan perubahan bisa disimpan. Dosen A lolos `role:dosen` karena memang berperan sebagai dosen. Middleware itu tidak membandingkan akun saya dengan `lecturer_id` milik course `2`.

Saya cukup kaget karena memasang middleware ternyata belum cukup aman. Pengguna yang boleh masuk ke area dosen masih bisa menyentuh data dosen lain kalau tidak ada pemeriksaan tambahan.

Pada route terbaru proyek, pengelolaan courses berada di `/admin/courses` dengan `role:admin`. Karena itu, percobaan ini membutuhkan konfigurasi lab di atas; membuka `/admin/courses/2/edit` sebagai dosen akan tertahan oleh middleware admin.

#### Kesimpulan

Saya memahami perbedaannya: middleware menjaga “siapa yang boleh masuk ruangan”, sedangkan Policy memeriksa “siapa yang boleh menyentuh barang tertentu”. Di Minggu 7, saya merencanakan `CoursePolicy::update` untuk mengecek dosen pengampu atau admin yang memang diberi izin.

Pemeriksaan harus berlaku saat membuka form edit dan saat menyimpan perubahan. Menyembunyikan tombol Edit saja tidak cukup karena URL dan request masih bisa dikirim langsung.

---

## Ringkasan

Dari pembelajaran Minggu 5, saya memahami bahwa:

- Route Model Binding otomatis mencarikan model dari parameter URL. Kalau datanya tidak ada, hasilnya `404`.
- Binding tidak otomatis mengecek kepemilikan. Tanpa pemeriksaan akses, mengganti angka di URL bisa membuka data orang lain dan menjadi IDOR.
- `scopeBindings()` memastikan data anak benar-benar milik induk pada route bertingkat, tetapi tidak menentukan siapa yang boleh mengaksesnya.
- Route detail yang memakai `shallow()` tetap membutuhkan pemeriksaan akses karena URL-nya hanya membawa ID anak.
- Middleware peran membatasi siapa yang boleh masuk suatu area. Policy memeriksa izin terhadap data tertentu, misalnya mata kuliah atau submission.
- Pada struktur Laravel 12 proyek ini, alias middleware didaftarkan di `bootstrap/app.php`, bukan `app/Http/Kernel.php` seperti tutorial lama.
- Saya perlu memeriksa izin pada proses baca maupun perubahan data. Sudah login, punya peran yang sesuai, dan input yang valid belum berarti boleh mengubah data milik orang lain.
