# Skenario demo UTS

Durasi perkiraan 15–20 menit. Infrastruktur database/seeder: **VERIFIED / PASS**. Status presentasi interaktif: **READY FOR UTS DEMO / PENDING PRESENTATION**. Feature test otomatis sudah dijalankan; hasilnya ada di [test-results.md](test-results.md). Belum ada screenshot/video presentasi yang diklaim tersedia.

## 1. Persiapan database dan aplikasi

Gunakan terminal PowerShell dari root repository. Dependency `vendor/` dan asset Vite harus tersedia; jika belum, jalankan `composer install`, `npm ci`, `npm run build`. Jangan menjalankan `composer setup`, migrate:fresh, atau seeder default pada database aplikasi untuk demo ini.

Database **kampuslms_uts_demo** sudah dibuat dan disiapkan pada MySQL lokal. `php scripts/prepare-bulan-demo.php` berhasil menjalankan migration/seeder dengan exit 0, sebagaimana dilaporkan pengguna dan dikonfirmasi lewat pemeriksaan data read-only. [Bukti M02](evidence/demo-preparation-20261011.md) memuat sumber bukti serta ID aktual. Untuk presentasi sekarang, gunakan fixture yang tersedia dan lanjut ke konfigurasi server; seeder tidak perlu diulang karena menolak fixture yang sudah ada.

Contoh persiapan di bawah hanya digunakan jika menyiapkan database demo kosong di lingkungan lain. Pastikan namanya **kampuslms_uts_demo**, terpisah dari database aplikasi dan `kampuslms_testing`. Gunakan kredensial koneksi lokal tanpa menampilkan/menyimpannya dalam rekaman. Jika config cache tersedia, periksa konfigurasi lalu jalankan `php artisan config:clear`.

```powershell
$demoSecret = Read-Host 'Password akun sintetis demo (minimal 8 karakter)' -AsSecureString
$env:BULAN_DEMO_PASSWORD = ([pscredential]::new('audit', $demoSecret)).GetNetworkCredential().Password
try {
    php scripts/prepare-bulan-demo.php
} finally {
    Remove-Item Env:\BULAN_DEMO_PASSWORD -ErrorAction SilentlyContinue
    $demoSecret = $null
}
```

Helper memaksa `APP_ENV=testing`, MySQL, database `kampuslms_uts_demo`, serta DB_URL kosong; memeriksa nama database dari server sebelum menjalankan migration non-destruktif. Seeder hanya boleh berjalan pada database tersebut. Catat tabel ID yang dicetak (tidak berisi password/token); jangan menganggap ID selalu 1, 2, dan seterusnya. Fixture yang sudah ada tidak ditimpa.

Di terminal khusus server, gunakan konfigurasi berikut selama demo:

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'mysql'
$env:DB_DATABASE = 'kampuslms_uts_demo'
$env:DB_URL = ''
$env:SESSION_DRIVER = 'file'
$env:CACHE_STORE = 'database'
$env:APP_DEBUG = 'false'
php artisan serve --host=127.0.0.1 --port=8000
```

URL demo: `http://127.0.0.1:8000`. Akun sintetis:

| Aktor | Email | Hubungan |
| --- | --- | --- |
| Mahasiswa A | mahasiswa-a@audit.example.test | AUDIT-A dan AUDIT-SHARED |
| Mahasiswa B | mahasiswa-b@audit.example.test | AUDIT-B dan AUDIT-SHARED |
| Dosen A | dosen-a@audit.example.test | Pengampu AUDIT-A dan AUDIT-SHARED |
| Dosen B | dosen-b@audit.example.test | Pengampu AUDIT-B |
| Admin | admin@audit.example.test | Pengelolaan user/course; bukan izin mengelola tugas/nilai API |

Password adalah nilai privat yang dimasukkan saat persiapan, bukan nilai yang dicantumkan dokumen. Gunakan empat profil browser terpisah untuk A/B mahasiswa/dosen; tambah profil admin bila diperlukan. Login melalui `/login`. Seeder membuat materi link, tugas published berdeadline satu minggu setelah persiapan, submission dengan berkas TXT sintetis yang diterima aturan `file`, dan notifikasi privat. Materi link tidak bergantung pada keberhasilan situs eksternal.

### Acuan ID riil database lokal yang sudah siap

Data berikut diverifikasi read-only pada 11 Oktober 2026. UUID adalah ID notifikasi, bukan token API. Nilai dapat berubah jika database demo di lingkungan lain dibuat ulang; pada database lokal saat ini gunakan nilai berikut.

