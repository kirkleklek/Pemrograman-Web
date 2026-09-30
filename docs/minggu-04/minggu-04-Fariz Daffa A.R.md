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

### BREAK — Tujuh Kerusakan

### 1. Hapus @csrf dari form, lalu kirim

Prediksi: tanpa token CSRF, Laravel bakal nolak request POST karena gak bisa verifikasi token yang cocok.

Hasil: sesuai prediksi, muncul error 419 Page Expired. Ini bukti @csrf mencegah serangan CSRF, situs jahat gak bisa masang form tersembunyi yang otomatis ngirim POST pakai session korban yang login, karena token itu unik dan cuma valid dari form asli aplikasi kita.

### 2. Ganti $request->validated() jadi $request->all(), kirim field liar lewat curl

Prediksi: kalau pake all(), semua data yang dikirim bakal diterima mentah, termasuk field yang gak ada di form asli.

Hasil: field liar yang disisipin (misalnya field yang gak ada di form) bakal ikut kesimpen ke database kalau kebetulan ada di $fillable model. Ini bukti mass assignment kembali terbuka kalau validated() diganti all(), soalnya all() gak nyaring data sama sekali, beda sama validated() yang cuma ngasih field yang emang didaftarin di rules().

### 3. Hapus validasi exists:users,id pada lecturer_id, kirim lecturer_id=99999

Prediksi: tanpa exists:users,id, Laravel gak bakal ngecek apakah id yang dikirim beneran ada di tabel users.

Hasil: data course bakal kesimpen dengan lecturer_id=99999 walau user dengan id itu gak pernah ada. Ini bikin data yatim, course yang nunjuk ke dosen yang gak eksis, bikin relasi jadi rusak kalau nanti dipanggil lewat $course->lecturer.

### 4. Hapus validasi in:... pada status, kirim status=superadmin

Prediksi: tanpa in:draft,active,archived, Laravel bakal nerima nilai apa aja buat field status.

Hasil: data course kesimpen dengan status=superadmin, padahal itu bukan salah satu dari tiga pilihan yang seharusnya. Ini bikin enum jebol, aplikasi bisa error atau berperilaku aneh di tempat lain yang ngasumsiin status cuma tiga pilihan itu.

### 5. Hapus ->withQueryString(), cari lalu klik halaman 2

Prediksi: tanpa withQueryString(), link pagination gak bakal bawa parameter filter yang lagi aktif.

Hasil: begitu klik halaman 2, filter pencarian yang tadi diisi ilang, balik nampilin semua data tanpa filter. Ini bug klasik, user harus ngulang isi filter lagi tiap pindah halaman, padahal harusnya filter itu nempel terus.

### 6. Ganti return redirect() jadi return view() di store, tekan F5 setelah simpan

Prediksi: ini ngelanggar pola PRG (Post-Redirect-Get). Kalau abis POST langsung return view(), browser masih nganggep halaman itu hasil dari request POST tadi.

Hasil: pas ditekan F5, browser bakal nanya "Confirm Form Resubmission" atau langsung ngirim ulang data yang sama, bikin data ke-submit dua kali. Ini alasan kenapa pola PRG penting, redirect abis POST bikin browser pindah ke state GET yang aman buat di-refresh.

### 7. Hapus old(...) dari semua input, kirim form dengan satu kesalahan

Prediksi: tanpa old(), input yang gagal validasi gak bakal nyimpen nilai yang tadi diisi user.

Hasil: begitu form gagal validasi dan redirect balik, semua field kosong lagi, padahal cuma satu field yang salah (misalnya SKS = 99). User harus ngetik ulang SEMUA data dari awal, bukan cuma benerin satu field yang error. Ini pengalaman buruk yang bikin old() penting buat dipasang di setiap input.
