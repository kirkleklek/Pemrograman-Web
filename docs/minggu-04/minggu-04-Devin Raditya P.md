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


2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?


3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?

4. Dari mana @error('sks') mengambil pesannya?


5. Dari mana old('sks') mengambil nilainya? Berapa lama nilai itu bertahan?


6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.


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