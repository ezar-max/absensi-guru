# Panduan Deployment & Operasional (DEPLOY.md)

Dokumen ini menjelaskan langkah-langkah untuk menjalankan, memindahkan, dan mempublikasikan aplikasi **Absensi Guru Sekolah** (Backend Laravel + Filament & Mobile React Native Expo).

---

## 1. Menjalankan di Lingkungan Lokal (Laragon)

### Prasyarat
- **Laragon** dengan PHP 8.2+, MySQL 8+, Composer 2.x, dan Node.js 20+.
- Layanan Apache/Nginx & MySQL aktif di Laragon (**Start All**).

### Langkah Menjalankan Backend:
1. Buka terminal di folder project:
   ```bash
   cd c:\laragon\www\absensi-guru\backend
   ```
2. Pastikan file `.env` sudah terkonfigurasi:
   ```env
   APP_NAME="Absensi Guru"
   APP_ENV=local
   APP_KEY=...
   APP_DEBUG=true
   APP_TIMEZONE=Asia/Jakarta
   APP_URL=http://absensi-guru.test

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=absensi_guru
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Migrasi & Seeder Database:
   ```bash
   php artisan migrate:fresh --seed
   ```
4. Akses Web Panel Admin:
   Buka browser dan akses: `http://localhost:8000/admin` (atau virtual host `http://absensi-guru.test/admin`).
   - **Email Admin:** `admin@gmail.com`
   - **Password:** `admin123`

### Menjalankan Backend Server untuk Mobile:
Jalankan artisan serve yang mengikat semua interface IP laptop agar bisa dijangkau oleh emulator maupun HP fisik:
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

---

## 2. Pengujian dari HP Fisik / Wi-Fi Lokal

1. Pastikan laptop dan HP sekretaris terhubung ke jaringan **Wi-Fi yang sama**.
2. Cek alamat IP lokal laptop (misal via `ipconfig` di Windows PowerShell: `192.168.1.15`).
3. Buka firewall port 8000 jika diperlukan:
   ```powershell
   New-NetFirewallRule -DisplayName "Laravel Dev Server 8000" -Direction Inbound -LocalPort 8000 -Protocol TCP -Action Allow
   ```
4. Di folder `mobile/.env`, ubah:
   ```env
   EXPO_PUBLIC_API_URL=http://192.168.1.15:8000/api
   ```
5. Jalankan aplikasi Expo:
   ```bash
   cd ../mobile
   npx expo start
   ```
6. Untuk pengembangan UI, buka **Expo Go** dan scan QR code. Push notification Android tidak tersedia di Expo Go; gunakan development build.

### Konfigurasi Firebase Cloud Messaging (Android)

1. Buat atau pilih proyek Firebase, daftarkan aplikasi Android dengan package `com.sekolah.absensiguru`, lalu unduh `google-services.json` ke folder `mobile/`. File ini berisi konfigurasi aplikasi Firebase, bukan private key server.
2. Setelah file tersedia, tambahkan `"googleServicesFile": "./google-services.json"` pada bagian `expo.android` di `mobile/app.json`.
3. Aktifkan **Firebase Cloud Messaging API** di Google Cloud untuk proyek tersebut.
4. Buat service account server dengan izin **Firebase Cloud Messaging API Admin**. Simpan JSON key di luar web root, misalnya `backend/storage/app/private/firebase-service-account.json`. Jangan commit file private key.
5. Atur `.env` backend:
   ```env
   FCM_PROJECT_ID=id-proyek-firebase
   FCM_CREDENTIALS=storage/app/private/firebase-service-account.json
   ```
6. Migrasikan tabel token perangkat:
   ```bash
   cd backend
   php artisan migrate
   ```
7. Build dan instal development build atau APK preview. Setelah sekretaris login dan memberi izin notifikasi, token perangkat akan terdaftar otomatis. Admin dapat mengirim push melalui aksi **Kirim Pengingat Penilaian** di halaman akun sekretaris.
8. Kanal push dibuat tanpa suara. Untuk menerima push, perangkat harus online dan menggunakan build native; Expo Go Android tidak mendukung push remote.

---

## 3. Deployment ke Server Produksi Sekolah (Ubuntu / Debian Linux)

### Langkah di Server Linux:
1. Clone repositori ke server (misal `/var/www/absensi-guru`).
2. Masuk ke folder backend:
   ```bash
   cd /var/www/absensi-guru/backend
   composer install --no-dev --optimize-autoloader
   ```
3. Konfigurasi file `.env` produksi:
   ```env
   APP_NAME="Absensi Guru"
   APP_ENV=production
   APP_KEY=base64:...
   APP_DEBUG=false
   APP_TIMEZONE=Asia/Jakarta
   APP_URL=https://absensi.sekolah.sch.id

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=absensi_guru_prod
   DB_USERNAME=absensi_user
   DB_PASSWORD=PasswordKuat123!
   ```
4. Jalankan optimasi Laravel:
   ```bash
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
5. Set hak akses direktori storage:
   ```bash
   chown -R www-data:www-data storage bootstrap/cache
   chmod -R 775 storage bootstrap/cache
   ```
6. Konfigurasi Nginx dengan HTTPS (Let's Encrypt / SSL Sekolah) yang mengarahkan `root` ke `/var/www/absensi-guru/backend/public`.

---

## 4. Build APK Android (EAS Build)

Untuk menghasilkan file installer APK mandiri (*standalone*) yang dapat dibagikan langsung ke HP para sekretaris tanpa perlu Expo Go:

1. Buat akun di [expo.dev](https://expo.dev) jika belum memiliki.
2. Login ke EAS CLI di terminal:
   ```bash
   npm install -g eas-cli
   eas login
   ```
3. Di dalam folder `mobile/`, pastikan `EXPO_PUBLIC_API_URL` mengarah ke URL domain produksi server sekolah:
   ```env
   EXPO_PUBLIC_API_URL=https://absensi.sekolah.sch.id/api
   ```
4. Pastikan `google-services.json` tersedia di `mobile/` dan sudah direferensikan dari `expo.android.googleServicesFile` sebelum build.
5. Jalankan perintah build APK:
   ```bash
   cd mobile
   npx eas-cli@latest build -p android --profile preview
   ```
6. Setelah build cloud selesai, terminal akan memberikan link download file `.apk`.
7. Unduh file APK tersebut dan bagikan ke HP para sekretaris kelas.

---

## 5. Pemeliharaan & Backup Database

Siapkan cron job di server produksi untuk backup database MySQL harian secara otomatis:
```bash
0 2 * * * mysqldump -u absensi_user -p'PasswordKuat123!' absensi_guru_prod > /backup/db_absensi_$(date +\%F).sql
```
Simpan file backup minimal 30 hari terakhir.

