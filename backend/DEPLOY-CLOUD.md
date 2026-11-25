# Deploy Backend Laravel ke Laravel Cloud (dari GitHub Repo)

Panduan ini men-deploy folder `backend/` dari repo monorepo:

```text
https://github.com/mpratarlokananta/Muhammad--Pratar--Lokananta
```

ke **Laravel Cloud**. Frontend (`frontend/`) tetap di-deploy terpisah (mis. Vercel), lalu dihubungkan lewat URL API.

## 0. Yang sudah disiapkan di repo

- Laravel Cloud mendukung monorepo: aplikasi ini dibuat dengan **root directory = `backend`**.
- Route health `/up` sudah ada di `bootstrap/app.php`, jadi Laravel Cloud bisa mendeteksinya otomatis.
- `AdminUserSeeder` membaca kredensial lewat `config('admin.*')` (bukan `env()`), agar tetap bekerja saat config di-cache saat deploy.
- Upload gambar memakai **default filesystem disk** (bukan hardcode `public`) dan `Storage::url()`, sehingga bisa diarahkan ke Laravel Cloud Object Storage / bucket S3.
- Paket `league/flysystem-aws-s3-v3` sudah ditambahkan ke `composer.json` + `composer.lock` (prasyarat bucket Laravel Cloud).

> **Penting:** Laravel Cloud clone kode dari GitHub, bukan dari folder lokal. Pastikan semua perubahan sudah di-`commit` dan di-`push` ke branch `main` sebelum deploy.

## 1. Buat application di Laravel Cloud

1. Buka https://cloud.laravel.com → **New application**.
2. Pilih **Connect to GitHub**, lalu berikan akses ke repo `Muhammad--Pratar--Lokananta`.
3. Pilih repo tersebut, nama application (mis. `portfolio-api`), branch `main`, dan region. Untuk Indonesia biasanya pilih region Asia/Singapore — **database harus satu region dengan aplikasi**.
4. Klik **Create Application**.
5. Karena ini monorepo, Laravel Cloud akan menampilkan daftar folder top-level. Pilih **`backend`** sebagai root directory. Semua build command, deploy command, dan process akan dijalankan di folder itu.

## 2. Runtime dan health check

1. Buka environment → **Settings → General Settings → Runtime**, pilih **PHP 8.4** (composer.json membutuhkan `^8.3`; 8.4 paling aman dan sama dengan lokal).
2. Buka **Settings → Deployments → Health checks**. Laravel Cloud seharusnya mengisi `/up` otomatis untuk startup & readiness (dari `bootstrap/app.php`). Jika kosong, isi endpoint `/up` pada startup dan readiness. Biarkan liveness kosong (default).

## 3. Buat database

1. Di kanvas environment, klik **Add database → Laravel MySQL**.
2. Buat cluster (ukuran Flex cukup untuk portfolio) di region yang sama dengan aplikasi, lalu buat database dengan nama mis. `portfolio`.
3. Laravel Cloud otomatis menyuntik `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. **Jangan isi variabel itu manual** di dashboard.
4. Tambahkan satu variabel custom: `DB_CONNECTION=mysql`.
5. Klik **Deploy** agar variabel baru terbaca, atau lanjutkan sampai langkah 6 lalu deploy sekali.

## 4. Build & deploy command

Buka **Settings → Deployments** dan isi:

**Build commands**

```sh
composer install --no-dev
php artisan optimize
```

Catatan:

- Backend ini API-only dan tidak punya `package-lock.json`, jadi `npm ci` tidak dipakai. Aset Vite bukan kebutuhan runtime API (halaman welcome punya fallback CSS).
- `php artisan optimize` melakukan cache config/route/view. Jalankan di **build**, bukan di deploy.

**Deploy commands**

```sh
php artisan migrate --force
php artisan db:seed --class=AdminUserSeeder --force
```

Catatan:

- Seeder hanya membuat/update akun admin (`admin@portfolio.test`, login bisa memakai `ADMIN_USERNAME`). Data projects/certificates **tidak** di-seed otomatis; isi lewat panel admin setelah deploy.
- Karena seeder dijalankan tiap deploy, password admin akan selalu diset ulang dari `ADMIN_PASSWORD`. Gunakan password yang kuat dan simpan di Secrets/Laravel Cloud.
- Jangan tambahkan `storage:link`, `optimize:clear`, `queue:restart`, atau `horizon:terminate` — Laravel Cloud menangani/melarangnya.

## 5. Environment variables

Buka **Settings → Environment variables** dan tambahkan:

```dotenv
APP_NAME="Portfolio API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://NAMA-APLIKASI.laravel.cloud
FRONTEND_URL=https://NAMA-FRONTEND.vercel.app
CORS_ALLOWED_ORIGINS=https://NAMA-FRONTEND.vercel.app

