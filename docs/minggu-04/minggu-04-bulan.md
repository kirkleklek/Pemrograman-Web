# **Week 4**

**Mata Kuliah: Pemrograman Web**
**Pertemuan: 4**
**Tanggal: 22 September 2026**
**Topik: Form Request, Validasi, Session, CSRF, dan PRG**

---

## **READ**

### **1. Method apa yang menerima request? Di controller mana?**

Request yang berasal dari form penambahan mata kuliah akan diterima oleh method `store()` yang terdapat di dalam `CourseController`.

Hal tersebut dapat diketahui melalui route berikut:

```php
Route::resource('courses', CourseController::class);
```

Karena menggunakan resource controller, maka request `POST /courses` secara otomatis akan diarahkan menuju:

```php
public function store(StoreCourseRequest $request)
```

Dengan demikian, request tersebut diproses oleh controller `App\Http\Controllers\CourseController`, khususnya pada method `store()`.

### **2. Di titik mana persisnya validasi terjadi, sebelum atau sesudah baris pertama method controller?**

Proses validasi dilakukan sebelum baris pertama yang terdapat di dalam method controller dijalankan.

Pada method `store()`, parameter yang digunakan adalah:

```php
StoreCourseRequest $request
```

Karena parameter tersebut menggunakan Form Request, Laravel akan terlebih dahulu membuat instance serta menjalankan proses validasi pada `StoreCourseRequest` sebelum menjalankan isi dari method `store()`.

Artinya, ketika baris berikut dijalankan:

```php
$course = Course::create($request->validated());
```

seluruh proses validasi berdasarkan rules pada `StoreCourseRequest` sudah dilakukan sebelumnya.

Apabila proses validasi gagal, Laravel tidak akan melanjutkan eksekusi ke dalam method `store()`.

### **3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?**

Ketika proses validasi gagal, Laravel akan melakukan redirect kembali menuju halaman sebelumnya, yaitu halaman tempat form tersebut dikirimkan.

Dalam kasus form penambahan mata kuliah, halaman yang biasanya menjadi tujuan redirect adalah:

```text
/courses/create
```

Tujuan tersebut ditentukan secara otomatis oleh Laravel berdasarkan request sebelumnya, terutama informasi yang berasal dari header `Referer`.

Karena validasi menggunakan Form Request, Laravel juga secara otomatis membawa pesan error serta input sebelumnya melalui session.

Dengan demikian, redirect tersebut bukan ditentukan secara manual melalui `CourseController`, melainkan ditangani oleh mekanisme validasi bawaan Laravel.

### **4. Dari mana `@error('sks')` mengambil pesannya?**

Directive `@error('sks')` mengambil pesan dari error bag yang disimpan oleh Laravel di dalam session ketika proses validasi gagal.

Pesan error untuk field `sks` berasal dari bagian message di dalam `StoreCourseRequest`, misalnya:

```php
'sks.required' => 'SKS wajib diisi.',
'sks.integer' => 'SKS harus berupa angka.',
'sks.between' => 'SKS harus antara 1 sampai 6.',
```

Saat validasi tidak berhasil, Laravel akan menyimpan informasi error tersebut ke dalam session dengan key `errors`.

Ketika halaman form ditampilkan kembali, directive Blade `@error('sks')` akan membaca error tersebut dan menampilkannya melalui variabel `$message`.

### **5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?**

Fungsi `old('sks')` memperoleh nilai dari input sebelumnya yang disimpan sementara atau di-flash oleh Laravel ke dalam session setelah validasi gagal.

Input lama tersebut disimpan menggunakan key `_old_input`.

Pada form mata kuliah, penggunaannya terlihat pada bagian:

```php
value="{{ old('sks', $course->sks ?? '') }}"
```

Nilai tersebut hanya tersedia selama satu request berikutnya karena termasuk ke dalam flash data.

Setelah request berikutnya selesai diproses, input lama tersebut akan otomatis dihapus dari session.

### **6. Buka DevTools -> Application -> Cookies. Temukan cookie session Laravel. Catat namanya.**

Nama cookie session Laravel yang digunakan pada project ini adalah:

```text
laravel-session
```

Nama tersebut berasal dari konfigurasi yang terdapat pada `config/session.php`:

```php
'cookie' => env(
    'SESSION_COOKIE',
    Str::slug((string) env('APP_NAME', 'laravel')).'-session'
),
```

Sementara itu, pada file `.env`, nilai `APP_NAME` adalah:

```text
APP_NAME=Laravel
```

Oleh karena itu, nama cookie session yang dihasilkan menjadi `laravel-session`.

---

## **BREAK**

### **1. Hapus `@csrf` dari form, lalu kirim**

#### **Prediksi**

Saya memperkirakan request `POST` akan ditolak oleh Laravel karena form tersebut tidak lagi menyertakan CSRF token.

#### **Hasil yang nyata**

Laravel menampilkan pesan error `419 Page Expired`.

Hal ini disebabkan middleware CSRF Laravel melakukan pemeriksaan terhadap request dengan method `POST`, `PUT`, `PATCH`, dan `DELETE`.

Apabila token CSRF tidak tersedia atau token yang dikirim tidak sesuai, request tersebut akan langsung ditolak.

#### **Kesimpulan**

Directive `@csrf` digunakan sebagai perlindungan terhadap serangan CSRF, yaitu kondisi ketika website lain mencoba mengirim request dengan memanfaatkan identitas user tanpa izin.

Tanpa adanya CSRF token, Laravel tidak dapat memastikan bahwa request yang masuk memang benar berasal dari form aplikasi yang sah.

---

### **2. Ganti `$request->validated()` menjadi `$request->all()`, lalu kirim field liar lewat `curl`**

#### **Prediksi**

Saya memperkirakan seluruh input yang dikirimkan melalui request akan ikut diambil, termasuk field tambahan yang sebenarnya tidak seharusnya digunakan.

Apabila model memiliki konfigurasi `$fillable` yang terlalu bebas atau menggunakan `protected $guarded = [];`, maka risiko mass assignment dapat kembali muncul.

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

Ketika menggunakan `$request->all()`, controller akan mengambil seluruh data yang terdapat pada request, tidak hanya data yang sudah melalui proses validasi.

Pada project ini, model `Course` masih menggunakan `$fillable` untuk membatasi atribut yang dapat diisi. Oleh sebab itu, field yang benar-benar tidak terdaftar tetap tidak dapat langsung dimasukkan apabila tidak termasuk dalam `$fillable`.

Meskipun demikian, penggunaan `all()` tetap memiliki risiko karena data seperti `lecturer_id` dan `status` dapat ikut diproses tanpa jaminan telah memenuhi aturan yang ditentukan apabila validasinya juga dilemahkan.

#### **Kesimpulan**

Penggunaan `$request->validated()` lebih aman karena hanya mengembalikan data yang sudah berhasil melewati rules validasi.

Sebaliknya, `$request->all()` mengambil seluruh input mentah dari user sehingga batas antara data yang sudah tervalidasi dan data mentah menjadi tidak jelas.

---

### **3. Hapus validasi `exists:users,id` pada `lecturer_id`, kirim `lecturer_id=99999`**

#### **Prediksi**

Saya memperkirakan request dengan nilai `lecturer_id=99999` dapat lolos dari proses validasi aplikasi karena Laravel tidak lagi melakukan pengecekan apakah ID user tersebut benar-benar tersedia.

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

Apabila database tidak memiliki foreign key yang dapat mencegah kondisi tersebut, data mata kuliah dengan `lecturer_id=99999` dapat tersimpan dan menghasilkan data yatim.

Data yatim merupakan kondisi ketika suatu data mata kuliah mengacu pada dosen yang sebenarnya tidak tersedia pada tabel `users`.

#### **Kesimpulan**

Validasi `exists:users,id` memiliki peran penting dalam menjaga hubungan antar-data pada tingkat aplikasi.

Aturan tersebut memastikan bahwa nilai `lecturer_id` yang dikirimkan benar-benar mengacu pada user yang tersedia di dalam database.

---

### **4. Hapus validasi `in:...` pada `status`, kirim `status=superadmin`**

#### **Prediksi**

Saya memperkirakan nilai `status=superadmin` dapat melewati validasi karena aplikasi tidak lagi membatasi nilai status yang dapat digunakan.

#### **Hasil yang nyata**

Data berpotensi tersimpan menggunakan nilai status yang tidak sesuai dengan aturan bisnis, seperti:

```text
superadmin
```

Padahal nilai status mata kuliah yang seharusnya diperbolehkan hanya:

```text
draft, active, archived
```

#### **Kesimpulan**

Validasi `in:draft,active,archived` berfungsi untuk membatasi nilai `status` agar hanya menggunakan pilihan yang telah ditentukan.

