# **Week 4**

**Mata Kuliah: Pemrograman Web**  
**Pertemuan: 4**  
**Tanggal: 22 September 2026**  
**Topik: Form Request, Validasi, Session, CSRF, dan PRG**

---

## **READ**

### **1. Method apa yang menerima request? Di controller mana?**

Request dari form tambah mata kuliah diterima oleh method `store()` di `CourseController`.

Hal ini terlihat dari route:

```php
Route::resource('courses', CourseController::class);
```

Dengan resource controller, request `POST /courses` otomatis diarahkan ke:

```php
public function store(StoreCourseRequest $request)
```

Jadi, controller yang menerima request adalah `App\Http\Controllers\CourseController`, tepatnya method `store()`.

### **2. Di titik mana persisnya validasi terjadi, sebelum atau sesudah baris pertama method controller?**

Validasi terjadi sebelum baris pertama di dalam method controller dijalankan.

Pada method `store()`, parameter yang diterima adalah:

```php
StoreCourseRequest $request
```

Karena menggunakan Form Request, Laravel akan membuat dan memvalidasi `StoreCourseRequest` terlebih dahulu sebelum masuk ke body method `store()`.

Artinya, sebelum baris ini dijalankan:

```php
$course = Course::create($request->validated());
```

Laravel sudah menjalankan validasi berdasarkan rules yang ada di `StoreCourseRequest`.

Jika validasi gagal, method `store()` tidak dijalankan sama sekali.

### **3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?**

Jika validasi gagal, Laravel akan me-redirect kembali ke halaman sebelumnya, yaitu halaman asal form dikirim.

Pada kasus tambah mata kuliah, biasanya kembali ke halaman:

```text
/courses/create
```

Tujuannya ditentukan secara otomatis oleh Laravel berdasarkan request sebelumnya, terutama dari header `Referer`. Karena validasi dilakukan oleh Form Request, Laravel juga otomatis membawa error validasi dan input lama ke session.

Jadi, tujuan redirect tidak ditulis manual di `CourseController`, tetapi ditangani oleh mekanisme validasi bawaan Laravel.

### **4. Dari mana `@error('sks')` mengambil pesannya?**

`@error('sks')` mengambil pesan dari error bag yang disimpan Laravel di session setelah validasi gagal.

Pesan error untuk field `sks` berasal dari `StoreCourseRequest`, khususnya bagian:

```php
'sks.required' => 'SKS wajib diisi.',
'sks.integer' => 'SKS harus berupa angka.',
'sks.between' => 'SKS harus antara 1 sampai 6.',
```

Ketika validasi gagal, Laravel menyimpan pesan tersebut ke session dengan key `errors`. Setelah halaman form dibuka kembali, Blade directive `@error('sks')` membaca pesan dari sana dan menampilkannya melalui variabel `$message`.

### **5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?**

`old('sks')` mengambil nilai dari input lama yang di-flash Laravel ke session saat validasi gagal.

Data tersebut disimpan sementara di session dengan key `_old_input`. Pada form mata kuliah, nilainya digunakan di bagian:

```php
value="{{ old('sks', $course->sks ?? '') }}"
```

Nilai itu hanya bertahan untuk satu request berikutnya karena termasuk flash data. Setelah halaman berikutnya selesai diproses, data lama tersebut akan hilang otomatis dari session.

### **6. Buka DevTools -> Application -> Cookies. Temukan cookie session Laravel. Catat namanya.**

Nama cookie session Laravel pada project ini adalah:

```text
laravel-session
```

Nama ini berasal dari konfigurasi `config/session.php`:

```php
'cookie' => env(
    'SESSION_COOKIE',
    Str::slug((string) env('APP_NAME', 'laravel')).'-session'
),
```

Karena di file `.env` nilai `APP_NAME` adalah:

```text
APP_NAME=Laravel
```

maka nama cookie session menjadi `laravel-session`.

---

## **BREAK**

### **1. Hapus `@csrf` dari form, lalu kirim**

#### **Prediksi**

Saya memprediksi request `POST` akan ditolak oleh Laravel karena form tidak lagi mengirim CSRF token.

#### **Hasil yang nyata**

Laravel menampilkan error `419 Page Expired`.

Hal ini terjadi karena middleware CSRF Laravel memeriksa setiap request `POST`, `PUT`, `PATCH`, dan `DELETE`. Jika token tidak ada atau tidak cocok, request langsung ditolak.

#### **Kesimpulan**

`@csrf` berfungsi untuk mencegah CSRF attack, yaitu kondisi ketika situs lain mencoba mengirim request atas nama user tanpa izin. Tanpa token ini, Laravel tidak bisa memastikan bahwa request benar-benar berasal dari form aplikasi sendiri.

---

### **2. Ganti `$request->validated()` menjadi `$request->all()`, lalu kirim field liar lewat `curl`**

#### **Prediksi**

Saya memprediksi semua input dari request akan ikut diambil, termasuk field liar yang tidak seharusnya dipakai.

Jika model memiliki `$fillable` yang terlalu terbuka atau memakai `protected $guarded = [];`, maka mass assignment bisa kembali berbahaya.

#### **Percobaan lewat `curl`**

```bash
curl -X POST http://kampuslms.test/courses \
  -H "X-CSRF-TOKEN: <ambil dari halaman>" \
  -b cookies.txt \
  -d "code=XX01" \
  -d "name=Uji" \
  -d "sks=3" \
  -d "lecturer_id=99999" \
  -d "status=superadmin"
```

#### **Hasil yang nyata**

Saat memakai `$request->all()`, controller mengambil seluruh isi request, bukan hanya data yang sudah lolos validasi.

