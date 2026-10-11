# Rencana audit integrasi Bulan

Tanggal audit: 11 Oktober 2026 (Asia/Makassar). Branch: `Bulan/audit`, HEAD awal `a11f51d`.

## Tujuan dan batas pekerjaan

Menguji akses yang diizinkan/ditolak, IDOR dua arah dengan dua mahasiswa dan dua dosen, serta regresi API Week 6. Tes lama tetap dipertahankan. Tidak ada perubahan controller, route, policy, middleware, Form Request, model, migration, atau fitur CRUD. Folder lokal awal `docs/checkpoint/` tidak diubah atau dimasukkan ke pekerjaan ini. Tidak ada commit/push.

## Sumber kontrak yang diperiksa

- `routes/web.php`, `routes/api.php`, `bootstrap/app.php`.
- Controller web/API, `CoursePolicy`, `MaterialPolicy`, middleware role/publikasi tugas, Form Request, Resources, model, factory, migration.
- `docs/api.md`, `docs/minggu-06-Eagan.md`, `docs/minggu-06/minggu-06-Fariz.md`.
- Riwayat Week 6: `c1d04be` (auth/course/material/assignment), `744b274` (integrasi REST/submission/grade/notification), `15e4126` (format validasi), `8ff8d81` (tes auth), `3beaaa3` (cakupan endpoint), `38ffc89` (notifikasi).
- Tes yang ada menjadi baseline aktual. Catatan READ/BREAK lama berisi simulasi dan perilaku versi lama; tidak diperlakukan sebagai bukti PASS. Contohnya route web `login` sekarang tersedia.

## Fitur, endpoint, dan aturan aktual

Prefix API: `/api/v1`. Login publik dibatasi 5 request/menit. Semua route API lainnya memakai `auth:sanctum`, 60 request/menit, dan header `Accept: application/json` dalam tes.

| Method dan endpoint | Akses/kontrak aktual | Referensi cakupan |
| --- | --- | --- |
| POST `/auth/login` | Validasi/kredensial gagal 422; berhasil 200 dengan `data.token` dan `data.user`; percobaan ke-6 429 | AuthApiTest, Week6RegressionTest |
| POST `/auth/logout`; GET `/me` | Login diperlukan; logout 204 mencabut token aktif, token dicabut 401; `/me` 200 | AuthApiTest, AccessIntegrationTest, Week6RegressionTest |
| GET `/courses` | Admin semua, dosen kelas yang diampu, mahasiswa kelas yang diikuti; pagination 15 | CourseApiTest, IdorIntegrationTest |
| GET `/courses/{course}` | Admin, dosen pengampu, mahasiswa terdaftar; lainnya 403 | CourseApiTest, AccessIntegrationTest, IdorIntegrationTest |
| GET `/courses/{course}/materials` | Akses mengikuti CoursePolicy; termasuk admin pada API; pagination 15 | MaterialApiTest, IdorIntegrationTest, Week6RegressionTest |
| GET `/courses/{course}/assignments` | Akses kelas; mahasiswa hanya published, termasuk count; filter status; status invalid 422 | AssignmentApiTest, IdorIntegrationTest, Week6RegressionTest |
| POST `/assignments` | Dosen pengampu saja, 201; `course_id` tidak ada 422 melalui exists, kelas dosen lain 403 | AssignmentApiTest, IdorIntegrationTest, Week6RegressionTest |
| PUT/PATCH `/assignments/{assignment}` | Dosen pengampu, 200; FK dan created_by tidak termasuk field update | AssignmentApiTest, IdorIntegrationTest, Week6RegressionTest |
| DELETE `/assignments/{assignment}` | Dosen pengampu, 204; cascade submission/grade | AssignmentApiTest, IdorIntegrationTest, Week6RegressionTest |
| GET `/assignments/{assignment}/submissions` | Dosen pengampu saja; mahasiswa/admin tidak diberi akses | SubmissionApiTest, IdorIntegrationTest |
| POST `/assignments/{assignment}/submissions` | Mahasiswa terdaftar; published; satu submission; deadline sesuai allow_late; multipart file; sukses 201 | SubmissionApiTest, IdorIntegrationTest, Week6RegressionTest |
| PUT `/submissions/{submission}/grade` | Dosen pengampu saja; score 0–100; upsert 201 lalu 200 | GradeApiTest, IdorIntegrationTest, Week6RegressionTest |
| GET `/notifications`; POST `/notifications/{notification}/read` | Hanya notifikasi pengguna sendiri; UUID asing 403; read sukses 200 | NotificationApiTest, IdorIntegrationTest |
| `/admin/users`, `/admin/courses` resource web | Session auth, role admin; dosen/mahasiswa ditolak meskipun policy course memberi izin tertentu pada dosen | UserManagementTest, RoleMiddlewareTest, AccessIntegrationTest, IdorIntegrationTest |
| GET `/dosen/courses/{course}/materials`, `/assignments`, `/materials/{material}`, `/assignments/{assignment}` | Role dosen + kelas pengampu; resource routes juga menyediakan create/edit/store/update/destroy | MaterialWebTest, AssignmentWebTest, IdorIntegrationTest |
| GET `/dosen/assignments/{assignment}/submissions`, `/dosen/submissions/{submission}` | Dosen pengampu | IdorIntegrationTest |
| GET `/mahasiswa/courses/{course}/materials`, `/assignments`; `/mahasiswa/materials/{material}`, `/assignments/{assignment}` | Mahasiswa terdaftar; tugas harus published | MaterialWebTest, AssignmentWebTest, IdorIntegrationTest |
| GET `/mahasiswa/submissions/{submission}` | Submission sendiri, anggota kelas, assignment published | ResourceOwnershipTest, AssignmentWebTest, IdorIntegrationTest |
| POST `/mahasiswa/assignments/{assignment}/submissions` | Mahasiswa terdaftar, published, tidak duplikat, deadline; sukses redirect ke detail | IdorIntegrationTest untuk penolakan ID kelas asing |

