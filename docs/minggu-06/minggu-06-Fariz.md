### Nama : Fariz Daffa Abbiyu Rahmatullah

### NIM  : 10241029

----

### READ

### 1. Jalankan php artisan install:api. Baca perubahan yang terjadi di bootstrap/app.php.

Perubahan utamanya ada di bootstrap/app.php, yaitu baris api: __DIR__.'/../routes/api.php' di dalam withRouting(). Baris ini bikin Laravel otomatis memuat routes/api.php dengan prefix /api dan middleware group api. Selain itu, install:api juga memasang Sanctum dan menjalankan migration personal_access_tokens. Di proyek kami routes/api.php sudah ada, jadi tidak dibuat ulang.

### 2. Buat satu endpoint GET /api/v1/courses sederhana.

Endpoint ini sudah ada di Api\CourseController@index dan terdaftar di routes/api.php dalam group auth:sanctum. Isinya menampilkan daftar course sesuai role user (dosen hanya course yang diampu, mahasiswa hanya course yang diikuti, admin semuanya), diurutkan berdasarkan code, dipaginasi 15 data per halaman, lalu dikembalikan sebagai JSON lewat CourseResource.

### 3. Bandingkan dengan CourseController versi web. Apa yang sama dan beda?

**Yang sama:**
- Sama-sama memakai model `Course` dan eager load relasi `lecturer`.
- Sama-sama diurutkan berdasarkan `code`.
- Sama-sama memakai `paginate(15)`.

**Yang beda:**
- **Output:** versi web mengembalikan `view('courses.index')` (HTML), sedangkan versi API mengembalikan JSON lewat `CourseResource`.
- **Pagination:** versi web memakai `withQueryString()` dan link halamannya dirender di view. Versi API mengirim informasi halaman lewat `meta` (`current_page`, `last_page`, `total`).
- **Pencarian dan filter:** versi web punya pencarian `q` (code/name) dan filter `status`. Versi API tidak punya.
- **Filter per role:** versi web tidak memfilter berdasarkan role di `index`, jadi semua course tampil. Versi API memfilter: dosen hanya course yang diampu, mahasiswa hanya course yang diikuti.
- **Data tambahan:** versi API menambahkan `withCount` untuk materials dan assignments, versi web tidak.
- **Autentikasi:** versi web berada di route `admin/*`, versi API memakai token Sanctum (`auth:sanctum`).
- **Operasi:** versi web CRUD lengkap (create, store, edit, update, destroy), versi API hanya baca (`index` dan `show`).
- **Otorisasi di `show`:** versi web hanya mengizinkan admin atau dosen pemilik course. Versi API juga mengizinkan mahasiswa yang terdaftar di course tersebut.
- **Respons 403:** versi web menampilkan halaman error HTML, versi API mengembalikan JSON `{"message": "..."}`.

Jadi, logika dasarnya sama (ambil course, urutkan, paginasi). Bedanya, versi web melayani tampilan HTML dengan fitur CRUD dan pencarian, sedangkan versi API melayani data JSON untuk klien berbasis token dengan akses yang dibatasi sesuai role.

### 4. Panggil endpoint API tanpa header `Accept: application/json`. Lalu dengan header itu. Catat bedanya.

**Tanpa header:**

```powershell
curl.exe -i http://127.0.0.1:8000/api/v1/courses
```

Hasil: muncul halaman error HTML `RouteNotFoundException: Route [login] not defined`. Tanpa header `Accept: application/json`, Laravel menganggap request datang dari browser, jadi user yang belum login coba di-redirect ke route `login`. Route itu tidak ada di proyek kami, sehingga error.

**Dengan header:**

```powershell
curl.exe -i -H "Accept: application/json" http://127.0.0.1:8000/api/v1/courses
```

Hasil: `401 Unauthorized` dengan body `{"message":"Unauthenticated."}`.

**Bedanya:** tanpa header, Laravel mencoba redirect dan hasilnya error HTML. Dengan header, Laravel langsung membalas 401 dalam bentuk JSON, tanpa redirect.

### 5. Jalankan `php artisan route:list --path=api`. Cocokkan dengan kontrak di spesifikasi.

Hasil `route:list` menunjukkan semua endpoint di kontrak sudah terdaftar dengan prefix `/api/v1`:

- `POST /auth/login` (publik), `POST /auth/logout`, `GET /me`
- `GET /courses`, `GET /courses/{course}`
- `GET /courses/{course}/materials`, `GET /courses/{course}/assignments`
- `POST /assignments`, `PUT|PATCH /assignments/{assignment}`, `DELETE /assignments/{assignment}`
- `GET` dan `POST /assignments/{assignment}/submissions`
- `PUT /submissions/{submission}/grade`
- `GET /notifications`, `POST /notifications/{notification}/read`