Pada project ini, model `Course` masih membatasi atribut dengan `$fillable`, sehingga field yang benar-benar liar tetap tidak otomatis masuk jika tidak ada di `$fillable`. Namun, mengganti `validated()` menjadi `all()` tetap berbahaya karena data seperti `lecturer_id` dan `status` bisa diproses tanpa jaminan sudah valid jika validasinya ikut dilemahkan.

#### **Kesimpulan**

`$request->validated()` lebih aman karena hanya mengembalikan field yang sudah melewati rules validasi. Jika diganti menjadi `$request->all()`, batas antara data valid dan data mentah dari user menjadi hilang.

---

### **3. Hapus validasi `exists:users,id` pada `lecturer_id`, kirim `lecturer_id=99999`**

#### **Prediksi**

Saya memprediksi request dengan `lecturer_id=99999` akan lolos dari validasi aplikasi karena Laravel tidak lagi mengecek apakah user tersebut benar-benar ada.

#### **Percobaan lewat `curl`**

```bash
curl -X POST http://kampuslms.test/courses \
  -H "X-CSRF-TOKEN: <ambil dari halaman>" \
  -b cookies.txt \
  -d "code=XX01" \
  -d "name=Uji" \
  -d "sks=3" \
  -d "lecturer_id=99999" \
  -d "status=active"
```

#### **Hasil yang nyata**

Jika database tidak memiliki foreign key yang mencegahnya, data mata kuliah dengan `lecturer_id=99999` bisa masuk dan menjadi data yatim.

Data yatim berarti data mata kuliah menunjuk ke dosen yang sebenarnya tidak ada di tabel `users`.

#### **Kesimpulan**

Validasi `exists:users,id` penting untuk menjaga relasi data dari sisi aplikasi. Validasi ini memastikan `lecturer_id` yang dikirim benar-benar mengarah ke user yang ada.

---

### **4. Hapus validasi `in:...` pada `status`, kirim `status=superadmin`**

#### **Prediksi**

Saya memprediksi nilai `status=superadmin` akan lolos karena aplikasi tidak lagi membatasi pilihan status.

#### **Hasil yang nyata**

Data dapat tersimpan dengan status yang tidak sesuai aturan bisnis, misalnya:

```text
superadmin
```

Padahal status mata kuliah yang benar hanya:

```text
draft, active, archived
```

#### **Kesimpulan**

Validasi `in:draft,active,archived` berfungsi sebagai pagar agar nilai status hanya berada dalam pilihan yang diizinkan. Jika validasi ini dihapus, enum atau pilihan status menjadi jebol.

---

### **5. Hapus `->withQueryString()`, lakukan pencarian lalu klik halaman 2**

#### **Prediksi**

Saya memprediksi filter pencarian akan hilang saat pindah ke halaman berikutnya.

#### **Hasil yang nyata**

Ketika mencari mata kuliah dengan query tertentu lalu klik halaman 2, URL pagination tidak lagi membawa parameter seperti:

```text
?q=...&status=...
```

Akibatnya, halaman 2 menampilkan semua data, bukan hasil pencarian yang sedang difilter.

#### **Kesimpulan**

`->withQueryString()` penting agar pagination tetap membawa query string dari pencarian atau filter yang sedang aktif. Tanpa ini, filter hilang saat user berpindah halaman.

---

### **6. Ganti `return redirect()` menjadi `return view()` pada `store`, lalu tekan F5 setelah simpan**

#### **Prediksi**

Saya memprediksi data bisa tersimpan dua kali saat halaman di-refresh setelah submit.

#### **Hasil yang nyata**

Setelah data berhasil disimpan dan response langsung memakai `return view()`, browser tetap berada pada response hasil request `POST`.

Ketika user menekan F5, browser akan mencoba mengirim ulang request `POST` yang sama. Akibatnya, data yang sama bisa tersimpan lagi atau browser menampilkan peringatan resubmit form.

#### **Kesimpulan**

Ini alasan pola PRG atau Post-Redirect-Get digunakan. Setelah menyimpan data lewat `POST`, aplikasi sebaiknya melakukan redirect ke halaman `GET`, misalnya detail mata kuliah. Dengan begitu, refresh halaman tidak mengulang proses simpan data.

---

### **7. Hapus `old(...)` dari semua input, lalu kirim form dengan satu kesalahan**

#### **Prediksi**

Saya memprediksi form akan kembali kosong setelah validasi gagal.

#### **Hasil yang nyata**

Saat satu field salah, misalnya `sks` diisi di luar rentang valid, Laravel memang menampilkan pesan error. Namun, field lain yang sebelumnya sudah diisi tidak muncul kembali jika `old(...)` dihapus.

User harus mengetik ulang data yang sebenarnya sudah benar.

#### **Kesimpulan**

`old(...)` sangat penting untuk pengalaman pengguna. Tanpa `old(...)`, validasi gagal terasa menyebalkan karena user kehilangan input yang sudah diisi.

---

## **Ringkasan**

Dari pembelajaran minggu ini, saya memahami bahwa:

- Form Request membuat validasi terjadi sebelum isi method controller dijalankan.
- `@error(...)` mengambil pesan dari error validasi yang disimpan di session.
- `old(...)` mengambil input lama dari flash session dan hanya bertahan satu request.
- `@csrf` mencegah request palsu dari luar aplikasi.
- `$request->validated()` lebih aman daripada `$request->all()` untuk menyimpan data.
- Validasi seperti `exists` dan `in` menjaga data tetap konsisten.
- `withQueryString()` menjaga filter tetap aktif saat pagination.
- Pola PRG mencegah data ganda saat user me-refresh halaman setelah submit.

Jadi, validasi, session, CSRF, dan redirect bukan hanya fitur tambahan Laravel, tetapi bagian penting untuk menjaga keamanan, konsistensi data, dan kenyamanan pengguna.