APP_KEY=base64:...
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
LOG_LEVEL=info

DB_CONNECTION=mysql

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public

ADMIN_USERNAME=admin
ADMIN_PASSWORD=GANTI-DENGAN-PASSWORD-KUAT
```

Generate `APP_KEY` di lokal tanpa mengubah `.env`:

```powershell
cd backend
php artisan key:generate --show
```

Catatan penting:

- `APP_URL` bisa diisi dengan domain gratis `*.laravel.cloud` yang muncul setelah deploy pertama. Kalau pakai custom domain, update `APP_URL`, `FRONTEND_URL`, dan `CORS_ALLOWED_ORIGINS`, lalu **deploy ulang** (config di-cache saat build).
- `CORS_ALLOWED_ORIGINS` boleh berisi beberapa origin dipisah koma, mis. `https://app.example.com,https://www.example.com`.
- Variabel custom **menimpa** variabel yang disuntikkan Cloud. Jangan menimpa `DB_HOST`, `DB_PASSWORD`, dll.
- Setelah mengubah variabel, klik **Deploy** agar berlaku.

## 6. Upload gambar (disarankan: Object Storage)

Filesystem aplikasi di Laravel Cloud bersifat **ephemeral** — file yang di-upload ke disk lokal akan hilang saat deploy berikutnya. Untuk gambar project/sertifikat yang persist:

1. Di kanvas environment, klik **Add bucket → Laravel Object Storage**.
2. Pilih bucket visibility **Public** (gambar dibaca frontend), lalu jadikan bucket ini **default disk** untuk environment agar Cloud meng-set `FILESYSTEM_DISK` dan konfigurasi S3-nya.
3. Deploy ulang. Kode sudah memakai disk default + `Storage::url()`, jadi URL gambar akan otomatis memakai URL publik bucket.
4. Gambar lama yang tersimpan di `public/storage` perlu di-upload ulang ke bucket (mis. lewat Cyberduck dengan kredensial bucket di menu **View credentials**).

Kalau bucket belum dibuat, API tetap jalan, tapi gambar yang di-upload lewat panel admin tidak akan bertahan setelah redeploy.

## 7. Deploy & verifikasi

1. Klik **Deploy**. **Push to deploy** aktif secara default, jadi setiap push ke `main` akan otomatis deploy.
2. Setelah sukses, cek:

```powershell
# Health
curl https://NAMA-APLIKASI.laravel.cloud/up

# Data publik (JSON, boleh kosong [])
curl https://NAMA-APLIKASI.laravel.cloud/api/v1/public/projects

# Login admin
curl -X POST https://NAMA-APLIKASI.laravel.cloud/api/v1/auth/login `
  -H "Content-Type: application/json" `
  -d '{"username":"admin","password":"PASSWORD-ANDA"}'
```

3. Di frontend (Vercel dll.), set:

```dotenv
LARAVEL_API_URL=https://NAMA-APLIKASI.laravel.cloud/api/v1
```

## 8. Masalah umum

| Gejala | Penyebab & solusi |
| --- | --- |
| Deploy gagal: `composer.json` tidak ditemukan | Root directory bukan `backend`. Buat application baru / ubah pengaturan root directory. |
| Health check 404 | Endpoint bukan `/up`, atau root directory salah. Isi `/up` di Settings → Deployments → Health checks. |
| `SQLSTATE... access denied` / `No database selected` | Cek `DB_CONNECTION=mysql`, pastikan database sudah di-attach dan environment sudah di-deploy ulang. Jangan set `DB_*` manual. |
| Login admin gagal | `ADMIN_PASSWORD` belum diisi/berbeda. Ubah variabel lalu deploy (seeder jalan saat deploy). |
| CORS error di browser | `CORS_ALLOWED_ORIGINS` harus exactly `https://domain-frontend` (tanpa trailing slash), lalu deploy ulang. |
| Gambar 404 setelah deploy | Aktifkan Object Storage sebagai default disk dan upload ulang file lama. |
| Perubahan `.env` tidak berlaku | Variabel di-cache saat build. Klik **Deploy** setelah mengubah environment variables. |

## 9. Catatan biaya

Laravel Cloud berbayar (ada free trial 31 hari + kredit). Mulai dengan compute & database ukuran kecil (Flex / scale-to-zero) untuk portfolio, lalu naikkan jika perlu. Cek **Settings → Spending limits** untuk membatasi tagihan.
