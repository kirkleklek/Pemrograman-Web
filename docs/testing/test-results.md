# Hasil pengujian integrasi Bulan

Audit dijalankan 11 Oktober 2026 (Asia/Makassar), branch `Bulan/audit`, HEAD `a11f51d`. Semua angka berikut berasal dari output PHPUnit dan JUnit yang tersimpan. Tidak ada commit/push.

**Keputusan akhir audit: PASS** untuk kesiapan pengujian otomatis, mitigasi IDOR, regresi API Week 6, dan infrastruktur data demo UTS. M01, M02, L01, L02, L03 semuanya **CLOSED / PASS**. M02 ditutup setelah persiapan database/seeder berhasil di terminal lokal dan data hasilnya diverifikasi secara read-only. Demo browser/API live siap dipresentasikan dengan status **READY FOR UTS DEMO / PENDING PRESENTATION**; pelaksanaan presentasi serta screenshot/video tidak diklaim sudah selesai.

## Lingkungan dan keamanan data

Windows, PHP 8.3.26, PHPUnit 11.5.56, MySQL 8.4.3. Database otomatis: **kampuslms_testing**; `APP_ENV=testing`; cache/session array, mail array, queue sync sesuai phpunit.xml. Runner memverifikasi nama database server, menolak cached config dan DB_URL, serta memaksa env pengujian ke proses PHPUnit. Akun dibuat dengan factory sintetis, upload memakai fake storage. Database aplikasi dan `.env` tidak diubah. Folder lokal sebelumnya `docs/checkpoint/` tetap dipertahankan.

## Perintah dan hasil nyata

Hasil terbaru setelah perbaikan:

| Perintah | Tes PASS | Assertion | Gagal/error/skip | Exit | Bukti run terpisah |
| --- | ---: | ---: | --- | ---: | --- |
| `php scripts/run-bulan-tests.php new` | 72 | 2774 | 0 / 0 / 0 | 0 | [log](evidence/runs/20261011-194601459725-new-4d0666db/new.txt), [JUnit](evidence/runs/20261011-194601459725-new-4d0666db/new.xml), [manifest](evidence/runs/20261011-194601459725-new-4d0666db/manifest.json) |
| `php scripts/run-bulan-tests.php regression` | 86 | 689 | 0 / 0 / 0 | 0 | [log](evidence/runs/20261011-194633102201-regression-d0f79478/regression.txt), [JUnit](evidence/runs/20261011-194633102201-regression-d0f79478/regression.xml), [manifest](evidence/runs/20261011-194633102201-regression-d0f79478/manifest.json) |
| `php scripts/run-bulan-tests.php all` | 236 | 3686 | 0 / 0 / 0 | 0 | [log](evidence/runs/20261011-194651609320-all-46ac323f/all.txt), [JUnit](evidence/runs/20261011-194651609320-all-46ac323f/all.xml), [manifest](evidence/runs/20261011-194651609320-all-46ac323f/manifest.json) |

Tes baru sekarang mencakup 64 kasus Feature dan 8 kasus Unit kontrol assertion. Rinciannya: AccessIntegrationTest 38/155 assertion, IdorIntegrationTest 18/2376, Week6RegressionTest 8/226, JsonPrivacyAssertionTest 8/17. Total full = baseline 164/912 + tambahan 72/2774 = 236/3686. Subset antar-run tidak dijumlahkan. Unit kontrol menguji kemampuan helper mendeteksi payload bocor; bukan mock endpoint aplikasi atau pengganti tes integrasi.

### Bukti historis sebelum perbaikan

Artefak berikut tetap utuh dan merupakan hasil revisi sebelumnya, bukan metrik terbaru:

