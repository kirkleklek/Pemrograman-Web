# KampusLMS API Documentation

## Base URL

```text
http://kampuslms-kelompok-03.test/api/v1
```

## Authentication

API menggunakan Laravel Sanctum dengan Bearer Token.

Untuk endpoint yang membutuhkan autentikasi, gunakan header:

```text
Accept: application/json
Authorization: Bearer TOKEN
```

Token diperoleh melalui endpoint login.

---

## 1. Login

### POST `/auth/login`

Digunakan untuk login dan mendapatkan Bearer Token.

### Request

```powershell
curl.exe -X POST "http://kampuslms-kelompok-03.test/api/v1/auth/login" -H "Accept: application/json" -d "email=dosen@kampuslms.test" -d "password=password"
```

### Response sukses

```json
{
  "data": {
    "token": "TOKEN",
    "user": {
      "id": 2,
      "name": "Dosen Demo 1",
      "email": "dosen@kampuslms.test",
      "role": "dosen",
      "nim_nip": "198001001",
      "email_verified_at": "2026-09-15T06:59:20+00:00"
    }
  }
}
```

Login menggunakan rate limit **5 request per menit**.

---

## 2. Logout

### POST `/auth/logout`

Menghapus token yang sedang digunakan.

```powershell
curl.exe -X POST "http://kampuslms-kelompok-03.test/api/v1/auth/logout" -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

### Response

```text
204 No Content
```

---

## 3. Current User

### GET `/me`

Mengambil data pengguna yang sedang login.

```powershell
curl.exe "http://kampuslms-kelompok-03.test/api/v1/me" -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

---

## 4. List Courses

### GET `/courses`

Menampilkan mata kuliah sesuai pengguna:

- Dosen: mata kuliah yang diajar.
- Mahasiswa: mata kuliah yang diikuti.

```powershell
curl.exe "http://kampuslms-kelompok-03.test/api/v1/courses" -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

### Response

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 0
  }
}
```

---

## 5. Course Detail

### GET `/courses/{course}`

Menampilkan detail mata kuliah dan jumlah materi serta tugas.

```powershell
curl.exe "http://kampuslms-kelompok-03.test/api/v1/courses/1" -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

---

## 6. Course Materials

### GET `/courses/{course}/materials`

Menampilkan daftar materi pada mata kuliah.

```powershell
curl.exe "http://kampuslms-kelompok-03.test/api/v1/courses/1/materials" -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

Mendukung pagination:

```text
?page=2
```

---

## 7. Course Assignments

### GET `/courses/{course}/assignments`

Menampilkan daftar tugas pada mata kuliah.

```powershell
curl.exe "http://kampuslms-kelompok-03.test/api/v1/courses/1/assignments" -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

Filter berdasarkan status:

```text
?status=published
```

Pagination:

```text
?page=2
```

Contoh:

```powershell
curl.exe "http://kampuslms-kelompok-03.test/api/v1/courses/1/assignments?status=published&page=2" -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

---

## 8. Create Assignment

### POST `/assignments`

Digunakan dosen untuk membuat tugas pada mata kuliah yang diajarnya.

```powershell
curl.exe -X POST "http://kampuslms-kelompok-03.test/api/v1/assignments" -H "Accept: application/json" -H "Authorization: Bearer TOKEN_DOSEN" -d "course_id=1" -d "title=Tugas API" -d "instructions=Kerjakan tugas REST API" -d "due_at=2026-10-10 23:59:00" -d "max_score=100" -d "allow_late=1" -d "status=published"
```

### Response

```text
201 Created
```

---

## 9. Update Assignment

### PUT/PATCH `/assignments/{assignment}`

Digunakan dosen pemilik tugas untuk memperbarui tugas.

```powershell
curl.exe -X PUT "http://kampuslms-kelompok-03.test/api/v1/assignments/1" -H "Accept: application/json" -H "Authorization: Bearer TOKEN_DOSEN" -d "title=Tugas API Revisi" -d "status=published"
```

