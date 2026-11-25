# Deploy Backend Laravel ke HostData.id Starter (GitHub Actions → FTP)

Panduan ini men-deploy hanya folder `backend/` dari repo monorepo:

```text
https://github.com/mpratarlokananta/Muhammad--Pratar--Lokananta
```

ke **HostData.id Starter (DirectAdmin shared hosting)**. Frontend (`frontend/`) tetap di-deploy terpisah.

## Ringkasan

HostData Starter adalah shared hosting DirectAdmin **tanpa SSH/Terminal**. Composer tidak bisa dijalankan di server, jadi:

- GitHub Actions yang build dependency (`composer install --no-dev`) dan meng-upload `vendor/` lewat FTP.
- Kode Laravel disimpan di folder `laravel/` (di luar document root).
- Document root `public_html/` hanya berisi `index.php`, `.htaccess`, dan script migrasi sekali pakai.
- Folder runtime (`storage/app/public`, `storage/framework/*`, `storage/logs`) **tidak ikut dihapus** saat FTP sync, jadi gambar hasil upload tidak hilang setiap deploy.
- Tidak pakai `php artisan storage:link`; Laravel 13 menyajikan disk `public` lewat route bawaan `/storage/{path}` (diaktifkan lewat `serve => true` di `config/filesystems.php`).

> Laravel 13 membutuh **PHP ≥ 8.3**. Workflow default memakai PHP **8.4**. Pastikan hosting juga 8.3/8.4.

---

## 1. Cek versi PHP di DirectAdmin

Coba menu ini (nama bisa beda sedikit tergantung skin DirectAdmin):

1. Login ke DirectAdmin.
2. Buka **Advanced Features → Select PHP Version** atau **Software → PHP Selector**.
3. Lihat versi yang aktif dan opsi yang tersedia. Pilih **8.4** (atau **8.3**).

Kalau menu itu tidak ada, cara alternatif tanpa menebak:

1. Buka **Advanced Features → File Manager**, masuk ke folder `public_html`.
2. Buat file `info.php` dengan isi:

   ```php
   <?php phpinfo();
   ```

3. Buka `https://domain-anda/info.php` di browser.
4. Lihat baris **PHP Version**. Setelah itu **hapus file `info.php`**.

- **8.3 / 8.4** → lanjut.
- **≤ 8.2** → jangan lanjut deploy. Buka tiket HostData untuk mengaktifkan PHP 8.3, atau minta saya menurunkan versi Laravel.
- Kalau tidak menemukan menu dan tidak bisa membuat file, tiket HostData saja: *“Mohon informasi versi PHP yang tersedia untuk paket Starter dan cara menggantinya ke PHP 8.3/8.4.”*

Kamu hanya perlu **melaporkan nomor versinya** di sini. Jangan kirim password.

---

## 2. Siapkan domain / subdomain untuk API

Disarankan memakai subdomain agar frontend dan API terpisah:

1. Pastikan domain utama sudah mengarah ke HostData (nameserver di registrar sudah diganti). Tunggu propagasi DNS.
2. Di DirectAdmin: **Account Manager → Subdomain Management**.
3. Isi subdomain `api`, pilih domain utama, lalu **Create**.
4. DirectAdmin akan membuat document root:

   ```text
   /domains/api.domain-anda.com/public_html
   ```

5. Buka **SSL Certificates → Free & automatic certificate from Let's Encrypt**, lalu aktifkan untuk `api.domain-anda.com`. API harus diakses lewat `https://`.

Kalau belum punya nama domain, ambil nama domain dari email welcome HostData. Nanti bisa diganti lewat GitHub Secret `API_DOMAIN` + `ENV_FILE`.

---

## 3. Buat database MySQL

1. DirectAdmin → **Account Manager → MySQL Management**.
2. Buat database, misalnya `portfolio`.
3. Tambah user baru, misalnya `portuser`, dengan password kuat.
4. Beri hak **ALL** hanya untuk database `portfolio` tersebut.
5. Catat persis nama database dan **nama user lengkap** yang ditampilkan DirectAdmin. Di DirectAdmin biasanya menjadi seperti `namauser_portuser` — pakai persis itu di `.env`, bukan cuma `portuser`.

