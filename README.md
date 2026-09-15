# KampusLMS Kelompok 03

## Deskripsi Proyek

KampusLMS adalah sistem Learning Management System (LMS) yang dikembangkan untuk mendukung kegiatan pembelajaran di lingkungan kampus. Sistem ini dibuat untuk membantu mahasiswa/i dalam kegiatan akademik.

---

## Daftar Anggota Kelompok

| No | Nama | NIM |
|----|------|-----|
| 1 | Devin Raditya P | 10241021 |
| 2 | Dewi Bulan Purnama | 10241023 |
| 3 | Eagan Ferdian | 10241025 |
| 4 | Fabyo Nathanael Suoth | 10241027 |
| 5 | Fariz Daffa Abbiyu Rahmatullah | 10241029 |

---

## Teknologi yang Digunakan

- PHP
- Laravel 12
- MySQL
- Composer
- Node.js dan NPM
- Vite

---

## Cara Instalasi

### 1. Clone Repository

Clone repository proyek ini:

```bash
git clone https://github.com/Devvrp/kampuslms-kelompok-03
```

### 2. Install Dependency PHP

Lakukan instalasi dependency php ini di terminal vscode dan pastikan sudah masuk dalam folder project 
```bash
composer install
```

### 3. Buat .env

Kamu bisa membuat .env dengan menyalin .env.example . Isi sesuai kebutuhan program mulai dari database hingga app key.

### 4. Generate APP Key

generate dengan mengetik perintah ini di powershell vscode
```bash
php artisan key:generate
```

### 5 Atur Setting Database

Kamu bisa isi ini sesuai dengan database yang kamu gunakan, nama database, dan usn serta password. Pastikan sama persis seperti nama database kamu
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kampuslms
DB_USERNAME=root
DB_PASSWORD=
```

### 6. Jalankan Migrate

Migration berguna untuk menambahkan/mengubah table tanpa perlu membuka database. Karena template laravel memberikan migration, maka jalankan :

```bash 
php artisan migrate
```

### 7 Instalasi Dependecy Front end yaitu vite js

Karena di front end kita mengggunakan framework Javascript, maka jalankan :

```bash 
npm install
```

Lalu jalankan menggunakan perintah berikut : 
```bash 
npm run build
```

### 8 Jalankan program

Jika seua sudah selesai, jalankan perintah berikut untuk menjalankan program :

```bash 
php artisan serve
```

kemudian akses via browser lewat link : 
```text
http://127.0.0.1:8000
```
atauapun
```text
http://kampuslms-kelompok-03.test
```

tergantung dengan apa yang anda gunakan, apakah laravel herd ataupun composer.

---
# Cara push

### Cek branch saat ini
```bash
git branch
```

### Ambil update terbaru github
```bash
git pull origin main
```

### Buat branch baru dan langsung pindah ke branch tersebut
```bash
git switch -c nama/bagian yang dikerjain
```

contoh : 
```bash
git switch -c devin/tentang
```

### Cek Perubahan
```bash
git status
```

### Tambahkan semua perubahan
```bash
git add .
```

### Commit perubahan
```bash
git commit -m "feat: tambah halaman tentang"
```
keterangan :

| No | Jenis Commit | Kegunaan | Contoh |
|----|-------------|----------|--------|
| 1 | `feat` | Menambahkan fitur baru | `feat: tambah halaman tentang` |
| 2 | `fix` | Memperbaiki bug atau error | `fix: perbaiki error login` |
| 3 | `docs` | Mengubah dokumentasi | `docs: update README` |
| 4 | `style` | Merapikan tampilan atau format kode | `style: rapikan navbar` |
| 5 | `refactor` | Merapikan struktur kode tanpa menambah fitur | `refactor: rapikan UserController` |
| 6 | `test` | Menambahkan atau mengubah testing | `test: tambah test login` |
| 7 | `chore` | Perubahan konfigurasi atau pekerjaan pendukung | `chore: update konfigurasi project` |
| 8 | `perf` | Meningkatkan performa aplikasi | `perf: optimalkan query mahasiswa` |
| 9 | `build` | Perubahan dependency atau proses build | `build: update vite configuration` |
| 10 | `ci` | Perubahan konfigurasi CI/CD | `ci: tambah GitHub Actions` |

### Push branch ke GitHub
```bash
git push origin devin/tentang
```