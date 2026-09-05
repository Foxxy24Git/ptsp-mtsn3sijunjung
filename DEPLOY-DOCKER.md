# DEPLOY-DOCKER.md — Panduan Deploy Instance Ini via Docker (Home Lab)

Panduan ini khusus untuk deploy **instance PTSP Online ini** ke home lab kamu (VM Docker +
Portainer), diakses publik lewat **Cloudflare Tunnel**. Untuk client sekolah lain yang masih
pakai shared hosting cPanel, tetap ikuti [DEPLOY.md](DEPLOY.md) — panduan ini tidak
menggantikannya.

**Arsitektur:** 4 container dalam satu `docker-compose.yml` — `app` (PHP-FPM), `webserver`
(nginx), `db` (MySQL), `cloudflared` (Cloudflare Tunnel). Tidak ada port yang dibuka ke host —
aplikasi hanya bisa diakses lewat domain Cloudflare-nya.

---

## 0. Prasyarat

- Docker + Docker Compose sudah jalan di VM home lab (sudah ada, terlihat dari `docker ps`).
- Domain sudah nameserver-nya di Cloudflare (sudah ada, sudah dipakai project lain).
- Repo ini sudah bisa di-`git clone`/`git pull` dari VM (private repo → siapkan
  [Personal Access Token](https://github.com/settings/tokens) atau deploy key kalau VM belum
  pernah akses repo private kamu).

---

## 1. Buat Cloudflare Tunnel & catat token

1. Buka [Cloudflare Zero Trust dashboard](https://one.dash.cloudflare.com/) → **Networks →
   Tunnels** → **Create a tunnel** → pilih **Cloudflared**.
2. Beri nama (mis. `ptsp-mtsn3sijunjung`) → lanjut ke langkah instalasi. Di situ Cloudflare kasih
   perintah instalasi yang mengandung sebuah **token** (string panjang setelah `--token` atau di
   URL). **Salin token itu saja** — tidak perlu install `cloudflared` manual di VM, karena kita
   jalankan sebagai container (`docker-compose.yml` sudah menyiapkan service `cloudflared`).
3. Lanjut ke tab **Public Hostname** pada tunnel yang baru dibuat → **Add a public hostname**:
   - **Subdomain**: sesuai keinginan, mis. `ptsp`
   - **Domain**: domain Cloudflare kamu
   - **Service Type**: `HTTP`
   - **URL**: `webserver:80` ← nama service nginx di `docker-compose.yml`, bukan IP/port host.

## 2. Siapkan `.env` produksi di VM

Di folder project (setelah `git clone`), buat file `.env` (file ini **tidak** ikut di-push ke
GitHub — sudah di-`.gitignore`). Isi minimal:

```dotenv
APP_NAME="PTSP Online"
APP_ENV=production
APP_KEY=base64:zhfkG3t0acW3jV9ptm/zG6Itp8Vrh93O68lPdoDxy8o=
APP_DEBUG=false
APP_URL=https://ptsp.GANTI-DENGAN-DOMAIN-KAMU.com

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=ptsp_online
DB_USERNAME=ptsp_user
DB_PASSWORD=OFFCBATttTXRB728RLsPSi21T915zsU
DB_ROOT_PASSWORD=c6CAT0gDuZp4VfvSTNIK7OhcMRLvq19

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync

CLOUDFLARE_TUNNEL_TOKEN=TEMPEL_TOKEN_DARI_LANGKAH_1_DI_SINI
```

> **Catatan:**
> - `APP_KEY` di atas sudah digenerate khusus untuk deploy ini (`php artisan key:generate --show`
>   dijalankan di lokal) — aman dipakai langsung, tapi perlakukan sebagai rahasia (jangan commit).
> - `DB_PASSWORD` & `DB_ROOT_PASSWORD` di atas juga sudah digenerate acak — boleh dipakai langsung
>   atau diganti sendiri, asal `DB_PASSWORD` sama antara baris ini dan yang dibaca MySQL container.
> - `DB_HOST=db` — **bukan** `127.0.0.1`, karena dari sudut pandang container `app`, MySQL ada di
>   host bernama `db` (nama service di `docker-compose.yml`), bukan di alamat lokalnya sendiri.
> - `QUEUE_CONNECTION=sync` — dicek, project ini tidak ada job yang di-queue, jadi tidak perlu
>   container worker terpisah.

## 3. Build & jalankan

```bash
git clone https://github.com/Foxxy24Git/ptsp-mtsn3sijunjung.git
cd ptsp-mtsn3sijunjung
# buat .env seperti langkah 2, lalu:
docker compose up -d --build
```

Saat pertama kali `app` start, `docker/entrypoint.sh` otomatis menjalankan (urut): migrate
database, lalu cache config/route/view. Tunggu sampai `docker compose ps` menunjukkan semua
service `running`/`healthy`.

> File upload (media library) ditulis oleh `app` ke volume `storage_public`, dan volume yang
> **sama** juga di-mount ke `webserver` tepat di `public/storage` — jadi nginx bisa langsung
> menyajikannya sebagai file statis tanpa perlu symlink `storage:link` seperti di deploy cPanel.

> Belum ada data awal (seeder). Kalau mau ikut buat admin default seperti di lokal, jalankan
> sekali: `docker compose exec app php artisan db:seed --class=AdminUserSeeder --force`, lalu
> **segera ganti password**-nya setelah login pertama.

## 4. Checklist pasca-deploy

- [ ] Buka `https://<domain-kamu>` → halaman muncul dengan CSS (verifikasi nginx + Vite build OK).
- [ ] Login ke `/admin`, ganti password default.
- [ ] Upload 1 dokumen PDF di menu Halaman → pastikan **thumbnail preview muncul** (ini yang
  memverifikasi Ghostscript & `memory_limit` di image sudah benar — bukan cuma "situs nyala").
- [ ] Cek `docker compose logs app --tail=50` kalau ada yang aneh.

## 5. Update selanjutnya

```bash
git pull
docker compose up -d --build
```

Rebuild ini otomatis: install ulang dependency (kalau `composer.lock`/`package-lock.json`
berubah), build ulang asset Vite, jalankan migration baru, refresh cache config/route/view. Data
MySQL (`db_data`) dan file upload (`storage_public`) **tidak ikut terhapus** — keduanya disimpan
di named volume terpisah dari container aplikasi.

## 6. Troubleshooting singkat

| Gejala | Kemungkinan sebab & solusi |
|---|---|
| Container `app` terus restart | Cek `docker compose logs app` — biasanya migration gagal karena `db` belum `healthy` atau kredensial `.env` salah. |
| Situs tampil tanpa CSS/JS | Build asset gagal — cek `docker compose logs webserver` dan `docker compose build app` ulang. |
| Gambar/dokumen tidak muncul | Cek volume ter-mount di kedua sisi: `docker compose exec app ls storage/app/public` dan `docker compose exec webserver ls public/storage`. |
| Thumbnail PDF tidak muncul (cuma ikon) | Cek Ghostscript terpasang di image: `docker compose exec app gs -version`. |
| Domain tidak bisa diakses sama sekali | Cek `docker compose logs cloudflared` — biasanya token salah atau Public Hostname belum disimpan di dashboard Cloudflare. |
| 419 / session expired terus | `APP_KEY` kosong/berubah — pastikan `.env` sudah ada isinya sebelum `docker compose up` pertama kali. |
