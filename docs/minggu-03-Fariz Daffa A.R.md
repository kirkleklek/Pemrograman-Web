### Nama : Fariz Daffa Abbiyu Rahmatullah

### NIM  : 10241029

----

### READ

### 1.

### 2. Untuk setiap foreign key, tentukan perilaku onDelete-nya dan alasannya

Berdasarkan dari spesifikasi proyek, ini foreign key dan onDelete-nya:

- courses.lecturer_id - users.id, pakai restrictOnDelete. Alasannya, dosen tidak boleh dihapus selama masih mengampu mata kuliah, soalnya kalau dosennya kehapus, mata kuliahnya jadi kehilangan pengampu padahal datanya masih dipakai/relevan.

- course_user.course_id dan course_user.user_id, keduanya biasanya pakai cascadeOnDelete. Alasannya, kalau mata kuliah atau usernya dihapus, data pendaftaran (enrollment) di tabel pivot ini otomatis ikut kehapus, soalnya data itu memang gak ada gunanya lagi kalau salah satu sisinya udah gak ada.

- materials.course_id - courses.id, pakai cascadeOnDelete. Alasannya, kalau mata kuliahnya dihapus, materi-materi di dalamnya juga otomatis kehapus, soalnya materi itu memang cuma "milik" mata kuliah itu, gak ada gunanya kalau berdiri sendiri.

- assignments.course_id - courses.id, pakai cascadeOnDelete. Sama kayak materials, kalau mata kuliah dihapus, semua tugas yang terkait juga ikut kehapus.

- submissions.assignment_id - assignments.id, pakai cascadeOnDelete. Kalau tugasnya dihapus, submission dari mahasiswa buat tugas itu juga otomatis kehapus.

- grades.submission_id - submissions.id, pakai cascadeOnDelete. Kalau submission-nya dihapus, nilai yang udah dikasih ke submission itu juga ikut kehapus, soalnya nilai itu gak ada artinya lagi kalau submission-nya udah gak ada.

### 3. Kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya? Kenapa dirancang begitu?

Karena lecturer_id pakai restrictOnDelete, sistem bakal menolak penghapusan dosen yang masih mengampu mata kuliah. Ini dirancang begitu biar mata kuliah gak kehilangan pengampu tiba-tiba, kalau dosennya beneran mau dihapus, harus pindahin dulu pengampu mata kuliahnya ke dosen lain.

### 4. Kenapa grades.submission_id bersifat unique, bukan sekadar index biasa?

Karena relasinya one-to-one, satu submission cuma boleh punya satu nilai. Index biasa cuma mempercepat pencarian, gak mencegah data ganda. Dengan unique, database otomatis nolak kalau ada yang coba kasih nilai kedua buat submission yang sama, jadi aturan bisnisnya ditegakkan langsung di database, bukan cuma diandelin dari pengecekan controller yang bisa gagal kalau ada race condition.

---

### BREAK 

### 1. Hapus unique(['course_id','user_id']) dari course_user, lalu daftarkan mahasiswa yang sama dua kali

Prediksi: kalau constraint unique-nya dihapus, database gak bakal nolak lagi walau Mahasiswa yang sama didaftarin dua kali ke mata kuliah yang sama.

Hasil: data ganda beneran lolos masuk ke database tanpa ada error sama sekali. Ini bahaya soalnya data enrollment jadi gak konsisten, satu Mahasiswa bisa punya dua baris pendaftaran buat mata kuliah yang sama, padahal harusnya cuma boleh satu. Buktinya constraint unique itu penting buat jaga integritas data, bukan sekadar formalitas.

### 2. Tambahkan role ke $fillable model User, lalu kirim request pembuatan user dengan role=admin lewat form yang gak punya field role

Prediksi: karena role sekarang ada di $fillable, sistem bakal nerima field role dari request meskipun form aslinya gak punya input buat field itu.

Hasil: berhasil, user baru yang dibuat langsung punya role admin, padahal formnya sama sekali ga ngasih pilihan role. Ini bukti nyata mass assignment, form di frontend itu bukan pembatas apa pun, penyerang bisa nyisipin field apa aja lewat request langsung (misal pakai Tinker atau curl), asal field itu ada di $fillable.

### 3. Ganti seluruh $fillable dengan protected $guarded = [], lalu ulangi nomor 2

Prediksi: $guarded = [] artinya semua kolom dianggap aman diisi, jadi bakal lebih parah dari nomor 2, bukan cuma role yang bisa disisipin, tapi kolom apa aja di tabel users.

Hasil: benar, serangan mass assignment jadi makin gampang karena semua kolom kebuka. Ini kenapa $guarded = [] dilarang keras, itu sama aja kayak matiin seluruh perlindungan mass assignment sekaligus, beda sama $fillable yang emang sengaja whitelist kolom yang boleh diisi aja.

### 4. Kosongkan isi down() di satu migrasi, lalu jalankan php artisan migrate:refresh

Prediksi: down() yang kosong artinya migrasi itu gak tahu cara "membalikkan" perubahan yang dibuat up(), jadi migrate:refresh (yang rollback semua terus migrate ulang) bakal gagal atau tabelnya gak balik ke kondisi bersih.

Hasil: migrate:refresh gagal atau nyisain tabel yang harusnya udah kehapus pas rollback, soalnya down() gak ngejalanin dropIfExists atau perubahan balik lainnya. Ini penting soalnya CI/CD nanti bakal jalanin migrate:refresh, kalau ada migrasi yang gak reversible, CI-nya bakal merah dan proyeknya dianggap gak lolos.

### 5. Ubah restrictOnDelete pada lecturer_id jadi cascadeOnDelete, lalu hapus satu dosen

Prediksi: kalau diubah jadi cascadeOnDelete, pas dosennya dihapus, mata kuliah yang diampu dosen itu bakal ikut kehapus otomatis, bukan ditolak penghapusannya kayak sebelumnya.

Hasil: sesuai prediksi, hapus 1 dosen bikin semua mata kuliah yang dia ampu ilang, dan otomatis semua materi, tugas, submission yang nempel di mata kuliah itu (karena mereka juga cascadeOnDelete) ikut ilang berantai. Ini bukti kenapa restrictOnDelete penting buat data yang masih "dipakai", biar gak ada penghapusan gak sengaja yang bikin banyak data penting ilang sekaligus.