| Perintah | Tes PASS | Assertion | Gagal/error/skip | Exit | Bukti |
| --- | ---: | ---: | --- | ---: | --- |
| `php scripts/run-bulan-tests.php baseline` | 164 | 912 | 0 / 0 / 0 | 0 | [log awal](evidence/baseline.txt), [JUnit](evidence/baseline.xml) |
| `php scripts/run-bulan-tests.php new` | 64 | 1547 | 0 / 0 / 0 | 0 | [log baru](evidence/new.txt), [JUnit](evidence/new.xml) |
| `php scripts/run-bulan-tests.php feature` | 227 | 2458 | 0 / 0 / 0 | 0 | [log Feature](evidence/feature.txt), [JUnit](evidence/feature.xml) |
| `php scripts/run-bulan-tests.php regression` | 86 | 689 | 0 / 0 / 0 | 0 | [log regresi](evidence/regression.txt), [JUnit](evidence/regression.xml) |
| `php scripts/run-bulan-tests.php all` | 228 | 2459 | 0 / 0 / 0 | 0 | [log lengkap](evidence/all.txt), [JUnit](evidence/all.xml) |

Pada revisi historis, full terdiri atas baseline 164 + tes baru 64. Data provider dihitung sebagai kasus tersendiri oleh PHPUnit. Baseline dijalankan sebelum kelas audit dibuat; seleksi `baseline` pada runner kini mengecualikan empat kelas audit (termasuk JsonPrivacyAssertionTest) agar perbandingan 164 tes lama bisa diulang. Run terbaru `all` juga mencakup seluruh tes Feature yang relevan; tidak ada skenario lama yang dihapus.

Perintah tambahan: `php artisan route:list --path=api --except-vendor` menunjukkan 15 route; Pint dijalankan hanya pada tujuh file PHP pekerjaan ini dan `--test` PASS. PHP lint file tambahan PASS. `git diff --check` PASS; pemeriksaan whitespace tambahan pada file baru dilakukan karena file untracked tidak dicakup oleh diff biasa. Rincian pemeriksaan akhir disimpan dalam [static-checks.txt](evidence/static-checks.txt).

Pemeriksaan statis tindak lanjut dibedakan dari bukti historis tersebut: lima file PHP yang dibuat/diubah melalui Pint dan PHP lint; diff/whitespace, preservasi file, manifest, dan hash artefak dicatat dalam [followup-static-checks.txt](evidence/followup-static-checks.txt).

## Ketertelusuran regresi Week 6

Sumber kontrak: routes/controller/Requests/Resources aktual, `docs/api.md`, dua laporan minggu-06, dan commit Week 6 yang dicantumkan di test-plan. Kontrak lama tidak ditebak dari simulasi BREAK.

| Kelompok tes lama | Kasus PASS | Assertion | Cakupan |
| --- | ---: | ---: | --- |
| AuthApiTest | 5 | 21 | login, error, me, revoke token |
| CourseApiTest | 8 | 33 | role, scope kelas, detail/count/pagination |
| MaterialApiTest | 7 | 27 | scope/pagination, guest, role |
| AssignmentApiTest | 37 | 271 | CRUD, scope, published/filter, validasi/byte limit |
| SubmissionApiTest | 8 | 28 | submit/list, ownership kelas, draft/duplikat/deadline |
| GradeApiTest | 4 | 22 | pemberian/revisi nilai, role/pengampu, validasi |
| NotificationApiTest | 4 | 36 | list/pagination, ownership, mark read, guest |
| ApiValidationFormatTest | 5 | 25 | format error standar |
| **Subtotal tes lama** | **78** | **463** | Semua PASS tanpa perubahan ekspektasi |
| Week6RegressionTest (tambahan) | 8 | 226 | token sungguhan/revoked, generic login, throttle 5/60, lifecycle, pagination, draft/count, validasi, tidak menyimpan upload ditolak |
| **Total regresi** | **86** | **689** | Semua PASS |

## Temuan, kegagalan, dan tindak lanjut

Run historis dan run final tindak lanjut semuanya PASS. Run awal selama implementasi tindak lanjut sempat gagal dan tetap disimpan, sebagaimana dijelaskan di bawah. Tidak ada bug aplikasi terkonfirmasi dalam cakupan yang diuji, sehingga tidak dilakukan perbaikan kode aplikasi. Ini bukan klaim seluruh aplikasi bebas bug.

### Resolusi temuan audit independen

