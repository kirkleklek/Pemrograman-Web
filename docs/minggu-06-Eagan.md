# Week 6

**Mata Kuliah: Pemrograman Web**  
**Pertemuan: 6**  
**Tanggal: 6 Oktober 2026**  
**Topik: REST API, API Resource, Autentikasi Sanctum, dan Rate Limiting**

---

Catatan: Saya menyusun laporan ini dari pembacaan kode proyek, hasil `route:list`, dan skenario eksplorasi modul. Bagian instalasi awal dan percobaan BREAK ditulis sebagai simulasi lab; respons HTTP dan jumlah query di sana belum diverifikasi lewat pengujian langsung. Pengaman pada kode aplikasi tidak saya hapus untuk membuat laporan ini.

## READ

### **1. Jalankan `php artisan install:api`**

Pada skenario awal modul, saya mencari-cari file `routes/api.php`, kok tidak ada. Saya sempat mengira folder proyek saya kurang lengkap. Ternyata pada instalasi baru Laravel 12, route API memang tidak tersedia secara default karena fitur API bersifat pilihan.

Perintah untuk mengaktifkannya adalah:

```bash
php artisan install:api
```

Perintah ini memasang Sanctum, membuat `routes/api.php`, dan mendaftarkan file route API di `bootstrap/app.php`. Untuk menyimpan token, database juga perlu mempunyai tabel `personal_access_tokens` melalui migration yang tersedia.

Pada proyek yang saya baca sekarang, API sudah terpasang. Saya tidak menjalankan instalasi ulang. Bagian `withRouting()` sudah memuat:

```php
api: __DIR__.'/../routes/api.php',
```

