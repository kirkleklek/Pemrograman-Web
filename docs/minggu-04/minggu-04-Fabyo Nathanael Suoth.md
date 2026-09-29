# 4.3 Read → Break → Fix → Build

__Nama : Fabyo Nathanael Suoth\
NIM : 10241027__

---

## READ

### 1. Method apa yang menerima request? Di controller mana?
Request dari form tersebut diproses oleh method `store()` yang berada di `CourseController`. Pada resource controller Laravel, request dengan method POST menuju `/courses` memang diarahkan ke `store()` untuk menangani proses penambahan data course.

---

### 2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?
Validasi dilakukan sebelum kode pertama di dalam `store()` dijalankan karena form menggunakan `StoreCourseRequest`. Laravel akan memproses Form Request terlebih dahulu, termasuk menjalankan aturan yang ada di `rules()`. Kalau data tidak memenuhi aturan validasi, proses berhenti dan method `store()` tidak sampai dijalankan.

---

### 3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?
Ketika validasi tidak berhasil, Laravel mengembalikan user ke halaman sebelumnya, yang biasanya merupakan halaman form `/courses/create`. Redirect tersebut ditentukan secara otomatis berdasarkan request sebelumnya, sehingga controller tidak perlu menentukan URL tujuan secara langsung.

---

### 4. Dari mana @error('sks') mengambil pesannya?
Pesan yang ditampilkan oleh `@error('sks')` berasal dari error bag yang Laravel simpan di session setelah validasi gagal. Error tersebut dibuat berdasarkan aturan dan pesan validasi yang terdapat pada `StoreCourseRequest`, kemudian Blade mengambil pesan yang berkaitan dengan field sks.

---

### 5. Dari mana old('sks') mengambil nilainya? Berapa lama nilai itu bertahan?
Nilai `old('sks')` berasal dari input sebelumnya yang disimpan sementara di session menggunakan `_old_input`. Dengan mekanisme ini, data yang sudah diketik user tetap bisa ditampilkan kembali ketika terjadi kesalahan validasi. Data tersebut hanya digunakan pada request selanjutnya karena merupakan flash data, kemudian akan hilang secara otomatis.

---

### 6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.
Cookie session yang digunakan pada proyek ini bernama `laravel-session`. Nama tersebut mengikuti konfigurasi session Laravel yang mengambil nama aplikasi dari `APP_NAME`, kemudian menambahkan akhiran `-session`.

---

### BREAK 

### 1. Hapus `@csrf` dari form, lalu kirim

Prediksi: karena token CSRF tidak lagi dikirim bersama form, request POST seharusnya ditolak oleh Laravel.

Hasil: muncul `419 Page Expired`. Hal ini terjadi karena Laravel melakukan pengecekan token CSRF pada request POST dan token yang valid tidak ditemukan. Mekanisme tersebut digunakan untuk mencegah request palsu dari sumber lain.

---

### 2. Ganti `$request->validated()` menjadi `$request->all()`, lalu kirim field liar lewat curl

Prediksi: data yang masuk ke controller akan lebih banyak karena `all()` mengambil seluruh input dari request, bukan hanya data yang sudah melewati validasi.

Hasil: `$request->all()` memang mengambil semua data yang dikirim. Tetapi model `Course` masih menggunakan `$fillable`, sehingga field yang tidak diperbolehkan tidak langsung disimpan. Meskipun begitu, penggunaan `all()` tetap kurang aman karena data yang diterima tidak dibatasi berdasarkan hasil validasi.

---

### 3. Hapus validasi `exists:users,id` pada `lecturer_id`, kirim `lecturer_id=99999`

Prediksi: sistem tidak lagi melakukan pengecekan apakah ID dosen yang dikirim benar-benar terdapat pada tabel `users`.

Hasil: jika tidak ada foreign key database yang menolaknya, course dengan `lecturer_id=99999` dapat tersimpan. Akibatnya, data course memiliki referensi dosen yang tidak tersedia. Validasi `exists:users,id` diperlukan untuk membantu menjaga agar hubungan antara course dan dosen tetap valid.

---

### 4. Hapus validasi `in:...` pada status, kirim `status=superadmin`

Prediksi: nilai `superadmin` akan diterima karena tidak ada aturan yang membatasi pilihan status.

Hasil: nilai `superadmin` dapat masuk ke database meskipun bukan status yang diperbolehkan oleh sistem. Status yang seharusnya digunakan adalah `draft`, `active`, atau `archived`. Jadi aturan in berfungsi membatasi nilai agar tetap sesuai dengan kebutuhan aplikasi.

---

### 5. Hapus `->withQueryString()`, lakukan pencarian lalu klik halaman 2

Prediksi: kata kunci atau filter yang digunakan saat pencarian tidak akan ikut terbawa ketika berpindah ke halaman berikutnya.

Hasil: parameter pencarian seperti `q` dan `status` tidak ikut dimasukkan ke URL pagination. Akibatnya, halaman berikutnya dapat menampilkan data yang tidak lagi mengikuti filter sebelumnya. `withQueryString()` diperlukan agar parameter tersebut tetap dibawa saat pagination digunakan.

---

### 6. Ganti `return redirect()` menjadi `return view()` pada `store`, lalu tekan F5 setelah simpan

Prediksi: browser masih berada pada hasil request POST sehingga melakukan refresh dapat menyebabkan form dikirim kembali.

Hasil: browser dapat menampilkan peringatan untuk mengirim ulang form atau melakukan POST kembali. Hal tersebut berpotensi menyebabkan data yang sama masuk lebih dari sekali. Redirect setelah POST digunakan agar browser berpindah ke request GET sehingga halaman dapat di-refresh dengan lebih aman.

---

### 7. Hapus `old(...)` dari semua input, lalu kirim form dengan satu kesalahan

Prediksi: ketika validasi gagal, nilai yang sebelumnya sudah dimasukkan pada field form tidak akan dipertahankan.

Hasil: error validasi masih dapat ditampilkan, tetapi field lain yang sebelumnya sudah diisi akan kembali kosong. User harus memasukkan ulang data tersebut. `old()` membantu mempertahankan input sebelumnya sehingga user tidak perlu mengulang seluruh pengisian form.

---