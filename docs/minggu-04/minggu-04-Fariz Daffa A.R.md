### Nama : Fariz Daffa Abbiyu Rahmatullah

### NIM  : 10241029

----

### READ

### 1. Method apa yang menerima request? Di controller mana?

Method store() di CourseController yang nerima request-nya, karena form tambah data biasanya kirim lewat POST, dan di Laravel route POST /courses itu standarnya diarahkan ke method store() (pola REST resource controller).

### 2. Di titik mana persisnya validasi terjadi, sebelum atau sesudah baris pertama method controller?

Kalau validasinya pakai Form Request (misalnya StoreCourseRequest), validasi terjadi sebelum baris pertama method controller dijalankan. Laravel bakal resolve Form Request itu duluan lewat dependency injection, jalanin method rules() di dalamnya, dan kalau gagal, request langsung di-redirect balik tanpa pernah masuk ke body method controller sama sekali.

### 3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?

Laravel redirect balik ke halaman asal tempat form tadi disubmit (biasanya halaman /courses/create). Yang nentuin tujuannya itu otomatis dari header Referer request sebelumnya, jadi gak perlu ditulis manual di kode, itu bawaan behavior Laravel pas validasi gagal.

### 4. Dari mana @error('sks') mengambil pesannya?

Dari MessageBag yang di-flash ke session sama Laravel pas validasi gagal, disimpan dengan key errors. Directive @error('sks') otomatis cek ke situ, khusus buat field sks.

### 5. Dari mana old('sks') mengambil nilainya? Berapa lama nilai itu bertahan?

Dari data input yang di-flash ke session juga (key _old_input), isinya semua data yang tadi diisi user sebelum submit gagal. Nilainya cuma bertahan satu request berikutnya (session flash data), habis itu otomatis kehapus.

### 6. Buka DevTools, Application, Cookies. Temukan cookie session Laravel. Catat namanya.

Nama cookie session Laravel di proyek kami adalah laravel-session. Ini nama default Laravel kalau APP_NAME di .env gak diubah dari default (kalau APP_NAME diubah, nama cookie-nya biasanya ikut berubah jadi nama_aplikasi_session, tapi di proyek kami ternyata masih default). Cookie ini ditandai HttpOnly, artinya cuma bisa diakses server, gak bisa dibaca lewat JavaScript, ini bagian dari proteksi keamanan bawaan Laravel.

---

### BREAK

### 1. Hapus middleware web dari route form (pakai Route::withoutMiddleware), lalu submit form

Prediksi: middleware web itu yang nanganin session dan CSRF token. Kalau dihapus, form gak akan punya akses ke session dan token CSRF valid.

Hasil: submit form bakal gagal dengan error 419 Page Expired, karena token CSRF gak bisa diverifikasi tanpa middleware web yang aktifin session.

### 2. Hapus @csrf dari form, submit

Prediksi: form gak bakal ngirim token CSRF sama sekali.

Hasil: muncul error 419 Page Expired juga, karena Laravel nolak semua request POST yang gak nyertain token CSRF valid, ini proteksi bawaan biar form gak bisa disubmit dari situs lain.

### 3. Hapus validasi max:20 dari SKS, isi 99, submit

Prediksi: tanpa validasi itu, data SKS 99 (yang gak masuk akal) bakal lolos masuk ke database.

Hasil: data dengan SKS 99 beneran kesimpen, padahal secara logika gak ada mata kuliah SKS segitu, bukti kalau validasi itu satu-satunya penjaga data masuk yang masuk akal, database sendiri gak otomatis nolak angka yang "gak wajar" kalau kolomnya emang bertipe angka biasa.

### 4. Ganti redirect di controller jadi return view(...) langsung (bukan redirect)

Prediksi: ini ngelanggar pola PRG (Post-Redirect-Get). Kalau langsung return view(...) abis proses POST, browser bakal nampilin hasil submit di URL yang sama kayak form tadi.

Hasil: kalau user nge-refresh halaman abis submit berhasil, browser bakal nanya "Resubmit form?" atau bahkan langsung ngirim ulang data yang sama tanpa nanya (submit ganda), soalnya browser mikirnya itu masih halaman hasil POST, bukan halaman baru. Makanya pola PRG penting, biar abis POST selalu di-redirect ke GET, jadi refresh aman.

### 5. Panggil session()->flash() manual dengan key yang sama kayak yang dipakai Laravel buat errors

Prediksi: kalau kamu flash data manual ke key errors yang sama, itu bakal nimpa/bentrok sama mekanisme error bawaan Laravel.

Hasil: data error yang seharusnya muncul dari validasi normal bisa ketimpa atau malah error, karena Laravel sendiri pakai key errors buat nyimpen MessageBag validasi, bukan buat data flash biasa. Ini nunjukkin kenapa penting gak asal pakai nama key yang udah dipakai sistem.
