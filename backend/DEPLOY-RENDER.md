# Deploy Backend Laravel gratis: Render + Neon (tanpa kartu)

Alternatif Railway yang benar-benar gratis dan tidak minta kartu:

- **Render Free** → menjalankan backend (Docker) dari GitHub.
- **Neon Free** → PostgreSQL sebagai database.

File yang dipakai:

- `render.yaml` (di root repo) → Blueprint Render, root directory `backend`.
- `backend/Dockerfile` → image PHP 8.4 + ekstensi Laravel/PostgreSQL.
- `backend/docker-entrypoint.sh` → migrate + seed admin + cache + start server saat container jalan.

## 1. Buat database Neon (gratis, tanpa kartu)

1. Buka https://neon.com → **Sign up with GitHub**.
2. Buat project, pilih region **Singapore** (biar dekat dengan Render `singapore`).
3. Buat database dengan nama `portfolio`.
4. Di halaman **Connection Details**, pilih connection string **Pooled** dan salin.

Bentuknya seperti:

```text
postgresql://user:password@ep-xxx-pooler.ap-southeast-1.aws.neon.tech/portfolio?sslmode=require
```

Simpan dulu; nanti dimasukkan ke `DB_URL` di Render.

> Neon Free: 1 GB storage & 100 CU-hours per project. Cukup untuk portfolio.

## 2. Generate APP_KEY

Di lokal:

```powershell
cd backend
php artisan key:generate --show
```

Salin hasil `base64:...`.

## 3. Deploy di Render dari Blueprint

1. Buka https://dashboard.render.com → **Sign up with GitHub**.
2. Klik **New → Blueprint**.
3. Pilih repo `mpratarlokananta/Muhammad--Pratar--Lokananta`, branch `main`.
4. Render akan membaca `render.yaml` di root repo.
5. Saat diminta isi variabel yang `sync: false`, isi:

   | Variable | Nilai |
   | --- | --- |
   | `APP_KEY` | hasil `php artisan key:generate --show` |
   | `DB_URL` | connection string Neon dari langkah 1 |
   | `ADMIN_PASSWORD` | password admin yang kuat |

6. Klik **Apply** / **Create Resources**.

Render akan otomatis:

- build Docker image dari `backend/Dockerfile`,
- menjalankan entrypoint: `migrate --force`, seed admin, cache, lalu start server,
- healthcheck di `/api/v1/public/projects`.

## 4. Update URL frontend (setelah frontend deploy)

Di service `portfolio-backend` → **Environment**, ubah:

```dotenv
APP_URL=https://portfolio-backend.onrender.com
FRONTEND_URL=https://NAMA-FRONTEND.vercel.app
CORS_ALLOWED_ORIGINS=https://NAMA-FRONTEND.vercel.app
```

Lalu **Manual Deploy → Deploy latest commit**.

Di frontend, set:

```dotenv
LARAVEL_API_URL=https://portfolio-backend.onrender.com/api/v1
```

## 5. Verifikasi

```powershell
curl https://portfolio-backend.onrender.com/api/v1/public/projects
```

Harus balas JSON. Lalu login admin:

```powershell
curl -X POST https://portfolio-backend.onrender.com/api/v1/auth/login `
  -H "Content-Type: application/json" `
  -d '{"username":"admin","password":"PASSWORD-ANDA"}'
```

## 6. Catatan penting Render Free

- Service **tidakaktif 15 menit akan sleep**. Request pertama setelah itu butuh ±1 menit untuk bangun. Normal.
- Berubah file di `backend/` otomatis trigger deploy. Perubahan di `frontend/` tidak trigger service ini (sudah diatur lewat `rootDir: backend`).
- Tidak ada shell / one-off job di Free, makanya migrate & seed dijalankan dari entrypoint saat startup.
- Kalau ingin database Render juga (tanpa Neon), bisa, tapi Database Free Render **expired 30 hari**. Neon lebih awet.

## 7. Railway

Konfigurasi Railway (`backend/railway.toml` + `backend/DEPLOY.md`) masih ada kalau nanti Anda punya akun/kredit Railway. Untuk jalur gratis ini, abaikan saja.
