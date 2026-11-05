# Dokumentasi Performa KampusLMS — Week 11

## 1. Tujuan

Optimisasi halaman Course dan Assignment dilakukan untuk mengurangi query berulang, mencegah N+1, dan meningkatkan efisiensi akses database.

## 2. Optimisasi yang Dilakukan

- Eager loading dan penggunaan kembali relasi Eloquent.
- Menggunakan `loadMissing()` untuk mencegah pemuatan relasi berulang.
- Mengoptimalkan pemeriksaan enrollment pada `AssignmentPolicy`.
- Pagination daftar tugas sebanyak 15 data per halaman.
- Mengaktifkan `Model::preventLazyLoading()` pada lingkungan non-produksi.
- Menambahkan composite index untuk query daftar tugas mahasiswa.

## 3. Hasil Pengukuran Query

| Halaman | Sebelum | Sesudah |
|---|---:|---:|
| Daftar tugas mahasiswa | 10 query | 8 query |
| Daftar tugas dosen | 7 query | 6 query |

Pengukuran setelah optimisasi menunjukkan:
- Mahasiswa: rata-rata 4,54 ms waktu SQL per request.
- Dosen: 5,34 ms waktu SQL per request.

Hasil tersebut merupakan pengukuran lokal dan belum membuktikan peningkatan waktu respons secara konsisten.

## 4. Optimisasi Index Database

Migration tambahan:

`2026_11_02_124441_add_assignments_course_status_due_index.php`

Index:

`assignments_course_status_due_index`

Kolom:
- `course_id`
- `status`
- `due_at`

Index mendukung pencarian tugas berdasarkan mata kuliah dan status publikasi, dengan pengurutan berdasarkan deadline.

Hasil `EXPLAIN QUERY PLAN`:

**Sebelum:**
```text
SEARCH assignments USING INDEX assignments_course_id_due_at_index (course_id=?)
```

**Sesudah:**
```text
SEARCH assignments USING INDEX assignments_course_status_due_index (course_id=? AND status=?)
```

Hasil membuktikan SQLite menggunakan index gabungan yang baru.

## 5. Cache Dashboard

Caching diterapkan pada dashboard mahasiswa dan dosen menggunakan Laravel Cache dengan cache driver database.

Implementasi menggunakan `DashboardCacheService` dengan masa berlaku cache selama 300 detik (5 menit).

Cache menggunakan key terpisah untuk setiap pengguna:

- `dashboard:student:{id}` untuk dashboard mahasiswa.
- `dashboard:lecturer:{id}` untuk dashboard dosen.

### Mekanisme Invalidasi

Cache dihapus ketika terjadi perubahan data yang memengaruhi dashboard.

| Komponen | Mekanisme invalidasi |
|---|---|
| CourseObserver | Perubahan mata kuliah |
| AssignmentObserver | Perubahan tugas |
| SubmissionObserver | Pengumpulan dan perubahan submission |
| GradeObserver | Pembuatan dan perubahan nilai |
| EnrollmentController | Pendaftaran dan pengeluaran mahasiswa |

Observer didaftarkan melalui AppServiceProvider agar aktif di semua lingkungan aplikasi.

Operasi enrollment menggunakan invalidasi langsung karena `attach()` dan `detach()` pada relasi pivot tidak memicu event model Eloquent biasa.

Caching hanya diterapkan pada hasil ringkasan dashboard per pengguna, bukan sebagai cache global untuk data Course dan Assignment. Hal ini mempertahankan pemisahan data berdasarkan identitas dan hak akses pengguna.


## 6. Hasil Pengujian

Pengujian menggunakan `php artisan test`.

Hasil pengujian regresi lengkap:

- 349 tests passed.
- 4.704 assertions.
- 0 failed.
- Durasi 59,79 detik.

### Pengujian Invalidasi Cache

Pengujian khusus dilakukan untuk memastikan cache terhapus ketika data berubah.

- DashboardCacheInvalidationTest: 4 tes berhasil, 24 assertions.
- EnrollmentManagementTest: 9 tes berhasil, 38 assertions.

Pengujian mencakup perubahan submission, nilai, tugas, mata kuliah, serta penambahan dan penghapusan enrollment.

Hasil pengujian membuktikan bahwa mekanisme invalidasi cache bekerja pada skenario yang diuji.

## 7. Kesimpulan

Optimisasi berhasil mengurangi jumlah query daftar tugas mahasiswa dari 10 menjadi 8 query dan daftar tugas dosen dari 7 menjadi 6 query.

Penggunaan composite index juga telah dibuktikan melalui `EXPLAIN QUERY PLAN`.

Caching dashboard mahasiswa dan dosen telah diterapkan dengan masa berlaku 5 menit serta mekanisme invalidasi berdasarkan perubahan data terkait.

Seluruh 349 pengujian otomatis berhasil dengan 4.704 assertions. Namun, pengukuran browser masih menunjukkan variasi sehingga peningkatan waktu respons halaman secara keseluruhan belum dapat dipastikan.