# Week 1

**Mata Kuliah: Pemrograman Web**\
**Pertemuan: 4**\
**Tanggal: 20 September 2026**\
**Topik: Pengelolaan State: Session, Validasi, dan Data yang Banyak**\

---

## Ringkasan

---
## READ
1. Method apa yang menerima request? Di controller mana?
    Request menggunakan method `POST` dan diterima oleh method `store()` pada `CourseController`

2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?
    Validasi terjadi di sebelum baris pertama method `store()` pada controller dijalankan, sistem menjalankan validasi dari `StoreCourseRequest` terlebih dahulu. Kalau data tidak memenuhi rules, request tidak melanjutkan proses ke isi method `store()`, tetapi sistem otomatis melakukan redirect kembali dengan pesan error dan input sebelumnya.

3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?
    Ketika validasi gagal, Laravel secara otomatis melakukan redirect kembali ke halaman/form sebelumnya. Tujuan redirect tersebut ditentukan oleh mekanisme validasi Form Request yang digunakan Laravel. Jadi, pada kondisi validasi gagal, programmer tidak perlu menuliskan redirect() secara manual. Laravel juga membawa pesan error dan input sebelumnya agar dapat ditampilkan kembali pada form.

4. Dari mana @error('sks') mengambil pesannya?
    @error('sks') mengambil pesan dari error validasi Laravel yang terjadi pada field sks. Error tersebut dihasilkan berdasarkan aturan validasi yang ada pada StoreCourseRequest. Contohnya, jika nilai SKS adalah 99, maka aturan between:1,6 gagal dan Laravel menyediakan pesan error yang kemudian dapat ditampilkan melalui @error('sks') dengan variabel $message.

5. Dari mana old('sks') mengambil nilainya? Berapa lama nilai itu bertahan?
    old('sks') mengambil nilai SKS dari input pada request sebelumnya yang gagal validasi. Laravel menyimpan input tersebut sementara agar dapat ditampilkan kembali ketika pengguna kembali ke form. Nilai ini hanya bertahan sementara, yaitu untuk request berikutnya, sehingga tidak tersimpan secara permanen di database.

6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.
    Pada DevTools → Application → Cookies, ditemukan cookie session Laravel dengan nama [isi sesuai hasil pada aplikasi]. Cookie tersebut digunakan Laravel untuk menyimpan identitas session pengguna sehingga server dapat mengenali state pengguna pada request berikutnya.

---

## Break

1. Hapus `@csrf` dari form, lalu kirim
* Prediksi awal : 

* Hasil :

2. Ganti `$request->validated()` menjadi `$request->all()`, lalu kirim field liar lewat `curl`
* Prediksi awal :

* Hasil :

3. Hapus validasi `exists:users,id` pada `lecturer_id`, kirim `lecturer_id=99999`
* Prediksi awal :

* Hasil :

4. Hapus validasi `in:...` pada `status`, kirim `status=superadmin`
* Prediksi awal :

* Hasil :

5. Hapus `->withQueryString()`, lakukan pencarian lalu klik halaman 2
* Prediksi awal :

* Hasil :

6. Ganti `return redirect()` menjadi `return view()` pada `store`, lalu tekan F5 setelah simpan
* Prediksi awal :

* Hasil :

7. Hapus `old(...)` dari semua input, lalu kirim form dengan satu kesalahan
* Prediksi awal :

* Hasil :

---
## Check Point Week 4
1. Kenapa validasi di JavaScript tidak dianggap keamanan? Peragakan cara melewatinya.
2.  Apa yang dikembalikan `$request->validated()` dan kenapa lebih aman daripada $request->all()?
3.  Jelaskan pola PRG. Apa yang terjadi kalau store mengembalikan view?
4.  Kenapa filter pencarian sebaiknya di query string, bukan session? Beri satu skenario yang rusak kalau dipindah ke session.
5.  Apa fungsi @csrf? Serangan apa yang dicegahnya, dan bagaimana serangan itu bekerja?
6.  Kenapa aturan unique pada update perlu `ignore()`?