Selain `login`, semua route berada di group `auth:sanctum`, sesuai kolom akses "auth" di kontrak. Perbedaannya hanya penamaan parameter: kontrak memakai `{id}`, sedangkan Laravel memakai nama model seperti `{course}` dan `{assignment}` karena route model binding. Ini tidak memengaruhi URL yang dipanggil.

Jadi, daftar route sudah sesuai kontrak. Pengecekan hanya sampai tingkat route. Isi response dan aturan akses (misalnya pemilik, terdaftar) belum diuji di sini.


---

### BREAK Tujuh Kerusakan

### 1. Kembalikan `response()->json(User::all())`, lalu hapus `$hidden`

Prediksi: semua kolom tabel users tampil, dan tanpa `$hidden` hash password ikut bocor.

Hasil: dengan `$hidden`, `password` dan `remember_token` disembunyikan, tapi kolom lain seperti `email` tetap tampil. Setelah `$hidden` dihapus, hash password ikut muncul di response. Ini bukti `$hidden` menyaring kolom sensitif saat model diubah ke JSON. Karena itu response API sebaiknya lewat API Resource yang hanya memilih field tertentu, bukan mengembalikan model mentah.

### 2. Hapus `auth:sanctum` dari grup route, panggil tanpa token

Prediksi: endpoint bisa diakses siapa saja tanpa token.

Hasil: request tanpa token langsung mendapat `200` beserta datanya. Tanpa `auth:sanctum`, tidak ada yang memeriksa identitas pemanggil, sehingga data terbuka untuk publik. Selain itu `$request->user()` menjadi `null`, sehingga kode yang memakai `$user->role` bisa error.

### 3. Panggil endpoint terlindungi dengan token yang sudah dihapus

Prediksi: token yang sudah tidak ada di database akan ditolak.

Hasil: `401 Unauthorized` dengan body `{"message":"Unauthenticated."}`. Sanctum mencari token di tabel `personal_access_tokens`. Karena barisnya sudah dihapus (misalnya lewat logout), token itu tidak valid lagi walaupun string-nya masih disimpan klien.

### 4. Login sebagai mahasiswa, panggil `POST /api/v1/assignments`

Prediksi: mahasiswa sudah login tapi tidak berhak membuat tugas, jadi harus `403`, bukan `401`.

Hasil: `403` dengan body `{"message":"Anda tidak memiliki akses ke sumber daya ini."}`. Di `store()`, controller mengecek `$course->lecturer_id === $request->user()->id`, dan untuk mahasiswa hasilnya `false` sehingga `abort_unless(..., 403)` dijalankan. `401` berarti belum terautentikasi, sedangkan `403` berarti sudah terautentikasi tapi tidak berhak. Karena token mahasiswa valid, yang benar adalah `403`.

### 5. Hapus eager loading, panggil daftar mata kuliah

Prediksi: jumlah query bertambah seiring jumlah course, karena relasi `lecturer` di-query satu per satu.

Hasil: tanpa `->with('lecturer')`, setiap course memicu satu query tambahan untuk mengambil dosennya. Untuk 15 course terjadi sekitar 1 query utama + 15 query tambahan (masalah N+1). Dengan eager loading cukup 2 query. Makin banyak data, API makin lambat.

### 6. Hapus throttle dari login, jalankan 50 percobaan berturut-turut

Prediksi: tanpa pembatas, penyerang bisa mencoba password sebanyak-banyaknya.

Hasil: dengan `throttle:5,1`, percobaan ke-6 dalam satu menit ditolak `429 Too Many Requests`. Setelah throttle dihapus, ke-50 percobaan diproses semua dengan `422` (password salah) dan tidak pernah kena `429`. Artinya brute force dan credential stuffing berjalan tanpa hambatan.

### 7. Buat pesan login berbeda untuk email salah vs password salah

Prediksi: pesan yang berbeda membocorkan informasi.

Hasil: pada kode asli, email salah dan password salah sama-sama menghasilkan "Email atau kata sandi salah.", jadi penyerang tidak bisa membedakannya. Kalau dipisah (misalnya "Email tidak ditemukan" vs "Password salah"), penyerang bisa tahu email mana yang terdaftar (user enumeration). Daftar email valid itu bisa dipakai untuk brute force terarah, phishing, atau credential stuffing. Karena itu pesan login harus generik.