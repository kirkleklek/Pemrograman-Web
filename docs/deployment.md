# Deployment Documentation

Dokumentasi deployment aplikasi KampusLMS ke environment production.

> **Status:** Draft — menunggu deployment aplikasi oleh Devin.  
> Bagian bertanda TODO harus dilengkapi setelah deployment berhasil.

## 1. Server Specification

- Hosting platform: Railway
- Environment: `production`
- Database service: MySQL
- MySQL version: 8.4.13
- Laravel application service: TODO — menunggu deployment Devin
- Production URL: TODO
- PHP version: TODO — verifikasi pada service production

## 2. Environment Configuration

Laravel production menggunakan MySQL yang disediakan oleh Railway.

Atur environment variables berikut pada **Laravel application service**, bukan pada MySQL service:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=<production-secret-key>

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
```

Catatan keamanan:
- Jangan memasukkan `.env`, password database, atau `APP_KEY` ke Git.
- Gunakan Railway Variables untuk menyimpan konfigurasi rahasia.
- Gunakan production `APP_KEY` yang dibuat khusus untuk deployment.
- `APP_DEBUG` harus `false` pada production.
- `SESSION_SECURE_COOKIE` harus `true` ketika aplikasi sudah menggunakan HTTPS.

## 3. Initial Database Setup

Setelah Laravel service terhubung ke MySQL Railway:

1. Pastikan konfigurasi database sudah benar.
2. Jalankan migrasi production:

```bash
php artisan migrate --force
```

3. Periksa status migrasi:

```bash
php artisan migrate:status
```

4. Periksa informasi aplikasi:

```bash
php artisan about
```

5. Jalankan seeder demo hanya setelah mendapat persetujuan tim dan memastikan akun demo serta password-nya sudah disepakati:

```bash
php artisan db:seed --force
```

> TODO: Catat hasil migrasi, seeding, dan verifikasi tabel setelah deployment.

## 4. Configuration Cache

Setelah environment variables production dikonfigurasi:

```bash
php artisan config:cache
```

Verifikasi:

```bash
php artisan about
```

Pastikan environment dan koneksi database menunjukkan konfigurasi production yang benar.

## 5. Security Verification

Checklist keamanan:

- [x] `.env` tidak dilacak oleh Git.
- [x] Tidak ditemukan pemanggilan `env()` di file PHP di luar direktori `config/`.
- [x] Konfigurasi session mendukung secure cookie dan HTTP-only.
- [ ] `APP_ENV=production` terverifikasi pada server.
- [ ] `APP_DEBUG=false` terverifikasi pada server.
- [ ] `APP_KEY` production sudah diatur sebagai secret.
- [ ] Database production menggunakan MySQL Railway.
- [ ] `SESSION_SECURE_COOKIE=true` terverifikasi pada server.
- [ ] HTTPS aktif dan dapat diakses.
- [ ] Bukti output `curl` production ditambahkan.

### HTTPS and HTTP Response

TODO — setelah domain production tersedia, jalankan:

```bash
curl -I https://<production-domain>
```

Simpan output aktual di bagian ini sebagai bukti HTTPS dan respons aplikasi.

## 6. Redeployment

TODO — lengkapi setelah Devin mengonfirmasi proses deployment yang digunakan.

Dokumentasikan:
- Cara melakukan redeploy.
- Cara memeriksa status deployment.
- Cara memeriksa log aplikasi.
- Cara menangani deployment yang gagal.

## 7. Logs and Troubleshooting

Periksa log melalui Railway deployment/service logs.

Masalah yang perlu diperiksa:

| Masalah | Pemeriksaan |
|---|---|
| Database connection gagal | Periksa `DB_HOST`, `DB_PORT`, `DB_DATABASE`, username, dan password pada Laravel service. |
| Tabel belum tersedia | Periksa `php artisan migrate:status`, lalu jalankan migrasi jika diperlukan. |
| Session bermasalah | Periksa `SESSION_DRIVER` dan konfigurasi session database. |
| Error 500 | Periksa application logs dan pastikan `APP_DEBUG=false` tetap digunakan pada production. |
| Config tidak sesuai | Periksa environment variables dan jalankan `php artisan config:cache` setelah perubahan konfigurasi. |

## 8. Production Verification

TODO — isi setelah aplikasi berhasil di-deploy.

- [ ] `php artisan about` menunjukkan environment production.
- [ ] `php artisan migrate:status` menunjukkan migrasi sudah berjalan.
- [ ] Tabel database terverifikasi di MySQL Railway.
- [ ] Pengujian baca dan tulis database berhasil.
- [ ] Akun demo dapat login.
- [ ] Pengujian authorization `test-authz.sh` berhasil.
- [ ] Aplikasi dapat diakses melalui HTTPS dari perangkat lain.
- [ ] Bukti output dan screenshot pengujian ditambahkan.

## 9. Deployment Notes

TODO — catat proses deployment aktual, kendala yang ditemukan, dan solusi yang diterapkan setelah deployment selesai.