ID route yang tidak ditemukan menghasilkan 404. Guest web redirect 302 ke `/login`; guest API dengan Accept JSON menghasilkan 401. Tes memeriksa kontrak ini secara terpisah. API tidak mempunyai detail materi, detail tugas, detail submission mahasiswa, atau update/delete submission; tidak dibuat endpoint fiktif untuk mengujinya. Operasi mahasiswa yang dilarang diuji pada route dosen/API yang benar-benar tersedia.

## Baseline dan kekurangan cakupan

Baseline sebelum penambahan tes: 164 tes / 912 assertion, PASS. Tersedia tes autentikasi/reset password, role, policy, resource ownership, users, CRUD materi/tugas web, tujuh kelompok API, dan format validasi API. Kekurangan yang dilengkapi: pasangan akun simetris, isolasi ownership dari enrollment, snapshot seluruh tabel terkait setelah penolakan, preservasi berkas, query/payload FK, ID tidak ditemukan, real token setelah logout, throttle, dan alur lintas endpoint.

## Lingkungan dan strategi

- Windows, PHP 8.3.26, PHPUnit 11.5.56, MySQL 8.4.3; dependency lokal `vendor/` tersedia.
- Runner `scripts/run-bulan-tests.php` memaksa nilai env dari phpunit.xml untuk proses parent/child, menolak cached config/DB_URL, dan memeriksa `SELECT DATABASE()` sebelum PHPUnit. Hanya `APP_ENV=testing`, koneksi MySQL, database `kampuslms_testing` diterima. Kredensial koneksi tetap lokal dan tidak dicetak.
- Database harus khusus tes: RefreshDatabase menjalankan migration/rebuild dan transaksi. Jangan arahkan runner atau phpunit.xml ke database aplikasi. Jangan menjalankan suite secara bersamaan pada database yang sama.
- Upload memakai `Storage::fake('local')`; fixture/factory memakai data sintetis. Snapshot mencakup users, courses, course_user, materials, assignments, submissions, grades, notifications. Untuk setiap operasi yang ditolak, tes memeriksa tabel terkait tetap sama; beberapa tes memeriksa seluruh snapshot setelah setiap request.
- Tes akses/IDOR memakai `actingAs`/`Sanctum::actingAs` dengan middleware otorisasi tetap aktif. Tes token memakai login dan Bearer token sungguhan di dalam proses, tanpa mencetak credential/token. Guard dibersihkan saat mensimulasikan request baru setelah logout.
- Urutan eksekusi: baseline → tes baru → seluruh Feature → regresi Week 6 lama+baru → seluruh suite. Hasil dan JUnit disimpan di `evidence/`.

