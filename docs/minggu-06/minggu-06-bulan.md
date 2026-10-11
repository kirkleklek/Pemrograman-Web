# Week 6

**Mata Kuliah: Pemrograman Web**  
**Pertemuan: 6**  
**Topik: REST API, API Resource, Autentikasi Sanctum, dan Rate Limiting**

---

Catatan: Catatan ini merangkum laporan Eagan dan daftar route proyek saat ini. Instalasi awal serta BREAK masih berupa skenario lab, bukan hasil pengujian HTTP langsung. Percobaan nomor 1 tetap perlu diperagakan untuk memenuhi ketentuan pengamatan nyata.

## READ

### **1. Jalankan `php artisan install:api`**

Pada instalasi baru Laravel 12, route API bersifat opsional. Perintah berikut memasang Sanctum, membuat `routes/api.php`, dan mendaftarkan route API:

```bash
php artisan install:api
```

Perubahan pada `withRouting()` di `bootstrap/app.php` adalah:

```php
api: __DIR__.'/../routes/api.php',
```

API proyek sudah terpasang sehingga tidak diinstal ulang. Penyimpanan token menggunakan tabel `personal_access_tokens` dari migration Sanctum.

### **2. Endpoint sederhana `GET /api/v1/courses`**

Bentuk ringkas route yang sudah tersedia di `routes/api.php`:

```php
use App\Http\Controllers\Api\CourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
});
```

Laravel menambahkan prefix `/api` otomatis. Controller mengambil mata kuliah sesuai akses pengguna, memuat relasi dosen dengan `with('lecturer')`, lalu mengembalikan `CourseResource` dalam JSON. Query menentukan data yang boleh diambil, sedangkan Resource memilih kolom respons.

### **3. Persamaan dan perbedaan controller web dengan API**

Keduanya memakai model `Course` dan Eloquent untuk membaca database yang sama. Validasi dan pemeriksaan akses tetap diperlukan pada kedua jalur.

| Bagian            | Web                         | API dengan Bearer token           |
| ----------------- | --------------------------- | --------------------------------- |
| Respons           | HTML melalui Blade          | JSON melalui Resource             |
| Autentikasi       | Session dan cookie          | Token pada header `Authorization` |
| Setelah menyimpan | Redirect                    | Respons JSON dan status HTTP      |
| Validasi gagal    | Redirect dengan pesan error | JSON `422` ketika meminta JSON    |
| CSRF              | Token pada form web         | Tidak memakai CSRF session        |

Setiap request Bearer token membawa identitasnya sendiri. Sanctum juga mendukung mode SPA berbasis cookie dan CSRF, sehingga perbandingan ini khusus untuk mode token.

### **4. Tanpa dan dengan `Accept: application/json`**

Contoh perbandingan memakai input login yang tidak valid:

```powershell
curl.exe -i -X POST http://kampuslms.test/api/v1/auth/login -d 'email=bukan-email' -d 'password=salah'
curl.exe -i -X POST http://kampuslms.test/api/v1/auth/login -H 'Accept: application/json' -d 'email=bukan-email' -d 'password=salah'
```

Pada penanganan validasi bawaan, tanpa header tersebut Laravel dapat mengembalikan redirect `302`, lalu HTML jika redirect diikuti. Dengan header JSON, respons yang diharapkan adalah `422` berisi `message` dan `errors`. Respons berhasil yang memang dibuat sebagai JSON tetap dapat berupa JSON tanpa header Accept.

### **5. Daftar route API dan kontraknya**

```bash
php artisan route:list --path=api
```

Perintah berhasil menampilkan 15 route:

| Method    | URI                                            | Aksi                       |
| --------- | ---------------------------------------------- | -------------------------- |
| POST      | `/api/v1/auth/login`                           | Login                      |
| POST      | `/api/v1/auth/logout`                          | Logout                     |
| GET       | `/api/v1/me`                                   | Profil pengguna            |
| GET       | `/api/v1/courses`                              | Daftar mata kuliah         |
| GET       | `/api/v1/courses/{course}`                     | Detail mata kuliah         |
| GET       | `/api/v1/courses/{course}/materials`           | Daftar materi              |
| GET       | `/api/v1/courses/{course}/assignments`         | Daftar tugas               |
| POST      | `/api/v1/assignments`                          | Membuat tugas              |
| PUT/PATCH | `/api/v1/assignments/{assignment}`             | Mengubah tugas             |
| DELETE    | `/api/v1/assignments/{assignment}`             | Menghapus tugas            |
| GET       | `/api/v1/assignments/{assignment}/submissions` | Daftar submission          |
| POST      | `/api/v1/assignments/{assignment}/submissions` | Mengumpulkan tugas         |
| PUT       | `/api/v1/submissions/{submission}/grade`       | Memberi nilai              |
| GET       | `/api/v1/notifications`                        | Daftar notifikasi          |
| POST      | `/api/v1/notifications/{notification}/read`    | Menandai notifikasi dibaca |

Route GET juga menerima HEAD. Login memakai `throttle:5,1`; route lainnya memakai `auth:sanctum` dan `throttle:60,1`. Method dan URI sesuai daftar pada catatan Eagan. Pencocokan dengan dokumen spesifikasi terpisah belum dilakukan karena dokumennya belum ditemukan.

---

## BREAK

Hasil berikut menjelaskan perilaku yang diharapkan pada perubahan lab. Pengaman aplikasi tidak dihapus untuk menyusun catatan ini.