| Temuan | Resolusi | Bukti | Status |
| --- | --- | --- | --- |
| M01 | AssertsPrivateResponse membaca JSON yang didekode dan memeriksa seluruh key/nilai string bersarang; API 403 juga mengikuti exact error contract tanpa data. HTML tetap diperiksa dalam bentuk mentah dan ter-escape. | IdorIntegrationTest memakai helper; 8 kontrol JsonPrivacyAssertionTest, termasuk reproduksi path ter-escape, key, nested value, pesan, Unicode, quotes, dan scalar JSON. | CLOSED / PASS |
| M02 | Database MySQL lokal `kampuslms_uts_demo` tersedia; seluruh migration dan BulanAuditDemoSeeder berhasil, exit 0. ID/relasi user/course/materi/tugas/submission/notifikasi serta berkas demo diperiksa read-only. | [Bukti persiapan M02](evidence/demo-preparation-20261011.md), [hasil read-only](evidence/demo-readonly-verification-20261011.json). | CLOSED / PASS |
| L01 | Kontrol positif memeriksa ID/FK/identitas di JSON, ID relasi/uploader/student, model serta koleksi pada view web, marker resource sendiri, dan tidak adanya marker target asing. | 18 dataset IdorIntegrationTest / 2376 assertion. | CLOSED / PASS |
| L02 | Provider guest web memakai placeholder yang diisi ID fixture aktual, dengan assertModelExists sebelum request. | 8 dataset guest web; redirect /login dan snapshot DB tetap. | CLOSED / PASS |
| L03 | Setiap run mendapat folder timestamp-microsecond/stage/random suffix; log dibuat eksklusif, JUnit dan manifest disimpan dalam folder itu. Manifest mencatat command, exit, waktu, hash source/config dan log/JUnit. | Empat folder run termasuk run gagal awal; hash artefak cocok dan 83 file yang dipantau (termasuk bukti historis) tidak berubah. | CLOSED / PASS |

Tidak mengubah controller, route, policy, middleware, model, migration, phpunit.xml, atau tes lama anggota lain. File tindak lanjut: AccessIntegrationTest, IdorIntegrationTest, AssertsPrivateResponse, JsonPrivacyAssertionTest, runner, dan dokumentasi terkait.

### Kegagalan awal tindak lanjut yang dikoreksi

Run `20261011-194440685217-new-39e929fd`: 72 tes, 30082 assertion, 6 failure, 0 error/skip, exit 1 (66 kasus berhasil). [Transcript gagal](evidence/runs/20261011-194440685217-new-39e929fd/new.txt), [JUnit](evidence/runs/20261011-194440685217-new-39e929fd/new.xml), [manifest](evidence/runs/20261011-194440685217-new-39e929fd/manifest.json) tetap tersedia.

Penyebabnya ekspektasi tes baru: empat kasus menerapkan exact error contract khusus `/api/*` ke request JSON route web, dan dua kasus mengharapkan redirect pembaruan materi ke index, padahal kontrak aktual menuju detail. Koreksi membatasi exact error contract ke route API dan memakai redirect aktual `dosen.materials.show`; pemeriksaan decoded JSON pada route web tetap aktif. Helper juga mengumpulkan seluruh key/nilai sebelum satu pemeriksaan per secret, sehingga tidak mengulang assertion untuk tiap elemen trace. Skenario/otorisasi aplikasi dan tes lama tetap dipertahankan. Run final `new`, `regression`, `all` sesudah koreksi tidak gagal.

Risiko konfigurasi pengujian yang diantisipasi: env PHPUnit biasanya tidak mengganti variabel yang sudah diwariskan shell. Runner memaksa konfigurasi tes dan mengecek database aktual sebelum RefreshDatabase. Penyesuaian ini hanya pada alat pengujian; phpunit.xml dan perilaku aplikasi tetap sama.

Perbedaan validasi web/API `max_score` dicatat di test-plan dan tetap mengikuti implementasi: web 0–100, API create/update 1–100. Penolakan ID course dalam payload create mengikuti exists validation 422, sedangkan ID route yang tidak ada 404. Tidak ada ekspektasi tes lama yang dilemahkan.

## Infrastruktur demo dan batas pelaksanaan presentasi