| Aktor | User ID | Course | Material | Assignment | Submission contoh | Notification UUID |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| Admin | 1 | — | — | — | — | — |
| Mahasiswa A | 2 | 1 | 1 | 1 | 1 | 6faab711-bff4-40e8-bf07-73bfef76b500 |
| Dosen A | 3 | 1 | 1 | 1 | 1 | 0247c897-587c-4cd1-bab5-0e782a2e3e23 |
| Mahasiswa B | 4 | 2 | 2 | 2 | 2 | a59694ce-77b9-4c95-b4c5-354e84826dd4 |
| Dosen B | 5 | 2 | 2 | 2 | 2 | 76076679-fec2-46d8-9688-8e629340643f |

Kelas shared: Course ID **3**, Assignment ID **3**, Submission Mahasiswa A **3**, Submission Mahasiswa B **4**. Dosen A mengampu kelas shared dan kedua mahasiswa terdaftar. Submission 1/3 adalah milik Mahasiswa A; 2/4 milik Mahasiswa B. Kolom dosen menunjukkan submission mahasiswa yang berada dalam kewenangan kelas dosen tersebut.

Untuk contoh PowerShell/API di bawah, siapkan variabel resource berikut; tidak ada credential atau token di dalamnya:

```powershell
$courseA = 1
$courseB = 2
$materialA = 1
$materialB = 2
$assignmentA = 1
$assignmentB = 2
$submissionA = 1
$submissionB = 2
$sharedCourse = 3
$sharedAssignment = 3
$sharedSubmissionA = 3
$sharedSubmissionB = 4
$notificationA = '6faab711-bff4-40e8-bf07-73bfef76b500'
$notificationB = 'a59694ce-77b9-4c95-b4c5-354e84826dd4'
$notificationDosenA = '0247c897-587c-4cd1-bab5-0e782a2e3e23'
$notificationDosenB = '76076679-fec2-46d8-9688-8e629340643f'
```

## 2. Penjelasan fitur

Jelaskan bahwa akses materi/tugas mengikuti kelas, submission mahasiswa bersifat privat, dosen hanya mengelola kelas yang diampu, dan notifikasi mengikuti pemilik. API Week 6 terdiri dari 15 route: autentikasi, course, materi, tugas, submission, penilaian, notifikasi. Tunjukkan `php artisan route:list --path=api --except-vendor`.

## 3. Akses diizinkan

- Mahasiswa A membuka `/mahasiswa/courses/{courseA}/materials`, `/mahasiswa/materials/{materialA}`, `/mahasiswa/assignments/{assignmentA}`, dan `/mahasiswa/submissions/{submissionA}`. Harapan: 200 dengan data kelas sendiri.
- Dosen A membuka `/dosen/courses/{courseA}/materials`, `/dosen/assignments/{assignmentA}`, `/dosen/assignments/{assignmentA}/submissions`, dan `/dosen/submissions/{submissionA}`. Harapan: 200.
- Admin membuka `/admin/users` dan `/admin/courses`. Harapan: 200. ID diambil dari tabel helper, bukan ditebak.

## 4. Akses ditolak dan guest

Di profil yang belum login, buka `/dashboard`: harapan redirect ke `/login`. GET API `/api/v1/courses` tanpa token dengan Accept JSON: harapan 401. Mahasiswa/dosen membuka `/admin/users`: harapan 403 tanpa isi daftar user. Mahasiswa membuka `/dosen/materials/{materialA}`: harapan 403 meskipun anggota kelas A. Simpan hanya status/respons yang sudah aman sebagai bukti manual tambahan.

## 5. IDOR Mahasiswa A dan B

Pada kelas AUDIT-SHARED, gunakan ID submission **shared** dari tabel helper:

1. A membuka `/mahasiswa/submissions/{sharedSubmissionA}` → 200.
2. A mengganti ID menjadi `{sharedSubmissionB}` → 403; catatan, nama berkas, dan email B tidak tampil.
3. B membuka submission shared B → 200, lalu mengganti ke shared A → 403.
4. A mengganti ID materi/tugas ke resource AUDIT-B → 403; B melakukan kebalikan ke AUDIT-A → 403.
5. Tunjukkan bahwa kedua mahasiswa terdaftar di kelas shared. Penolakan langkah 2–3 disebabkan kepemilikan submission, bukan enrollment.

## 6. IDOR Dosen A dan B

1. Dosen A membuka materi/tugas/submission AUDIT-A → 200, lalu mengganti ID ke AUDIT-B → 403.
2. Dosen B melakukan kebalikan → 200 milik B, 403 milik A.
3. Coba form edit `/dosen/materials/{materialB}/edit` dan `/dosen/assignments/{assignmentB}/edit` sebagai A → 403; lakukan kebalikan sebagai B.
4. Untuk perubahan/deletion terlarang dan manipulasi FK, jalankan tes IDOR otomatis melalui runner `new`. Tunjukkan assertion database/berkas dan pasangan dataset A→B/B→A. Bukti ini memeriksa tidak ada perubahan setelah request, bukan hanya tampilan error.