### Response

```text
200 OK
```

---

## 10. Delete Assignment

### DELETE `/assignments/{assignment}`

Menghapus tugas oleh dosen pemilik.

```powershell
curl.exe -X DELETE "http://kampuslms-kelompok-03.test/api/v1/assignments/1" -H "Accept: application/json" -H "Authorization: Bearer TOKEN_DOSEN"
```

### Response

```text
204 No Content
```

---

## 11. List Submissions

### GET `/assignments/{assignment}/submissions`

Menampilkan submission mahasiswa pada tugas tertentu.

Akses: dosen pemilik tugas.

```powershell
curl.exe "http://kampuslms-kelompok-03.test/api/v1/assignments/1/submissions" -H "Accept: application/json" -H "Authorization: Bearer TOKEN_DOSEN"
```

### Response

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 0
  }
}
```

---

## 12. Submit Assignment

### POST `/assignments/{assignment}/submissions`

Digunakan mahasiswa yang terdaftar pada mata kuliah untuk mengumpulkan tugas.

Request menggunakan multipart form.

```powershell
curl.exe -X POST "http://kampuslms-kelompok-03.test/api/v1/assignments/1/submissions" -H "Accept: application/json" -H "Authorization: Bearer TOKEN_MAHASISWA" -F "file=@tugas.pdf" -F "note=Tugas sudah selesai"
```

### Response

```text
201 Created
```

---

## 13. Grade Submission

### PUT `/submissions/{submission}/grade`

Digunakan dosen pemilik mata kuliah untuk memberikan nilai submission.

Parameter:

```text
score
feedback
```

### Request

```powershell
curl.exe -X PUT "http://kampuslms-kelompok-03.test/api/v1/submissions/226/grade" -H "Accept: application/json" -H "Authorization: Bearer TOKEN_DOSEN" -H "Content-Type: application/json" -d "{\"score\":85,\"feedback\":\"Sudah baik.\"}"
```

### Penilaian pertama

```text
201 Created
```

### Penilaian ulang

```text
200 OK
```

Penilaian menggunakan mekanisme upsert sehingga satu submission hanya memiliki satu grade.

---

## 14. List Notifications

### GET `/notifications`

Menampilkan notifikasi milik pengguna yang sedang login.

```powershell
curl.exe "http://kampuslms-kelompok-03.test/api/v1/notifications" -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

### Response

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "total": 0
  }
}
```

---

## 15. Mark Notification as Read

### POST `/notifications/{notification}/read`

Menandai notifikasi milik pengguna sebagai sudah dibaca.

```powershell
curl.exe -X POST "http://kampuslms-kelompok-03.test/api/v1/notifications/NOTIFICATION_ID/read" -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

### Response

```json
{
  "data": {
    "id": "NOTIFICATION_ID",
    "type": "TestNotification",
    "data": {
      "message": "Notifikasi pengujian Week 6"
    },
    "read_at": "2026-10-06T04:37:00+00:00",
    "created_at": "2026-10-06T04:34:58+00:00"
  }
}
```

---

# HTTP Status Codes

| Status | Keterangan |
|---|---|
| 200 | Request berhasil |
| 201 | Resource berhasil dibuat |
| 204 | Berhasil tanpa response body |
| 401 | Belum login atau token tidak valid |
| 403 | Sudah login tetapi tidak memiliki akses |
| 404 | Resource tidak ditemukan |
| 422 | Data request tidak valid |
| 429 | Rate limit terlampaui |

---

# Validation Error

Response validasi menggunakan format:

```json
{
  "message": "Data yang diberikan tidak valid.",
  "errors": {
    "score": [
      "Nilai wajib diisi."
    ]
  }
}
```

---

# Authorization

Endpoint API yang membutuhkan autentikasi menggunakan Laravel Sanctum.

Rate limit:

```text
Endpoint umum: 60 request/menit
Login: 5 request/menit
```

Token asli tidak dicantumkan dalam dokumentasi.