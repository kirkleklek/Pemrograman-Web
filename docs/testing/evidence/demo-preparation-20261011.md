# Bukti penutupan M02: persiapan database dan seeder demo

Tanggal: 11 Oktober 2026, Asia/Makassar. Database khusus: `kampuslms_uts_demo`, MySQL lokal.

## Eksekusi persiapan oleh pengguna

Sumber bukti eksekusi: hasil terminal lokal yang disampaikan pengguna dalam sesi finalisasi. Ini adalah ringkasan laporan eksekusi pengguna, bukan transcript stdout yang ditangkap ulang oleh agen.

- Perintah: `php scripts/prepare-bulan-demo.php`.
- Status yang dilaporkan: `Verified: APP_ENV=testing, MySQL kampuslms_uts_demo. Applying pending migrations without dropping tables.`
- Seluruh migration dari `0001_01_01_000000_create_users_table` sampai `2026_10_10_030327_create_feedback_templates_table` berhasil dengan status DONE.
- Seeder `BulanAuditDemoSeeder` berhasil mengisi data; exit code **0**.
- Password demo diberikan melalui memori. Pengguna melaporkan `$env:BULAN_DEMO_PASSWORD` telah dibersihkan dari session PowerShell. Nilai password tidak dikirim atau disimpan dalam dokumentasi.

## Verifikasi tambahan oleh agen

Pada `2026-10-11T20:05:20+08:00`, pemeriksaan read-only pada database yang sama menghasilkan **PASS / exit 0**. Tidak menjalankan migration, seeder, reset database, login, atau request API mutasi.

[Hasil JSON read-only](demo-readonly-verification-20261011.json) mencatat:

- `APP_ENV=testing`, database `kampuslms_uts_demo`, MySQL 8.4.3.
- Lima user sintetis dengan ID/email/role sesuai laporan terminal.
- Tiga course dan empat enrollment dengan pengampu/keanggotaan yang tepat.
- Dua materi, tiga tugas published, dan empat submission dengan FK sesuai tabel demo.
- Empat notifikasi dengan UUID/pemilik yang sesuai.
- Seluruh 12 nama migration cocok dengan migration repository.
- Keempat berkas submission tersedia pada disk local; ukurannya cocok dengan metadata database.

Pemeriksaan memakai SELECT hanya pada kolom ID, email sintetis, role, relasi, nama migration, status/deadline tugas, dan metadata berkas. Kolom password, remember_token, serta personal access token tidak dibaca atau ditampilkan. UUID notifikasi adalah ID resource, bukan token autentikasi.

## ID resource yang terverifikasi

| Aktor | User ID | Course | Material | Assignment | Submission contoh | Notification UUID |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| Admin | 1 | — | — | — | — | — |
| Mahasiswa A | 2 | 1 | 1 | 1 | 1 | 6faab711-bff4-40e8-bf07-73bfef76b500 |
| Dosen A | 3 | 1 | 1 | 1 | 1 | 0247c897-587c-4cd1-bab5-0e782a2e3e23 |
| Mahasiswa B | 4 | 2 | 2 | 2 | 2 | a59694ce-77b9-4c95-b4c5-354e84826dd4 |
| Dosen B | 5 | 2 | 2 | 2 | 2 | 76076679-fec2-46d8-9688-8e629340643f |

Kelas shared: Course ID **3**, Assignment ID **3**. Submission shared Mahasiswa A: **3**; Mahasiswa B: **4**. Dosen A mengampu kelas shared; kedua mahasiswa terdaftar. Submission 1/3 dimiliki Mahasiswa A dan 2/4 dimiliki Mahasiswa B; dosen mengaksesnya melalui kewenangan kelas.

## Keputusan dan batas bukti

**M02 CLOSED / PASS** untuk persiapan database dan seeder/data demo. Bukti BLOCKED/exit 2 sebelumnya tetap disimpan sebagai riwayat dan telah digantikan hasil persiapan sukses di atas.

Demo interaktif browser dan API live: **READY FOR UTS DEMO / PENDING PRESENTATION**. Presentasi, screenshot, dan rekaman video belum dijalankan/dibuat dalam sesi ini. Bukti kesiapan data tidak diklaim sebagai bukti pelaksanaan presentasi.