Pengguna menjalankan `php scripts/prepare-bulan-demo.php` pada database demo lokal dengan password di memori, kemudian melaporkan exit 0 dan seluruh migration/seeding sukses. Agen tidak mengulang seeder pada fixture yang sudah ada; verifikasi SELECT dan keberadaan berkas pada 11 Oktober 2026 pukul 20:05:20 WITA berhasil dengan exit 0. Kelima akun, tiga kelas, dua materi, tiga tugas, empat submission, empat UUID notifikasi, serta seluruh 12 migration cocok dengan hasil yang dilaporkan. Tabel ID aktual tercantum di [bukti M02](evidence/demo-preparation-20261011.md) dan [panduan demo](uat-demo-script.md).

Status BLOCKED/exit 2 pada pemeriksaan terdahulu tetap berada dalam artefak historis, tetapi bukan status kesiapan saat ini. Password/token tidak dicantumkan. Pembersihan variabel password pada session terminal asal dilaporkan pengguna; agen tidak mengklaim memeriksa memori session tersebut.

| Aktivitas | Status aktual | Alasan/tindak lanjut |
| --- | --- | --- |
| `php scripts/prepare-bulan-demo.php` | VERIFIED / PASS, exit 0 | Berhasil dijalankan pengguna pada `kampuslms_uts_demo`; data hasilnya juga diverifikasi agen secara read-only. |
| Migration/seeding data demo penuh | VERIFIED / PASS | Seluruh 12 migration tercatat; lima akun, relasi/resource, UUID notifikasi, dan empat berkas submission terbentuk sesuai seeder. |
| Demo manual empat profil/browser dan API server nyata | READY FOR UTS DEMO / PENDING PRESENTATION | Infrastruktur data dan panduan siap; presentasi interaktif belum dilaksanakan dalam sesi ini. |
| Screenshot/rekaman demo | NOT RUN | Tidak dibuat dan tidak diklaim tersedia. |
| Race/concurrency, performa, browser CSRF, deployment, download file | NOT VERIFIED | Di luar cakupan Feature audit ini. |

Tidak ada tes otomatis yang skip atau BLOCKED. Tidak menjalankan skrip lama `scripts/test-api.sh` karena membutuhkan server/akun/token manual; ekuivalen aturan akses diuji melalui Feature test dengan data terisolasi. Tidak dibuat endpoint yang tidak ada.

## Bukti dan review akhir

- [Rencana dan kontrak endpoint](test-plan.md).
- [Matriks IDOR lengkap](idor-test-matrix.md): 18 kasus identitas dua arah + referensi tambahan ID/FK tidak ditemukan; hasil respons, tidak bocor, serta preservasi DB/file.
- [Skenario demo UTS](uat-demo-script.md): persiapan terpisah, akses sukses/gagal, kedua pasangan IDOR, API Week 6, tes otomatis, bukti, dan pemulihan.
- [Penutupan M02](evidence/demo-preparation-20261011.md): hasil persiapan terminal pengguna dan verifikasi database/berkas read-only oleh agen; ID riil siap dipakai dalam presentasi.
- [Pemeriksaan finalisasi](evidence/finalization-checks-20261011.txt): git diff/whitespace, validitas tautan, serta kecocokan hash source dan hasil full suite 236/3686.
- `evidence/`: bukti historis awal tetap tersedia; bukti tindak lanjut berada di `evidence/runs/<run-id>/` dengan transcript, JUnit, dan manifest. Nama/folder run tercetak pada runner. Tidak ada password/token autentikasi di dalam artefak hasil ini.
- Tindak lanjut mengubah file pekerjaan Bulan yang masih untracked dan menambahkan helper/kontrol Unit. Tes lama anggota lain, kode aplikasi, konfigurasi, dan perubahan lokal lain tidak dihapus/ditimpa. Belum ada commit/push; hasil siap diperiksa pengguna.
- Finalisasi ini hanya memperbarui dokumentasi dan menambahkan bukti data demo/pemeriksaan akhir. Tidak mengulang suite pada tahap dokumentasi; metrik 236 tes / 3686 assertion / 0 failure/error/skip tetap berasal dari log/JUnit run terakhir yang terverifikasi dan sesuai hash source saat ini. Keputusan **PASS** berlaku untuk cakupan audit dan kesiapan infrastruktur; presentasi/rekaman tetap pending.
