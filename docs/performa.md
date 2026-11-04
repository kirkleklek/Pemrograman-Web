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

## 5. Cache

Data Course dan Assignment tidak diberi cache karena bergantung pada status publikasi, enrollment, dan hak akses pengguna.

Optimisasi cache konfigurasi, route, dan view Laravel dapat dilakukan melalui `php artisan optimize`.

## 6. Hasil Pengujian

Pengujian menggunakan `php artisan test`:

- 337 tests passed.
- 4.179 assertions.
- 0 failed.
- Durasi 13,23 detik.

Pengujian Assignment setelah perubahan terbaru:
- 88 tests passed.
- 637 assertions.

## 7. Kesimpulan

Optimisasi berhasil mengurangi jumlah query daftar tugas mahasiswa dari 10 menjadi 8 query dan daftar tugas dosen dari 7 menjadi 6 query.

Penggunaan composite index juga telah dibuktikan melalui `EXPLAIN QUERY PLAN`.

Seluruh pengujian otomatis berhasil. Namun, pengukuran browser masih menunjukkan variasi sehingga peningkatan kecepatan akses halaman secara keseluruhan belum dapat dipastikan.