## 7. API Week 6

Gunakan terminal privat untuk login. Tutup panel yang memperlihatkan token atau nilai environment pada rekaman. Script berikut menyimpan token dalam memori dan hanya mencetak nama aktor serta status login:

```powershell
$apiBase = 'http://127.0.0.1:8000/api/v1'
$apiSecret = Read-Host 'Password sintetis demo' -AsSecureString
$apiCredential = ([pscredential]::new('audit', $apiSecret)).GetNetworkCredential().Password
$tokens = @{}
try {
    foreach ($actor in @('mahasiswa-a', 'mahasiswa-b', 'dosen-a', 'dosen-b')) {
        $loginBody = @{ email = "$actor@audit.example.test"; password = $apiCredential } | ConvertTo-Json
        $loginResult = Invoke-RestMethod -Method Post -Uri "$apiBase/auth/login" -ContentType 'application/json' -Body $loginBody
        $tokens[$actor] = $loginResult.data.token
        Write-Output "$actor login: HTTP 200"
    }
} finally {
    $apiCredential = $null
    $apiSecret = $null
    $loginBody = $null
    $loginResult = $null
}

function Get-AuditHeaders([string]$Actor) {
    return @{ Accept = 'application/json'; Authorization = 'Bearer ' + $tokens[$Actor] }
}
function Show-AuditStatus([string]$Method, [string]$Path, [hashtable]$Headers, [string]$Body = '') {
    $request = @{ Method = $Method; Uri = "$apiBase$Path"; Headers = $Headers; UseBasicParsing = $true }
    if ($Body) { $request.Body = $Body; $request.ContentType = 'application/json' }
    try {
        $result = Invoke-WebRequest @request
        Write-Output "$Method $Path -> HTTP $([int]$result.StatusCode)"
    } catch {
        if ($null -eq $_.Exception.Response) { throw }
        Write-Output "$Method $Path -> HTTP $([int]$_.Exception.Response.StatusCode)"
    }
}
```

Gunakan variabel ID riil pada bagian persiapan, lalu jalankan contoh berikut saat presentasi:

```powershell
Show-AuditStatus 'GET' '/courses' @{ Accept = 'application/json' }
Show-AuditStatus 'GET' '/me' (Get-AuditHeaders 'mahasiswa-a')
Show-AuditStatus 'GET' "/courses/$courseA" (Get-AuditHeaders 'mahasiswa-a')
Show-AuditStatus 'GET' "/courses/$courseB" (Get-AuditHeaders 'mahasiswa-a')
Show-AuditStatus 'GET' "/courses/$courseA/materials?page=1" (Get-AuditHeaders 'dosen-a')
Show-AuditStatus 'GET' "/courses/$courseA/assignments?status=published" (Get-AuditHeaders 'mahasiswa-a')
Show-AuditStatus 'GET' "/assignments/$assignmentA/submissions" (Get-AuditHeaders 'dosen-a')
Show-AuditStatus 'GET' "/assignments/$assignmentA/submissions" (Get-AuditHeaders 'mahasiswa-a')
Show-AuditStatus 'PATCH' "/assignments/$assignmentB" (Get-AuditHeaders 'dosen-a') '{"title":"Tidak boleh tersimpan"}'
Show-AuditStatus 'DELETE' "/assignments/$assignmentB" (Get-AuditHeaders 'dosen-a')
Show-AuditStatus 'PUT' "/submissions/$submissionB/grade" (Get-AuditHeaders 'dosen-a') '{"score":80}'
Show-AuditStatus 'GET' '/courses/999999999' (Get-AuditHeaders 'dosen-a')
Show-AuditStatus 'POST' '/assignments' (Get-AuditHeaders 'dosen-a') '{}'
Show-AuditStatus 'GET' '/notifications' (Get-AuditHeaders 'mahasiswa-a')
Show-AuditStatus 'POST' "/notifications/$notificationB/read" (Get-AuditHeaders 'mahasiswa-a')
Show-AuditStatus 'POST' "/notifications/$notificationA/read" (Get-AuditHeaders 'mahasiswa-a')
```

Harapan berurutan: **401, 200, 200, 403, 200, 200, 200, 403, 403, 403, 403, 404, 422, 200, 403, 200**. `$notificationA/B` pada snippet ini adalah UUID notifikasi **mahasiswa**, bukan UUID dosen. Helper hanya menampilkan status; pemeriksaan struktur respons, kebocoran data, serta snapshot DB dilakukan oleh tes otomatis.