Saya memahami Sanctum sebagai pemeriksa tanda pengenal untuk request API. Pada mode Bearer token yang dipakai di lab ini, token dikirim lewat header `Authorization`. Instalasi dan cara penggunaan token dijelaskan di [dokumentasi Sanctum Laravel 12](https://laravel.com/docs/12.x/sanctum).

### **2. Buat endpoint sederhana `GET /api/v1/courses`**

Saya mempelajari route daftar mata kuliah di `routes/api.php`. Bentuk sederhananya seperti ini:

```php
use App\Http\Controllers\Api\CourseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/courses', [CourseController::class, 'index']);
});
```

Prefix `/api` sudah diberikan Laravel dari pendaftaran file API. Jadi saya cukup menulis `v1`, bukan `api/v1` lagi.

Contoh controller pembelajaran berikut mengambil mata kuliah sesuai pengguna, memuat dosen, lalu mengembalikan Resource:

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $courses = Course::query()
            ->with('lecturer')
            ->when($user->role === 'dosen',
                fn ($query) => $query->where('lecturer_id', $user->id))
            ->when($user->role === 'mahasiswa',
                fn ($query) => $query->whereHas('students',
                    fn ($students) => $students->where('users.id', $user->id)))
            ->paginate(15);

        return CourseResource::collection($courses);
    }
}
```

Kode itu versi ringkas untuk menjelaskan alurnya. Controller proyek juga memakai `withCount()`, pengurutan kode mata kuliah, dan metadata pagination sendiri. Saya mulai paham bahwa API Resource memilih isi respons, sedangkan query di controller menentukan data mana yang boleh diambil.

### **3. Bandingkan Controller Web vs Controller API**

Saya melihat keduanya sama-sama memakai Eloquent Model untuk mengambil data dari database. Model `Course` tetap dipakai, jadi saya tidak perlu membuat tabel khusus hanya karena responsnya berubah menjadi JSON.

| Bagian | Controller Web | Controller API pada lab Bearer token |
| --- | --- | --- |
| Respons | View HTML yang dibuat dengan Blade | JSON, biasanya dibentuk lewat API Resource |
| Identitas pengguna | Umumnya session dan cookie browser | Token pada header setiap request |
| Setelah simpan | Redirect ke halaman GET melalui pola PRG | JSON dan status HTTP, misalnya `201` |
| Kesalahan validasi | Redirect kembali dengan error dan input lama | JSON dengan status `422` jika meminta JSON |
| CSRF | Form web memakai token CSRF | Request Bearer token ini tidak memakai CSRF session |

Saya memahami stateless sebagai server tidak mengandalkan sesi browser sebelumnya untuk mengenali request token ini. Setiap request membawa tanda pengenalnya sendiri. Server tetap boleh menyimpan data dan token di database; stateless bukan berarti server tidak menyimpan apa pun.

Saya juga mencatat bahwa Sanctum punya mode SPA yang memakai cookie, session, dan CSRF. Jadi penjelasan “API tidak memakai session atau CSRF” dalam laporan ini khusus untuk mode Bearer token, bukan semua cara memakai Sanctum.

### **4. Panggil API tanpa vs dengan `Accept: application/json`**

Saya memakai endpoint login untuk membandingkan kegagalan validasi. Contoh perintah berikut ditujukan untuk PowerShell; `curl.exe` dipakai agar tidak tertukar dengan alias PowerShell.

Tanpa header Accept:

```powershell
curl.exe -i -X POST http://kampuslms.test/api/v1/auth/login -d 'email=bukan-email' -d 'password=salah'
```

Dengan header Accept:

```powershell
curl.exe -i -X POST http://kampuslms.test/api/v1/auth/login -H 'Accept: application/json' -d 'email=bukan-email' -d 'password=salah'
```

Pada penanganan validasi bawaan, request pertama bisa dianggap seperti request browser biasa. Laravel mencoba redirect kembali, umumnya dengan status `302`, dan jika redirect diikuti, respons berikutnya bisa berupa HTML. Saya tidak bisa menyebut alamat redirect tertentu tanpa melihat request dan konfigurasi yang sedang berjalan.

Request kedua meminta JSON secara jelas. Hasil yang diharapkan adalah `422 Unprocessable Content` dengan objek `message` dan `errors`, termasuk kesalahan format email. Saya memahami header Accept seperti berkata, “tolong jawab saya dengan JSON.” Ini membantu Laravel memilih bentuk respons error.

Header ini tidak menjamin setiap respons selalu JSON jika controller sengaja mengembalikan HTML. Endpoint berhasil yang memang memakai `response()->json()` tetap dapat menghasilkan JSON meskipun header Accept tidak dikirim.

### **5. Jalankan `php artisan route:list --path=api`**

Saya menjalankan:

```bash
php artisan route:list --path=api
```

Hasilnya ada 15 route. Saya menambahkan garis miring awal pada URI di tabel supaya terlihat sebagai URL:

| Method | URI | Controller Action |
| --- | --- | --- |
| POST | `/api/v1/auth/login` | Api\AuthController@login |
| POST | `/api/v1/auth/logout` | Api\AuthController@logout |
| GET\|HEAD | `/api/v1/me` | Api\AuthController@me |
| GET\|HEAD | `/api/v1/courses` | Api\CourseController@index |
| GET\|HEAD | `/api/v1/courses/{course}` | Api\CourseController@show |
| GET\|HEAD | `/api/v1/courses/{course}/materials` | Api\MaterialController@index |
| GET\|HEAD | `/api/v1/courses/{course}/assignments` | Api\AssignmentController@index |
| POST | `/api/v1/assignments` | Api\AssignmentController@store |
| PUT\|PATCH | `/api/v1/assignments/{assignment}` | Api\AssignmentController@update |
| DELETE | `/api/v1/assignments/{assignment}` | Api\AssignmentController@destroy |
| GET\|HEAD | `/api/v1/assignments/{assignment}/submissions` | Api\SubmissionController@index |
| POST | `/api/v1/assignments/{assignment}/submissions` | Api\SubmissionController@store |
| PUT | `/api/v1/submissions/{submission}/grade` | Api\GradeController@update |
| GET\|HEAD | `/api/v1/notifications` | Api\NotificationController@index |
| POST | `/api/v1/notifications/{notification}/read` | Api\NotificationController@read |

Login memakai `throttle:5,1`. Route lainnya berada dalam grup `auth:sanctum` dan `throttle:60,1`. Saya perlu melihat kode route atau memakai tambahan `-v` untuk membaca middleware; tabel ringkas tadi hanya memperlihatkan method, URI, dan action.

---

## BREAK

Saya menggunakan data akun demo dan endpoint lokal untuk skenario berikut. `<TOKEN>` adalah pengganti token akun demo, bukan token sungguhan. Bagian “Hasil yang nyata” menjelaskan hasil yang diharapkan dari perubahan lab tersebut, bukan bukti bahwa request sudah saya jalankan pada aplikasi ini.

### **1. Kembalikan model User langsung, lalu hapus sementara `$hidden`**

#### Prediksi

Saya memprediksi data user akan muncul sebagai JSON. Awalnya saya belum terpikir bahwa kolom tambahan juga bisa ikut tampil walaupun tidak dibutuhkan klien.

#### Percobaan

Pada salinan lab, contoh endpoint sementara dalam grup yang terlindungi adalah:

```php
Route::get('/demo/users', function () {
    return response()->json(\App\Models\User::all());
});
```

Saya membandingkan respons sebelum dan sesudah properti `$hidden` pada model `User` dikosongkan sementara:

```powershell
curl.exe -i http://kampuslms.test/api/v1/demo/users -H 'Accept: application/json' -H 'Authorization: Bearer <TOKEN>'
```

Alternatif tanpa mengubah model atau menyimpan data adalah membuat objek dummy di Tinker, lalu membandingkan `$user->toArray()` dengan `$user->setHidden([])->toArray()`.

#### Hasil yang nyata

Model mentah mengeluarkan atribut yang tidak disembunyikan, termasuk email dan tanggal verifikasi jika tersedia. Begitu `$hidden` dikosongkan, hash password dan `remember_token` juga bisa ikut tampil. Hash bukan password asli, tetapi tetap sensitif karena bisa dipakai untuk mencoba tebakan password.

Saya membayangkan kaget melihat string hash password terpampang di JSON. Ini serem karena niat awalnya cuma menampilkan daftar user, tetapi data lain ikut terbawa. `remember_token` juga berbeda dari Bearer token Sanctum.

#### Kesimpulan

Saya perlu memilih kolom respons secara sadar. API Resource berfungsi seperti daftar barang yang memang boleh keluar. Resource proyek memilih `id`, `name`, `email`, `role`, `nim_nip`, dan `email_verified_at`; password tidak masuk daftar itu. Email pun hanya boleh dikirim jika endpoint dan penggunanya memang membutuhkan.

---

### **2. Hapus `auth:sanctum`, lalu panggil tanpa token**

#### Prediksi

Saya memprediksi route tanpa pemeriksaan autentikasi akan bisa dibuka tanpa login.

#### Percobaan

Pada salinan lab, saya membandingkan endpoint sederhana yang awalnya dilindungi `auth:sanctum`, lalu middleware tersebut dilepas:

```php
Route::get('/demo/private-courses', function () {
    return \App\Http\Resources\CourseResource::collection(
        \App\Models\Course::all()
    );
});
```

Request tidak membawa token:

```powershell
curl.exe -i http://kampuslms.test/api/v1/demo/private-courses -H 'Accept: application/json'
```

#### Hasil yang nyata

Pada endpoint demo yang langsung mengambil data tersebut, respons bisa menjadi `200` dan mata kuliah terbaca tanpa login. Resource tetap membatasi kolom, tetapi tidak menjaga siapa yang boleh meminta data.

Saya juga melihat perbedaan dengan controller proyek: `CourseController::index()` memakai `$request->user()->role`. Kalau hanya middleware-nya dihapus, pengguna menjadi `null` dan controller bisa mengalami error, bukan otomatis sukses membocorkan daftar. Karena itu, endpoint demo diperlukan untuk memperlihatkan pintu yang terbuka secara jelas.

#### Kesimpulan

Saya tidak bisa mengandalkan Resource untuk autentikasi. Request Bearer token perlu `auth:sanctum`, lalu tetap perlu aturan akses dan kepemilikan setelah penggunanya dikenali.

---

### **3. Pakai token yang sudah dicabut**

#### Prediksi

Saya memprediksi token lama tidak bisa dipakai lagi setelah catatannya di database dihapus.

#### Percobaan

Pada skenario ini saya login untuk memperoleh token demo, memakainya untuk logout, lalu mencoba token yang sama lagi:

```powershell
curl.exe -i -X POST http://kampuslms.test/api/v1/auth/logout -H 'Accept: application/json' -H 'Authorization: Bearer <TOKEN>'
curl.exe -i http://kampuslms.test/api/v1/me -H 'Accept: application/json' -H 'Authorization: Bearer <TOKEN>'
```

#### Hasil yang nyata

Controller logout proyek menghapus token aktif dengan `currentAccessToken()?->delete()` dan mengembalikan `204 No Content`. Request berikutnya dengan token tersebut diharapkan mendapat `401 Unauthorized` dan pesan `Unauthenticated.`

Pada autentikasi personal access token, Sanctum memeriksa token terhadap data `personal_access_tokens` pada setiap request. Menyimpan salinan token di klien tidak membuat token yang sudah dicabut menjadi berlaku lagi.

#### Kesimpulan

Saya memahami bahwa logout API ini mencabut token yang sedang dipakai. Token lain milik akun yang sama tidak otomatis ikut terhapus. Saya tidak perlu menghapus seluruh token pengguna untuk membuktikan percobaan ini.

---

### **4. Login sebagai Mahasiswa, lalu membuat assignment**

#### Prediksi

Saya memprediksi token mahasiswa dikenali, tetapi mahasiswa tetap tidak boleh membuat tugas.

#### Percobaan

Saya memakai token akun mahasiswa demo untuk request:

```powershell
curl.exe -i -X POST http://kampuslms.test/api/v1/assignments -H 'Accept: application/json' -H 'Authorization: Bearer <TOKEN_MAHASISWA>' -d 'course_id=1' -d 'title=Tugas Uji' -d 'instructions=Latihan API' -d 'due_at=2026-10-20 23:59:00' -d 'max_score=100' -d 'allow_late=0' -d 'status=published'
```

#### Hasil yang nyata

Dengan pemeriksaan peran dosen pada endpoint pembuatan tugas, mahasiswa diharapkan menerima `403 Forbidden`. Token valid membuat identitas saya dikenali, tetapi peran saya tidak mempunyai izin membuat assignment.

Saya mulai bisa membedakan penolakan ini dari token yang salah. Sama-sama gagal, tetapi penyebabnya berbeda.

#### Kesimpulan

Saya memahami `401` sebagai “sistem belum berhasil mengenali saya”, misalnya token tidak ada atau sudah dicabut. `403` berarti “sistem sudah mengenali saya, tetapi saya tidak boleh melakukan tindakan ini”.

---

### **5. Hapus eager loading dosen, lalu amati query**

#### Prediksi

Saya memprediksi nama dosen membutuhkan query tambahan kalau relasinya belum dimuat. Namun, saya perlu melihat cara Resource membaca relasi sebelum menyimpulkan pasti terjadi N+1.

#### Percobaan

Pada salinan lab, saya membandingkan query daftar 15 mata kuliah dengan dan tanpa:

```php
->with('lecturer')
```

Saya memanggil endpoint daftar dan mengamati query melalui query log atau alat pemantau query yang tersedia:

```powershell
curl.exe -i http://kampuslms.test/api/v1/courses -H 'Accept: application/json' -H 'Authorization: Bearer <TOKEN>'
```

Untuk memperlihatkan N+1, versi Resource lab mengakses relasi secara langsung:

```php
'lecturer' => new UserResource($this->lecturer),
```

#### Hasil yang nyata

Tanpa eager loading dan dengan akses langsung `$this->lecturer`, Laravel dapat melakukan lazy loading. Untuk 15 mata kuliah yang mempunyai dosen, pola sederhananya menjadi satu query daftar ditambah sampai 15 query dosen. Pagination juga dapat menambah query hitung total. Angka itu menjelaskan pola N+1, bukan hasil pengukuran yang sudah saya catat dari database proyek.

Pada Resource proyek sekarang, kode yang dipakai justru:

```php
'lecturer' => UserResource::make($this->whenLoaded('lecturer')),
```

Kalau saya hanya menghapus `with('lecturer')` dan mempertahankan `whenLoaded()`, field dosen tidak disertakan. Resource tidak mencari relasi sendiri, sehingga perubahan itu saja tidak memicu N+1. Jika lazy loading dilarang, akses langsung pada versi lab juga bisa menghasilkan exception.

#### Kesimpulan

Saya akhirnya paham pembagian tugasnya: controller memuat relasi yang diperlukan, Resource menampilkannya jika tersedia. `whenLoaded()` bukan perintah mengambil data. Perilaku ini dijelaskan dalam [dokumentasi conditional relationships Laravel 12](https://laravel.com/docs/12.x/eloquent-resources#conditional-relationships).

---

### **6. Hapus throttle login, lalu kirim request berulang**

#### Prediksi

Saya memprediksi tanpa throttle, server terus memproses percobaan login meskipun password salah berkali-kali.

#### Percobaan

Pada route proyek, login dibatasi dengan:

```php
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');
```

Saya membandingkan konfigurasi tersebut dengan salinan lab tanpa middleware throttle. Contoh 20 request lokal:

```powershell
1..20 | ForEach-Object {
    curl.exe -i -X POST http://kampuslms.test/api/v1/auth/login -H 'Accept: application/json' -d 'email=mahasiswa@kampuslms.test' -d 'password=salah'
}
```

#### Hasil yang nyata

Tanpa throttle atau pembatas lain, puluhan request bisa tetap diproses. Pada implementasi login proyek, kredensial salah menghasilkan `422` karena memakai `ValidationException`. “Lolos” di sini berarti percobaan diproses server, bukan login berhasil.

Dengan `throttle:5,1`, setelah jendela batas sebelumnya habis dan tanpa request lain dari identitas yang sama, lima request awal dapat diproses. Request keenam diharapkan mendapat `429 Too Many Requests`. Header `Retry-After` memberi tahu waktu tunggu.

#### Kesimpulan

Saya memahami rate limiting sebagai pembatas kecepatan mencoba. Tanpanya, menebak password berulang atau brute force jadi lebih mudah. Batas 5 request per menit adalah pilihan awal proyek, bukan angka wajib Laravel. Pada login tanpa autentikasi, batas bawaan berkaitan dengan domain dan IP, bukan hanya email; pengguna Wi-Fi kampus bisa berbagi batas yang sama.

---

### **7. Bedakan pesan “Email tidak terdaftar” dan “Password salah”**

#### Prediksi

Saya awalnya mengira pesan yang spesifik lebih membantu pengguna. Setelah dipikirkan lagi, pesan itu juga bisa memberi tahu orang luar apakah sebuah akun ada.

#### Percobaan

Pada salinan lab, saya membandingkan dua kondisi login yang menghasilkan pesan berbeda: user tidak ditemukan dan password tidak cocok. Saya mengirim dua request:

```powershell
curl.exe -i -X POST http://kampuslms.test/api/v1/auth/login -H 'Accept: application/json' -d 'email=tidak-ada-demo@example.test' -d 'password=salah'
curl.exe -i -X POST http://kampuslms.test/api/v1/auth/login -H 'Accept: application/json' -d 'email=mahasiswa@kampuslms.test' -d 'password=salah'
```

Email kedua diasumsikan milik akun demo yang memang terdaftar.

#### Hasil yang nyata

Dengan pesan spesifik, “Email tidak terdaftar” menunjukkan akun tidak ada. “Password salah” justru memberi petunjuk bahwa email tersebut terdaftar. Saya baru sadar bahwa orang tidak perlu berhasil login untuk mengumpulkan informasi akun kampus. Ini disebut user enumeration.

Controller proyek menggabungkan kedua kondisi dengan pesan yang sama:

```php
if (! $user || ! Hash::check($request->password, $user->password)) {
    throw ValidationException::withMessages([
        'email' => ['Email atau kata sandi salah.'],
    ]);
}
```

Dengan implementasi itu, kedua request diharapkan mendapat `422` dan pesan generik yang sama. Pesan tentang format email tidak valid tetap boleh spesifik karena tidak menyatakan apakah akun ada.

#### Kesimpulan

Saya perlu menjaga pesan gagal login tetap umum agar tidak membocorkan keberadaan akun. Pesan generik dan throttle saling membantu, tetapi pesan yang sama saja belum menutup semua petunjuk lain, misalnya perbedaan waktu respons.

---

## Ringkasan

Dari pembelajaran Minggu 6, saya memahami bahwa:

- API pada lab ini memakai Bearer token pada setiap request agar tidak bergantung pada session browser. Sanctum juga punya mode SPA berbasis cookie yang berbeda.
- Mengembalikan model mentah dapat mengeluarkan terlalu banyak atribut. `$hidden` membantu, tetapi API Resource membuat pilihan kolom respons lebih jelas.
- `Accept: application/json` menyatakan format respons yang saya inginkan, terutama agar error validasi ditangani sebagai JSON.
- Autentikasi mengenali pengguna; pemeriksaan peran dan kepemilikan menentukan tindakan yang boleh dilakukan pengguna itu.
- Eager loading menghindari query relasi berulang. `whenLoaded()` hanya menyertakan relasi yang sudah dimuat, bukan mengambilnya sendiri.
- Rate limiting penting pada login dan route sensitif agar percobaan otomatis tidak terus diproses tanpa batas.
- Pesan login generik mengurangi kebocoran informasi tentang akun yang terdaftar.

Saya juga mencatat status HTTP yang paling sering dipakai:

| Status | Arti sederhana | Contoh |
| --- | --- | --- |
| `200 OK` | Request berhasil | Membaca daftar mata kuliah |
| `201 Created` | Data baru berhasil dibuat | Membuat tugas |
| `204 No Content` | Berhasil tanpa isi respons | Logout pada proyek ini |
| `401 Unauthorized` | Autentikasi gagal | Token tidak dikirim atau sudah dicabut |
| `403 Forbidden` | Pengguna dikenali tetapi tidak diizinkan | Mahasiswa mencoba membuat tugas |
| `422 Unprocessable Content` | Input tidak lolos validasi | Format email salah; proyek ini juga memakainya untuk kredensial login salah |
| `429 Too Many Requests` | Batas request terlampaui | Terlalu banyak request login |

Saya merasa lebih paham setelah membedakan tugas masing-masing bagian: route menentukan alamat, controller mengambil data, Resource memilih isi JSON, Sanctum memeriksa identitas, dan throttle membatasi frekuensi request.