### **1. Mengembalikan model User langsung dan menghapus `$hidden`**

#### Prediksi

Model mentah akan menampilkan kolom pengguna yang tidak disembunyikan, termasuk email.

#### Percobaan

Pada salinan lab, buat endpoint uji dengan `response()->json(User::all())`. Lihat JSON sebelum dan sesudah menghapus sementara `$hidden` dari model `User`, lalu pulihkan pengamannya.

#### Hasil

Dengan `$hidden`, email dan atribut lain yang tidak disembunyikan ikut tampil. Tanpanya, hash password dan `remember_token` juga dapat muncul. Pengamatan ini belum diperagakan langsung, sehingga belum memenuhi kewajiban melihat kebocoran nyata.

#### Kesimpulan

Model mentah dapat membocorkan data berlebihan. Resource harus memilih kolom yang diperlukan; hash password tetap sensitif meskipun bukan password asli.

---

### **2. Menghapus `auth:sanctum` dan mengakses tanpa token**

#### Prediksi

Endpoint yang langsung mengambil data akan terbuka tanpa autentikasi.

#### Percobaan

Pada salinan lab, lepaskan `auth:sanctum` dari grup endpoint uji yang mengembalikan daftar mata kuliah, lalu panggil tanpa token.

#### Hasil

Endpoint sederhana tersebut dapat mengembalikan `200` dan data menjadi publik. Pada controller proyek yang membaca `$request->user()->role`, melepas middleware saja justru dapat menimbulkan error karena pengguna bernilai `null`.

#### Kesimpulan

Resource membatasi isi JSON, tetapi tidak menggantikan autentikasi dan pemeriksaan akses.

---

### **3. Memakai token yang sudah dihapus**

#### Prediksi

Token yang dicabut tidak bisa digunakan kembali.

#### Percobaan

Login, gunakan token untuk logout, lalu panggil `GET /api/v1/me` memakai token yang sama dan header Accept JSON.

#### Hasil

Logout menghapus token aktif dan mengembalikan `204`. Request berikutnya seharusnya menerima `401 Unauthorized` karena token sudah tidak valid.

#### Kesimpulan

Salinan token di klien tidak berlaku setelah dicabut. Logout ini hanya menghapus token aktif, bukan seluruh token akun.

---

### **4. Mahasiswa membuat assignment**

#### Prediksi

Identitas mahasiswa dikenali, tetapi tidak diizinkan membuat tugas.

#### Percobaan

Gunakan token mahasiswa yang valid untuk `POST /api/v1/assignments`, dengan input tugas yang valid dan header Accept JSON.

#### Hasil

Pemeriksaan peran seharusnya menolak request dengan `403 Forbidden`, bukan `401`.

#### Kesimpulan

`401` menunjukkan autentikasi gagal. `403` menunjukkan pengguna sudah dikenali, tetapi tidak memiliki izin.

---

### **5. Menghapus eager loading dan mengamati N+1**

#### Prediksi

Akses relasi dosen tanpa eager loading dapat menambah query untuk setiap mata kuliah.

#### Percobaan

Pada salinan lab, hapus `with('lecturer')`, akses `$this->lecturer` langsung dalam Resource, lalu panggil daftar mata kuliah. Bandingkan query melalui Telescope, Debugbar, atau query log.

#### Hasil

Untuk 15 mata kuliah, polanya dapat menjadi satu query daftar ditambah sampai 15 query dosen, di luar query pagination. Ini contoh pola, bukan jumlah yang sudah diukur. Jika Resource tetap memakai `whenLoaded('lecturer')`, relasi yang belum dimuat dihilangkan dari JSON dan tidak diambil otomatis.

#### Kesimpulan

Eager loading mencegah query relasi berulang. `whenLoaded()` hanya menampilkan relasi yang sudah dimuat.

---

### **6. Menghapus throttle dan mencoba login 50 kali**

#### Prediksi

Tanpa pembatas, percobaan login berulang terus diproses.

#### Percobaan

Pada salinan lab, hapus throttle login dan kirim 50 request lokal:

```powershell
1..50 | ForEach-Object {
    curl.exe -i -X POST http://kampuslms.test/api/v1/auth/login -H 'Accept: application/json' -d 'email=mahasiswa@kampuslms.test' -d 'password=salah'
}
```

#### Hasil

Tanpa pembatas lain, seluruh percobaan dapat diproses; password salah tetap menghasilkan `422`. Dengan `throttle:5,1` dan batas awal yang kosong, request keenam dalam jendela satu menit seharusnya mendapat `429 Too Many Requests`.

#### Kesimpulan

Throttle membatasi percobaan brute force. Request yang diproses bukan berarti login berhasil.

---

### **7. Membedakan pesan email salah dan password salah**

#### Prediksi

Pesan berbeda dapat mengungkap apakah suatu email terdaftar.

#### Percobaan

Pada salinan lab, bedakan pesan untuk email yang tidak ditemukan dan password yang salah. Bandingkan login dengan email tidak terdaftar serta email demo terdaftar memakai password salah.

#### Hasil

Pesan “Password salah” memberi petunjuk bahwa email terdaftar, sedangkan “Email tidak terdaftar” menunjukkan sebaliknya. Pada proyek, kedua kondisi memakai pesan “Email atau kata sandi salah.” dengan status `422`.

#### Kesimpulan

Perbedaan pesan memudahkan user enumeration dan penargetan akun. Pesan umum serta throttle mengurangi risiko, walaupun petunjuk lain seperti waktu respons tetap perlu diperhatikan.