Kredensial ini akan masuk ke GitHub Secret `ENV_FILE`, bukan diketik di chat.

---

## 4. Buat akun FTP untuk GitHub Actions

1. DirectAdmin → **Account Manager → FTP Management** (atau **FTP Accounts**).
2. Klik **Create FTP Account**.
3. Isi:
   - **Username**: `deploy`
   - **Password**: buat password panjang/unik (simpan di GitHub Secret, jangan di chat)
   - **Home Directory**: pilih **root domain** `/domains/api.domain-anda.com`, **bukan** `/public_html`
   - **Quota**: unlimited / sesuai kebutuhan
4. Simpan. DirectAdmin mungkin menampilkan username lengkap seperti `namauser_deploy` — pakai persis yang ditampilkan.
5. FTP server biasanya `ftp.domain-anda.com` atau IP yang ada di email welcome. Port `21`, protokol `ftp`.

Kenapa home directory harus root domain? Workflow meng-upload dua folder bersaudara:

```text
/domains/api.domain-anda.com/
├── laravel/       <- aplikasi + vendor + .env
└── public_html/   <- index.php + .htaccess + deploy.php
```

---

## 5. Isi GitHub Secrets dan Variables

Di repo GitHub: **Settings → Secrets and variables → Actions**.

### Secrets

| Nama | Isi contoh | Catatan |
| --- | --- | --- |
| `FTP_SERVER` | `ftp.contoh.com` | Boleh IP. Port 21. |
| `FTP_USERNAME` | `namauser_deploy` | Username lengkap dari DirectAdmin. |
| `FTP_PASSWORD` | `<password FTP>` | Jangan pernah paste di chat/issue. |
| `FTP_REMOTE_PATH` | `./` | **Harus diakhiri `/`**. Kalau FTP home bukan root domain, isi `/domains/api.contoh.com/`. |
| `API_DOMAIN` | `api.contoh.com` | Tanpa `https://`. Boleh juga diisi dengan scheme; workflow membersihkannya. |
| `ENV_FILE` | seluruh isi `.env` | Multiline. Template di bawah. |

### Variables

| Nama | Isi | Catatan |
| --- | --- | --- |
| `PHP_VERSION` | `8.4` | Opsional. Default `8.4`. Set `8.3` kalau hosting hanya punya 8.3. |

generate `APP_KEY` sekali di lokal:

```powershell
cd backend
php artisan key:generate --show
```

Salin hasil `base64:...` ke `ENV_FILE`.

### Template `ENV_FILE`

```dotenv
APP_NAME="Portfolio API"
APP_ENV=production
APP_KEY=base64:PASTE-HASIL-KEY-GENERATE-DI-SINI
APP_DEBUG=false
APP_URL=https://api.contoh.com
FRONTEND_URL=https://frontend-contoh.vercel.app
CORS_ALLOWED_ORIGINS=https://frontend-contoh.vercel.app

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=portfolio
DB_USERNAME=namauser_portuser
DB_PASSWORD=password-database-anda

SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

ADMIN_USERNAME=admin
ADMIN_PASSWORD=GANTI-DENGAN-PASSWORD-KUAT
```

Catatan:

- Workflow akan **menimpa** `APP_URL` dengan `https://<API_DOMAIN>` dan memaksa `FILESYSTEM_DISK=public`. Jadi keduanya tidak boleh salah.
- `CORS_ALLOWED_ORIGINS` harus persis origin frontend (tanpa trailing slash). Boleh beberapa origin dipisah koma.
- Seed admin dijalankan setiap deploy, jadi `ADMIN_PASSWORD` akan selalu diset ulang dari nilai ini.

---

## 6. Alur deploy otomatis

Setiap push ke `main` yang menyentuh `backend/` atau workflow ini:

1. Checkout repo.
2. Setup PHP sesuai variable `PHP_VERSION`.
3. Tulis `backend/.env` dari secret `ENV_FILE`.
4. `composer install --no-dev --optimize-autoloader`.
5. `php artisan optimize` (config/route/view cache).
6. Susun `build/laravel` dan `build/public_html`.
7. Buat `deploy.php` dengan token random sekali pakai.
8. Upload ke FTP.
9. Panggil `https://<API_DOMAIN>/deploy.php` dengan token → `migrate --force` + seed admin.
10. `deploy.php` **menghapus dirinya sendiri** setelah sukses.
11. Cek `https://<API_DOMAIN>/up`.

Karena `vendor/` dan `.env` ikut ter-upload, hosting Starter tidak perlu SSH atau Composer.

Untuk deploy manual: tab **Actions → Deploy Backend ke HostData (FTP) → Run workflow**.

---

## 7. Verifikasi

```powershell
curl https://api.contoh.com/up
curl https://api.contoh.com/api/v1/public/projects
curl -X POST https://api.contoh.com/api/v1/auth/login `
  -H "Content-Type: application/json" `
  -d '{"username":"admin","password":"PASSWORD-DARI-ENV_FILE"}'
```

Frontend:

```dotenv
LARAVEL_API_URL=https://api.contoh.com/api/v1
```

Gambar yang di-upload panel admin akan tersedia di:

```text
https://api.contoh.com/storage/projects/<nama-file>
```

---

## 8. Masalah umum

| Gejala | Penyebab & solusi |
| --- | --- |
| `ENV_FILE belum diisi` / `APP_KEY tidak valid` | Secret belum dibuat atau `APP_KEY` bukan `base64:...`. Generate ulang dengan `php artisan key:generate --show`. |
| `composer install` gagal karena `php ^8.3` | `PHP_VERSION` di GitHub Actions ≤ 8.3 atau hosting ≤ 8.2. Set variable `8.4` dan cek versi PHP hosting. |
| 500 setelah upload | Cek versi PHP hosting, `vendor/` ter-upload, dan `bootstrap/cache` tidak terhapus. Lihat `storage/logs/laravel.log` via File Manager. |
| 404 semua `/api/...` | `FTP_REMOTE_PATH` salah, atau `.htaccess` tidak masuk `public_html`. Pastikan FTP home = root domain dan `server-dir` diakhiri `/`. |
| `deploy.php` 404 / gagal dipanggil | DNS belum aktif, SSL belum terpasang, atau `deploy.php` tidak ter-upload. Tunggu propagasi lalu **Run workflow** ulang. |
| `SQLSTATE... access denied` | `DB_USERNAME` harus nama lengkap dari DirectAdmin (mis. `namauser_portuser`), bukan cuma `portuser`. |
| Login admin gagal | `ADMIN_USERNAME` / `ADMIN_PASSWORD` di `ENV_FILE` berubah. Setelah mengubah secret, jalankan workflow ulang. |
| CORS error di browser | `CORS_ALLOWED_ORIGINS` harus persis `https://domain-frontend`, tanpa trailing slash. |
| Gambar 404 | Pastikan `FILESYSTEM_DISK=public` dan `config/filesystems.php` masih punya `serve => true` pada disk `public`. Jangan buat symlink `public/storage`. |
| Gambar hilang setelah deploy | Jangan mengubah `exclude` di workflow; folder `laravel/storage/app/public/**` sengaja dikecualikan dari FTP sync. |
| Perubahan `.env` tidak berlaku | Setelah mengubah secret `ENV_FILE`, jalankan ulang workflow (config di-cache saat build). |

---

## 9. Catatan

- Dokumen Laravel Cloud tetap ada di `backend/DEPLOY-CLOUD.md` kalau kamu kembali ke Laravel Cloud.
- HostData Starter **tidak punya SSH/Terminal** (fitur itu mulai paket Developer), jadi semua perintah dijalankan di GitHub Actions, bukan di server.
- Kalau HostData hanya menyediakan PHP 8.2, beri tahu saya — keputusannya: minta upgrade PHP atau downgrade Laravel ke versi yang kompatibel.
