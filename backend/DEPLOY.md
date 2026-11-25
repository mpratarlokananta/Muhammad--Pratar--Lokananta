# Deploy Backend Laravel ke Railway (dari GitHub Repo)

Panduan ini untuk men-deploy folder `backend/` dari repo GitHub:
`https://github.com/mpratarlokananta/Muhammad--Pratar--Lokananta`

## 1. Buat project Railway dari GitHub

1. Buka https://railway.com/dashboard → **New Project** → **Deploy from GitHub repo**.
2. Pilih repo `Muhammad--Pratar--Lokananta`, branch `main`.
3. Setelah service dibuat, buka **Settings → General → Root Directory** dan isi:

   ```text
   backend
   ```

   Ini wajib karena repo ini monorepo (ada `frontend/` dan `backend/`). Kalau Root Directory salah, build gagal karena `composer.json` tidak ditemukan.

## 2. Tambah database MySQL

1. Di project Railway yang sama, klik **+ New → Database → MySQL**.
2. **Rename service database menjadi `MySQL`** (nama ini dipakai di referensi variabel di bawah).
3. Railway otomatis membuat variabel `MYSQL_URL`, `MYSQLHOST`, dll. pada service database.

## 3. Generate APP_KEY

Jalankan di lokal (tidak mengubah `.env` Anda):

```powershell
cd backend
php artisan key:generate --show
```

Salin hasil `base64:...` untuk dipakai di langkah berikutnya.

## 4. Set Environment Variables

Buka service **backend** → tab **Variables**, lalu tambahkan:

```dotenv
APP_NAME="Portfolio API"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://NAMA-SERVICE-ANDA.up.railway.app
FRONTEND_URL=https://NAMA-FRONTEND-ANDA.vercel.app
CORS_ALLOWED_ORIGINS=https://NAMA-FRONTEND-ANDA.vercel.app

APP_KEY=base64:...hasil_dari_langkah_3...

DB_CONNECTION=mysql
DB_URL=${{MySQL.MYSQL_URL}}

SESSION_DRIVER=file
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
PHP_CLI_SERVER_WORKERS=4

ADMIN_USERNAME=admin
ADMIN_PASSWORD=GANTI-DENGAN-PASSWORD-KUAT
```

Catatan penting:

- `DB_URL` di-set di **service backend**, bukan di service MySQL.
- Kalau nama service MySQL bukan `MySQL`, sesuaikan `${{MySQL.MYSQL_URL}}` dengan nama service tersebut.
- `APP_URL` bisa diisi setelah domain Railway muncul di **Settings → Networking → Generate Domain**.
- `FRONTEND_URL` / `CORS_ALLOWED_ORIGINS` diisi dengan domain frontend yang sudah deploy. Bisa dikosongkan dulu kalau frontend belum ada; isi nanti lalu **Redeploy**.
- **Jangan** pakai `ADMIN_PASSWORD=admin123` di production.

Simpan variabel → Railway akan otomatis deploy ulang.

## 5. Apa yang dijalankan Railway saat deploy

Konfigurasi ada di `backend/railway.toml`:

- **Build:** `composer install --no-dev --optimize-autoloader` + `php artisan storage:link --force`
- **Pre-deploy:** `migrate --force` + seed admin (`AdminUserSeeder`) + cache config/route/view
- **Start:** `php artisan serve --host 0.0.0.0 --port $PORT`
- **Healthcheck:** `GET /api/v1/public/projects`

Seeder hanya membuat/update akun admin (`admin@portfolio.test`) dengan password dari `ADMIN_PASSWORD`. Data projects/certificates **tidak** di-seed otomatis; bisa ditambahkan lewat panel admin afterward.

## 6. Verifikasi

Setelah deploy selesai:

```powershell
curl https://NAMA-SERVICE-ANDA.up.railway.app/api/v1/public/projects
```

Harus balas JSON (bisa array kosong `[]` kalau belum ada data). Lalu coba login admin:

```powershell
curl -X POST https://NAMA-SERVICE-ANDA.up.railway.app/api/v1/auth/login `
  -H "Content-Type: application/json" `
  -d '{"username":"admin","password":"PASSWORD-ANDA"}'
```

## 7. Frontend (kalau sudah deploy)

Di project frontend (Vercel/Railway), set:

```dotenv
LARAVEL_API_URL=https://NAMA-SERVICE-ANDA.up.railway.app/api/v1
```

## Alternatif: deploy via CLI (setelah login)

Kalau nanti sudah bisa login:

```powershell
cd backend
railway login
railway link
railway up
```

Untuk menjalankan seeder/manual command:

```powershell
railway run php artisan db:seed --force
```