Untuk alur sukses create/update/upload/grade/delete, demonstrasikan `Week6RegressionTest::test_assignment_submission_grade_and_delete_lifecycle` dalam source dan JUnit: 201 tugas → PUT/PATCH 200 → upload mahasiswa 201 → list dosen 200 → grade 201 lalu 200 dengan ID sama → delete 204 dan cascade DB. Ini adalah demo otomatis endpoint aktual, tanpa menyalin token ke layar. Tunjukkan pula tes rate limit 5/60 dan token yang ditolak setelah logout.

Setelah selesai, logout masing-masing token melalui helper tanpa menampilkan token:

```powershell
foreach ($actor in @('mahasiswa-a', 'mahasiswa-b', 'dosen-a', 'dosen-b')) {
    Show-AuditStatus 'POST' '/auth/logout' (Get-AuditHeaders $actor)
}
Show-AuditStatus 'GET' '/me' (Get-AuditHeaders 'mahasiswa-a')
$tokens.Clear()
```

Harapan logout 204; GET memakai token yang dicabut 401.

## 8. Eksekusi tes otomatis

Di terminal kedua, runner memaksa database **kampuslms_testing** meskipun terminal server menggunakan database demo. Jangan jalankan suite bersamaan pada database tes yang sama.

```powershell
php scripts/run-bulan-tests.php new
php scripts/run-bulan-tests.php feature
php scripts/run-bulan-tests.php regression
php scripts/run-bulan-tests.php all
git diff --check
```

Harapan hasil sesuai angka terakhir di test-results; exit code 0. Setelah tindak lanjut teknis: new 72; Feature 227; regresi 86; full 236. Delapan kontrol Unit tambahan ikut `new` dan `all`; jumlah Feature tetap 227. Baseline lama dapat dijalankan terpisah dengan `php scripts/run-bulan-tests.php baseline` (mengeluarkan empat kelas audit dari seleksi baseline, bukan menghapus tes). Runner mencetak folder bukti unik per eksekusi dan tidak menimpa bukti historis.

## 9. Menunjukkan bukti

Buka `docs/testing/test-results.md`, `idor-test-matrix.md`, lalu log/JUnit/manifest di `evidence/runs/<run-id>/` yang ditautkan dokumen atau dicetak runner. File statis `evidence/new.txt`, `regression.txt`, `all.txt` merupakan riwayat sebelum perbaikan, bukan hasil terbaru. JUnit `.xml` menunjukkan nama kelas/metode/dataset, jumlah tes/assertion, dan kegagalan jika ada; manifest mengikat hasil dengan hash source/config serta artefak. Tunjukkan [bukti persiapan M02](evidence/demo-preparation-20261011.md) dan [hasil data read-only](evidence/demo-readonly-verification-20261011.json). M02 database/seeder telah CLOSED / PASS; presentasi manual masih pending dan hasilnya harus dicatat setelah dilaksanakan. Jika mengambil screenshot manual, sembunyikan field password, Authorization, respons login/token, dan environment sensitif. Jangan mengklaim screenshot sudah ada pada audit ini.

## 10. Pemulihan dan hasil yang diharapkan

- DB/konfigurasi BLOCKED: periksa layanan MySQL, database khusus, akses akun koneksi, dan cached config. Helper tidak membuat/menghapus database dan tidak memperbaiki konfigurasi secara diam-diam.
- Fixture sudah ada: pakai ID yang sudah disiapkan; untuk menemukan ulang, tampilkan hanya ID/nama/email sintetis lewat pengelola DB. Jangan menjalankan reset pada database aplikasi. Jika perlu latihan ulang, siapkan database demo kosong hanya setelah memastikan nama dan isi database demo tersebut.
- Halaman asset error: jalankan build Vite, ulangi halaman. 403 tak terduga: periksa profil login dan ID dari tabel helper. 404: periksa resource belum terhapus. 429: tunggu sesuai Retry-After sebelum mengulang.
- Deadline lewat: buat tugas baru berdeadline masa depan di database demo melalui dosen pengampu; jangan mengubah aturan aplikasi atau melemahkan tes.
- Tes gagal: simpan output/exit code yang nyata, bedakan bug, konfigurasi, atau fixture; jangan mengubah ekspektasi untuk menutupi kegagalan. Tunjukkan bukti eksekusi terakhir dengan waktu yang jelas.
- Setelah demo, hentikan server dengan Ctrl+C, logout profil browser, bersihkan variabel environment demo pada terminal atau tutup terminal khusus. Password/token tidak disimpan dalam dokumen. Tidak ada commit/push dalam prosedur ini.