## Identitas dan hubungan data

`IntegrationFixture` membuat Mahasiswa A/B dan Dosen A/B. Kelas AUDIT-A diampu Dosen A dengan Mahasiswa A; AUDIT-B diampu Dosen B dengan Mahasiswa B. AUDIT-SHARED diampu A dan diikuti kedua mahasiswa; tiap mahasiswa memiliki submission privat sendiri di assignment bersama. Materi/tugas kelas boleh dibaca anggota yang sah, bukan diperlakukan sebagai milik pribadi mahasiswa. Submission dan notifikasi diuji sebagai resource privat. Admin tambahan dipakai hanya untuk memeriksa batas izin role.

## File pekerjaan

- `tests/Support/IntegrationFixture.php`.
- `tests/Support/AssertsPrivateResponse.php` dan `tests/Unit/JsonPrivacyAssertionTest.php` untuk kontrol pemeriksaan decoded JSON (tindak lanjut M01).
- `tests/Feature/AccessIntegrationTest.php`, `IdorIntegrationTest.php`, `Week6RegressionTest.php`.
- `scripts/run-bulan-tests.php` (guard dan evidence), `scripts/prepare-bulan-demo.php` (persiapan demo terpisah).
- `database/seeders/BulanAuditDemoSeeder.php` (data sintetis demo; hanya testing/kampuslms_uts_demo).
- Empat dokumen di folder ini dan log/JUnit evidence.

## Batasan

Feature test memakai kernel Laravel, bukan browser/server HTTP jaringan. Manual UAT, screenshot, deployment, concurrency/race, kinerja, pengujian download file, CSRF melalui browser, dan pemeriksaan keamanan menyeluruh tidak diklaim telah dijalankan. Demo helper disediakan untuk persiapan pada database khusus; status validasinya dicatat terpisah. Perbedaan max_score aktual dipertahankan: web menerima 0–100, API create/update 1–100; bukan diubah untuk menyamakan tes.

## Tindak lanjut M01/L01/L02/L03

Pemeriksaan respons JSON kini membaca seluruh key/nilai string yang telah didekode, sehingga escaped slash/Unicode/quotes tidak menghindari assertion. Delapan kontrol Unit menguji helper tersebut secara terpisah dari endpoint aplikasi. Kontrol positif IDOR memeriksa identitas/relasi model pada JSON dan view; guest web memakai ID fixture yang dibuktikan ada.

Runner menyimpan bukti di `evidence/runs/<timestamp-microseconds>-<stage>-<random>/`, tanpa menimpa log/JUnit historis. Setiap folder memuat manifest command, waktu, environment non-sensitif, exit code, hash source/config, serta hash artefak. Lokasi run dicetak di terminal. Verifikasi ulang wajib `new` → `regression` → `all`; hasil terbaru dan riwayat kegagalan selama implementasi dicatat di test-results. Stage `new` sekarang mencakup empat kelas audit, termasuk delapan kontrol Unit; baseline tetap memilih 164 tes lama.

Status akhir audit: **PASS** untuk pengujian otomatis, mitigasi IDOR, regresi API Week 6, dan infrastruktur data demo. M01/L01/L02/L03 sudah ditutup; M02 database/seeder juga **CLOSED / PASS** setelah persiapan lokal exit 0 dan verifikasi data read-only. [Bukti M02](evidence/demo-preparation-20261011.md) memisahkan laporan eksekusi terminal pengguna dari hasil verifikasi agen. Presentasi interaktif browser/API live: **READY FOR UTS DEMO / PENDING PRESENTATION**; belum ada klaim screenshot/video atau pelaksanaan presentasi.