Jika aturan validasi tersebut dihapus, maka pembatasan terhadap enum atau pilihan status dapat dilewati sehingga nilai yang tidak sesuai dapat masuk.

---

### **5. Hapus `->withQueryString()`, lakukan pencarian lalu klik halaman 2**

#### **Prediksi**

Saya memperkirakan filter pencarian yang sedang digunakan akan hilang ketika user berpindah menuju halaman berikutnya.

#### **Hasil yang nyata**

Saat melakukan pencarian menggunakan query tertentu kemudian berpindah ke halaman 2, URL pagination tidak lagi menyertakan parameter seperti:

```text
?q=...&status=...
```

Akibatnya, halaman kedua kembali menampilkan seluruh data, bukan hanya hasil yang sesuai dengan pencarian atau filter sebelumnya.

#### **Kesimpulan**

Method `->withQueryString()` diperlukan agar parameter query dari pencarian maupun filter yang sedang digunakan tetap dibawa ketika berpindah halaman.

Tanpa menggunakan method tersebut, filter akan hilang saat user menggunakan pagination.

---

### **6. Ganti `return redirect()` menjadi `return view()` pada `store`, lalu tekan F5 setelah simpan**

#### **Prediksi**

Saya memperkirakan data dapat tersimpan lebih dari satu kali apabila halaman di-refresh setelah proses submit dilakukan.

#### **Hasil yang nyata**

Setelah data selesai disimpan dan response langsung dikembalikan menggunakan `return view()`, browser masih berada pada response yang berasal dari request `POST`.

Apabila user menekan tombol F5, browser akan mencoba mengirim ulang request `POST` tersebut.

Akibatnya, data yang sama berpotensi tersimpan kembali atau browser akan menampilkan peringatan untuk melakukan resubmit terhadap form.

#### **Kesimpulan**

Kondisi tersebut menjadi salah satu alasan digunakannya pola PRG atau **Post-Redirect-Get**.

Setelah proses penyimpanan menggunakan request `POST`, aplikasi sebaiknya melakukan redirect menuju halaman yang menggunakan request `GET`, misalnya halaman detail mata kuliah.

Dengan cara tersebut, ketika halaman di-refresh, proses penyimpanan tidak akan dijalankan kembali.

---

### **7. Hapus `old(...)` dari semua input, lalu kirim form dengan satu kesalahan**

#### **Prediksi**

Saya memperkirakan seluruh isian form akan kembali kosong apabila proses validasi gagal.

#### **Hasil yang nyata**

Ketika salah satu field memiliki nilai yang tidak valid, misalnya `sks` diisi dengan angka di luar batas yang diperbolehkan, Laravel tetap dapat menampilkan pesan error.

Namun, apabila `old(...)` dihapus, nilai pada field lain yang sebelumnya telah diisi dengan benar tidak akan ditampilkan kembali.

Akibatnya, user harus mengisi ulang data yang sebenarnya sebelumnya sudah benar.

#### **Kesimpulan**

Penggunaan `old(...)` sangat penting untuk meningkatkan kenyamanan user ketika mengisi form.

Tanpa adanya `old(...)`, kegagalan validasi membuat user kehilangan data yang sebelumnya sudah dimasukkan sehingga harus mengisi form tersebut kembali.

---

## **Ringkasan**

Dari pembelajaran pada minggu ini, saya memperoleh beberapa pemahaman, yaitu:

- Form Request menyebabkan proses validasi dilakukan sebelum isi method controller dijalankan.
- `@error(...)` memperoleh pesan dari error validasi yang tersimpan di dalam session.
- `old(...)` mengambil data input sebelumnya dari flash session dan hanya tersedia selama satu request berikutnya.
- `@csrf` digunakan untuk mencegah request tidak sah yang berasal dari luar aplikasi.
- `$request->validated()` lebih aman digunakan dibandingkan `$request->all()` ketika menyimpan data.
- Validasi seperti `exists` dan `in` membantu menjaga konsistensi serta kesesuaian data.
- `withQueryString()` mempertahankan parameter pencarian atau filter ketika user berpindah halaman melalui pagination.
- Pola PRG membantu mencegah terjadinya penyimpanan data ganda ketika halaman di-refresh setelah proses submit.

Dengan demikian, validasi, session, CSRF, dan mekanisme redirect bukan sekadar fitur tambahan dalam Laravel. Seluruh mekanisme tersebut memiliki peran penting dalam menjaga keamanan aplikasi, konsistensi data, serta memberikan pengalaman penggunaan yang lebih baik.
