# RENCANA PEMBUATAN: Aplikasi Absensi Guru Sekolah

> Dokumen ini adalah spesifikasi lengkap untuk AI agent / developer. Bangun sesuai urutan fase di Bagian 14. Jika ada yang ambigu, pilih opsi yang paling sederhana yang memenuhi aturan di dokumen ini, lalu catat asumsinya di `DECISIONS.md`.

---

## 1. Ringkasan Produk

Aplikasi untuk mencatat kehadiran **guru** per jam pelajaran. Yang mengabsen guru adalah **sekretaris murid** di tiap kelas lewat aplikasi mobile. **Admin** (TU / wakil kurikulum) mengelola data master dan jadwal, memantau, serta merekap lewat **web di laptop**.

| Platform | Pengguna | Teknologi |
|---|---|---|
| Aplikasi mobile Android (APK) | Sekretaris kelas | React Native (Expo) |
| Web panel (browser laptop) | Admin | Laravel + Filament |
| Backend API | Mobile | Laravel 11 + Sanctum |
| Database | Semua | MySQL 8 / MariaDB 10.4+ |

**Lingkungan:**
- Pengembangan: server lokal **Laragon** (PHP 8.2+, MySQL, Composer, Node 20+).
- Produksi: server sekolah (Linux/Windows) dengan HTTPS. Pindah server cukup mengganti `.env`.

---

## 2. Ruang Lingkup

### Versi 1 (WAJIB dibuat)
- Login dua role: `admin` dan `secretary`.
- Master data: tahun ajaran/kelas, mapel, guru, jam pelajaran, akun sekretaris, hari libur.
- Penyusunan jadwal per kelas dengan **validasi anti-bentrok**.
- Sekretaris mengabsen guru per slot jadwal hari ini.
- Status otomatis: hadir, terlambat, kosong; manual: izin, sakit, dinas.
- Dashboard admin, rekap bulanan, ekspor Excel/PDF, audit log.
- Pengaturan toleransi yang bisa diubah admin.

### Di luar Versi 1 (JANGAN dibuat dulu)
Notifikasi push, mode offline, foto bukti, guru pengganti, iOS. Struktur kode jangan menghalangi penambahan ini nanti (lihat Bagian 15).

---

## 3. Peran dan Hak Akses

**Admin**
- CRUD semua data master dan jadwal.
- Melihat semua absensi, mengoreksi absensi kapan saja (tanpa batas waktu), mengabsen atas nama kelas mana pun.
- Mengubah pengaturan, mengelola akun, melihat audit log, ekspor laporan.
- Login lewat **web**. Admin tidak memakai aplikasi mobile.

**Secretary (Sekretaris kelas)**
- Login lewat **aplikasi mobile**.
- Satu akun bisa terikat ke satu atau lebih kelas; **satu kelas boleh punya banyak sekretaris** (cadangan bila sekretaris utama tidak masuk).
- Hanya melihat jadwal dan riwayat kelasnya sendiri.
- Hanya bisa mengabsen di hari dan jam jadwal kelasnya (aturan di Bagian 6).
- Tidak punya akses web admin (ditolak di panel Filament).

---

## 4. Teknologi dan Versi

**Backend**
- PHP 8.2+, Laravel 11, `laravel/sanctum` (token untuk mobile).
- `filament/filament` v3 (panel admin web, auth session terpisah dari Sanctum).
- `maatwebsite/excel` (ekspor Excel), `barryvdh/laravel-dompdf` (ekspor PDF).
- Zona waktu aplikasi: `Asia/Jakarta` (`APP_TIMEZONE` dan `config/app.php`).
- Locale: `id`.

**Mobile**
- Expo SDK terbaru, TypeScript, Expo Router (file-based).
- `@tanstack/react-query` (data server), `axios`, `expo-secure-store` (simpan token), `zod` (validasi form).
- Styling: NativeWind (Tailwind untuk RN) **atau** StyleSheet dengan design token di Bagian 9. Pilih satu, konsisten.
- Font: Plus Jakarta Sans via `@expo-google-fonts/plus-jakarta-sans`.
- Ikon: `lucide-react-native`.
- Build APK: EAS Build (`eas build -p android --profile preview` menghasilkan APK untuk dibagikan).

**Struktur repo (monorepo sederhana)**
```
absensi-guru/
├─ backend/        # Laravel (API + panel admin Filament)
├─ mobile/         # Expo app
├─ docs/           # DECISIONS.md, API.md, catatan deploy
└─ README.md
```
Catatan: sudah ada backend awal (migration, `AttendanceService`, controller API, routes) di `absensi-guru-backend.zip`. Gunakan sebagai titik mulai dan rapikan, atau implementasikan ulang sesuai spesifikasi ini. Perilaku di dokumen ini yang jadi acuan.

---

## 5. Desain Database (MySQL)

Engine InnoDB, charset `utf8mb4`. Semua tabel dibuat lewat migration Laravel.

### 5.1 Tabel

**users** (tabel bawaan Laravel + tambahan)
| Kolom | Tipe | Catatan |
|---|---|---|
| id | bigint PK | |
| name | string | |
| email | string unique | dipakai login |
| password | string | bcrypt |
| role | string(20) | `admin` / `secretary`, default `secretary` |
| is_active | boolean | default true; akun nonaktif tidak bisa login |

**classes**: `id`, `name`, `academic_year` (mis. `2026/2027`); unique(`name`,`academic_year`).

**class_user** (pivot sekretaris-kelas, many-to-many): `user_id`, `class_id`; PK gabungan. Inilah yang memungkinkan banyak sekretaris per kelas.

**subjects**: `id`, `name` unique.

**teachers**: `id`, `name`, `nip` nullable unique, `is_active`.

**periods** (jam pelajaran): `id`, `label` (mis. "Jam ke-1"), `start_time`, `end_time`. Aturan: `end_time > start_time` dan **tidak boleh tumpang tindih** dengan periode lain (validasi di model).

**schedules**: `id`, `class_id`, `day_of_week` (1=Senin..7=Minggu), `period_id`, `subject_id`, `teacher_id`.
- `UNIQUE(class_id, day_of_week, period_id)` → anti-bentrok kelas.
- `UNIQUE(teacher_id, day_of_week, period_id)` → anti-bentrok guru.
- Karena periode dijamin tidak tumpang tindih, dua unique index ini sudah cukup menjamin tidak ada bentrok waktu.

**settings** (key-value): `key` PK, `value`. Default:
| key | default | arti |
|---|---|---|
| late_tolerance_min | 10 | datang ≤ N menit setelah mulai = hadir |
| absent_threshold_pct | 50 | "kosong" baru boleh dicatat setelah N% durasi jam berlalu |
| edit_lock_min | 15 | sekretaris boleh koreksi N menit setelah mencatat |

**holidays**: `date` PK, `name`.

**attendances**: `id`, `schedule_id`, `date`, `status` (`hadir|terlambat|kosong|izin|sakit|dinas`), `arrived_at` (time, null), `late_minutes` (int default 0), `note` (null), `recorded_by` (user), `updated_by` (user, null), `created_at`, `updated_at`.
- `UNIQUE(schedule_id, date)` → mustahil absen dobel pada slot yang sama.
- Index pada `date`.

**audit_logs**: `id`, `actor_id`, `action` (`insert|update|delete|login`), `table_name`, `record_id`, `detail` (JSON), `created_at`.

### 5.2 Relasi singkat
`classes 1─N schedules`, `teachers 1─N schedules`, `subjects 1─N schedules`, `periods 1─N schedules`, `schedules 1─N attendances`, `users N─M classes`.

### 5.3 Seeder (untuk dev)
- 1 admin (`admin@sekolah.test`), 2 sekretaris (satu kelas yang sama untuk uji "banyak sekretaris"), 3 kelas, 5 mapel, 6 guru, 6 jam pelajaran, jadwal contoh senin-jumat tanpa bentrok.

---

## 6. Aturan Bisnis (INTI, wajib persis)

Semua perhitungan waktu memakai **jam server** (`Asia/Jakarta`). Jam HP tidak boleh dipercaya. Logika berada di satu tempat: `App\Services\AttendanceService::submit(User, scheduleId, action, note)`.

**Aksi dari sekretaris:** `datang`, `kosong`, `izin`, `sakit`, `dinas`.

**Validasi urut (gagal → error 422 dengan pesan Indonesia):**
1. Sekretaris harus terikat ke kelas pada jadwal itu (admin bebas).
2. `day_of_week` jadwal harus sama dengan hari ini.
3. Hari ini bukan hari libur (`holidays`).
4. Sekarang ≥ `start_time` periode ("Jam pelajaran belum dimulai").
5. Sekarang ≤ `end_time` periode untuk sekretaris ("Jam pelajaran sudah selesai, hubungi admin"); admin boleh melewati.

**Penentuan status:**
- `datang`: `menit = sekarang − start_time`. Jika `menit ≤ late_tolerance_min` → `hadir`, `late_minutes = 0`. Jika lebih → `terlambat`, `late_minutes = menit`. `arrived_at` = jam server saat tombol ditekan.
- `kosong`: hanya boleh jika `menit ≥ durasi × absent_threshold_pct / 100` (sekretaris). Admin boleh kapan saja.
- `izin|sakit|dinas`: langsung dicatat dengan catatan opsional.

**Contoh baku (harus jadi test):**
| Periode | Aksi pada | Hasil |
|---|---|---|
| 07:00–09:00 | `datang` 07:05 | hadir, late 0 |
| 07:00–09:00 | `datang` 07:10 | hadir, late 0 (batas toleransi) |
| 07:00–09:00 | `datang` 07:30 | terlambat, late 30 |
| 07:00–09:00 | `kosong` 07:40 | ditolak (batas 50% = 08:00) |
| 07:00–09:00 | `kosong` 08:00 | kosong |
| 07:00–09:00 | `datang` 06:59 | ditolak, belum mulai |
| 07:00–09:00 | `datang` 09:01 (sekretaris) | ditolak, sudah selesai |

**Koreksi dan kunci edit:**
- Jika slot sudah punya absensi di hari itu, pemanggilan berikutnya dianggap **koreksi**.
- Sekretaris hanya boleh koreksi sampai `edit_lock_min` menit setelah pencatatan pertama (`created_at`). Setelah itu → "Batas waktu koreksi habis, hubungi admin".
- Admin boleh koreksi kapan saja.
- Saat koreksi menjadi `datang`, pertahankan `arrived_at` dan `late_minutes` pertama bila sudah ada (tidak boleh "memperbaiki" keterlambatan lewat koreksi).
- Setiap insert/update tercatat di `audit_logs`.

**Banyak sekretaris:** dua sekretaris kelas yang sama menekan hampir bersamaan → satu berhasil, yang lain dapat "Slot ini sudah diabsen oleh sekretaris lain" (tangkap unique violation, gunakan transaksi + `lockForUpdate`).

**Validasi jadwal (anti-bentrok), dicek di model sebelum simpan (pesan jelas) dan dijaga unique index (pengaman terakhir):**
- Guru bentrok: "Guru X sudah mengajar di kelas Y pada slot yang sama".
- Kelas bentrok: "Kelas Y sudah punya mapel Z pada slot yang sama".
- Satu guru boleh mengajar banyak mapel dan banyak kelas selama slotnya berbeda.

**Slot terlewat:** slot yang periodenya sudah selesai tanpa absensi ditampilkan sebagai "Belum diabsen" (jangan otomatis jadi kosong; admin yang memutuskan lewat dashboard).

---

## 7. Kontrak API (untuk mobile)

Base: `https://<host>/api`. Header: `Accept: application/json`, `Authorization: Bearer <token>`. Semua error validasi: HTTP 422 dengan body `{"message": "...", "errors": {"message": ["pesan Indonesia"]}}`. Tampilkan `errors.message[0]` ke pengguna. HTTP 401 → paksa logout di aplikasi.

| Method | Path | Fungsi |
|---|---|---|
| POST | `/login` | body `{email, password, device?}` → `{token, user{id,name,email,role}, classes[{id,name}]}`. Throttle 10/menit. Tolak jika bukan `secretary` atau tidak aktif. |
| POST | `/logout` | hapus token saat ini |
| GET | `/attendance/today` | jadwal hari ini untuk semua kelas sekretaris |
| POST | `/attendance` | body `{schedule_id, action, note?}` → baris attendance |
| GET | `/attendance/history?page=` | riwayat paginasi (30/halaman), terbaru dulu |

**Respons `/attendance/today`:**
```json
{
  "server_time": "2026-11-20T07:31:05+07:00",
  "data": [
    {
      "schedule_id": 12, "class": "X IPA 1",
      "period": "Jam ke-1", "start": "07:00", "end": "09:00",
      "subject": "Matematika", "teacher": "Pak Ahmad",
      "attendance": { "status": "terlambat", "arrived_at": "07:30:12", "late_minutes": 30, "note": null, "created_at": "..." }
    }
  ]
}
```
`attendance` bernilai `null` bila belum diabsen. Mobile **wajib** memakai `server_time` (bukan jam HP) untuk menentukan state slot dan hitung mundur.

Tambahan: dokumentasikan semua endpoint di `docs/API.md`.

---

## 8. Panel Admin Web (Filament v3)

Akses: `/admin`. Hanya `role = admin` dan `is_active` (implementasikan `canAccessPanel`). Bahasa Indonesia. Navigasi dikelompokkan:

**Beranda**
- **Dashboard** (default): widget statistik hari ini: total slot, hadir, terlambat, kosong, belum diabsen. Tabel "Perlu perhatian" (slot terlewat belum diabsen dan guru kosong) dengan aksi cepat "Catat sebagai kosong/izin". Grafik tren kehadiran 30 hari.

**Data Master**
- **Kelas**: CRUD; relation manager untuk menautkan banyak sekretaris.
- **Mapel**, **Guru**: CRUD + toggle aktif. Halaman guru menampilkan beban jam mengajar per minggu.
- **Jam Pelajaran**: CRUD dengan validasi tumpang tindih.
- **Akun Sekretaris**: buat akun (name, email, password, kelas), reset password, nonaktifkan. Nonaktif bukan hapus.
- **Hari Libur**: CRUD (tanggal + nama), dukung input rentang tanggal.

**Jadwal**
- **Grid Jadwal** (halaman kustom): pilih kelas → tabel baris = jam pelajaran, kolom = Senin–Sabtu. Klik sel kosong membuka modal (mapel + guru). Dropdown guru **menandai/menonaktifkan guru yang sudah terpakai di slot itu** dan menampilkan "mengajar di kelas Y". Sel terisi menampilkan mapel + nama guru, bisa diedit/hapus. Pesan bentrok tampil sebagai notifikasi merah yang jelas.
- **Daftar Jadwal**: tabel datar dengan filter kelas/guru/hari.
- **Jadwal per Guru**: pilih guru → grid mingguan (read-only) untuk memeriksa beban.

**Absensi**
- **Data Absensi**: tabel dengan filter tanggal/rentang, kelas, guru, mapel, status. Edit status oleh admin (wajib isi catatan alasan; tercatat di audit log).
- **Rekap**: rekap bulanan per guru (total slot, hadir, terlambat, kosong, izin/sakit/dinas, total menit terlambat, persentase hadir) dan rekap per kelas. Ekspor **Excel** dan **PDF**.

**Sistem**
- **Pengaturan**: form untuk `late_tolerance_min`, `absent_threshold_pct`, `edit_lock_min` dengan teks bantuan dan contoh perhitungan live.
- **Audit Log**: tabel read-only dengan filter pelaku dan aksi.

---

## 9. UI/UX dan Gaya Visual

### 9.1 Prinsip
1. **Satu tugas utama**: sekretaris cukup membuka aplikasi dan menekan satu tombol besar pada jam yang berlangsung. Maksimal 2 ketukan dari layar utama ke absen terkirim.
2. **Status terbaca sekilas** lewat warna + ikon + teks (jangan hanya warna).
3. **Bahasa Indonesia sederhana**, tanpa jargon. Pengguna adalah siswa.
4. **Jelas, tidak mengintimidasi**: konfirmasi sebelum aksi yang tidak bisa dibatalkan (kosong).
5. Ramah satu tangan: aksi utama di bagian bawah layar.

### 9.2 Design token
**Warna (mode terang)**
| Token | Hex | Pemakaian |
|---|---|---|
| primary | `#2F5BEA` | tombol utama, tab aktif, link |
| primary-soft | `#EAF0FF` | latar slot sedang berlangsung |
| bg | `#F6F7FB` | latar layar |
| surface | `#FFFFFF` | kartu |
| text | `#0F172A` | teks utama |
| text-muted | `#64748B` | teks sekunder |
| border | `#E2E8F0` | garis |
| success | `#16A34A` / soft `#DCFCE7` | Hadir |
| warning | `#D97706` / soft `#FEF3C7` | Terlambat |
| danger | `#DC2626` / soft `#FEE2E2` | Kosong |
| info | `#7C3AED` / soft `#EDE9FE` | Izin / Sakit / Dinas |
| neutral | `#94A3B8` / soft `#F1F5F9` | Belum mulai / Belum diabsen |

**Mode gelap:** ikuti pengaturan sistem. bg `#0B1220`, surface `#131C2E`, text `#E5E7EB`, border `#24324A`; warna status tetap, soft-nya diganti versi transparan 16%. Pastikan kontras teks minimal WCAG AA (4.5:1).

**Tipografi (Plus Jakarta Sans):** Judul layar 22/28 SemiBold; judul kartu 16/22 SemiBold; isi 14/20 Regular; keterangan 12/16 Medium; jam besar 28/32 Bold (angka tabular).

**Bentuk dan ruang:** grid 4dp; padding layar 16; radius kartu 16, tombol 14, chip 999; elevasi lembut (shadow 0/2/8 opacity 6%). Tinggi tombol utama 52, area sentuh minimal 48×48. Ikon garis (lucide) ukuran 20–24.

### 9.3 Komponen
- **StatusChip**: pil berwarna soft + ikon + label (Hadir ✓, Terlambat ⏱ +30 mnt, Kosong ✕, Izin/Sakit/Dinas ℹ, Belum mulai ◔, Belum diabsen !).
- **SlotCard**: kiri blok jam (mulai besar, selesai kecil), tengah mapel (bold) + nama guru + nama kelas bila sekretaris punya >1 kelas, kanan StatusChip. Sedang berlangsung: border primary 2dp + latar primary-soft + label "Sedang berlangsung" dan tombol di dalam kartu.
- **PrimaryButton**: penuh lebar, loading state (spinner, tombol disabled).
- **BottomSheet** aksi absen.
- **Toast/Snackbar** hasil (sukses hijau, gagal merah, otomatis hilang 3 dtk).
- **EmptyState**: ilustrasi sederhana + teks ("Tidak ada jadwal hari ini", "Hari ini libur: <nama>").
- **Skeleton** saat memuat (jangan layar kosong).

### 9.4 Layar mobile (Expo Router)
```
app/
├─ (auth)/login.tsx
├─ (tabs)/_layout.tsx        # tab bawah: Hari Ini | Riwayat | Profil
├─ (tabs)/index.tsx          # Jadwal Hari Ini
├─ (tabs)/history.tsx
├─ (tabs)/profile.tsx
└─ attendance/[scheduleId].tsx   # opsional bila tidak memakai bottom sheet
```

**1. Login**
- Logo + nama aplikasi di atas, form email + password (ikon mata untuk tampilkan sandi), tombol "Masuk".
- Error ditampilkan inline di bawah form. Token disimpan di SecureStore; saat dibuka lagi langsung ke Beranda bila token valid.

**2. Jadwal Hari Ini (Beranda)**
- Header: sapaan ("Halo, <nama>"), tanggal hari ini (format "Jumat, 20 November 2026"), nama kelas (chip), jam server yang berdetak.
- Daftar SlotCard berurutan jam. Auto-scroll ke slot yang sedang berlangsung. Pull-to-refresh. Auto-refresh tiap 60 detik dan saat aplikasi kembali aktif.
- **State SlotCard:**
  | State | Tampilan |
  |---|---|
  | Belum mulai | abu, chip "Belum mulai", tidak ada tombol |
  | Sedang berlangsung, belum diabsen | primary-soft + tombol besar "Guru Datang" dan tombol sekunder "Lainnya…" |
  | Sudah diabsen | chip status + jam datang; jika masih dalam jendela koreksi tampil tautan "Ubah" dengan sisa waktu ("sisa 12 mnt") |
  | Sudah diabsen, jendela koreksi habis | chip status, tanpa tombol, ikon gembok kecil |
  | Selesai, belum diabsen | chip "Belum diabsen" + teks "Hubungi admin" |
- Tombol **"Guru Datang"** langsung mengirim. Setelah sukses, kartu berubah menjadi Hadir/Terlambat dengan animasi singkat dan toast ("Tercatat: Terlambat 30 menit") plus haptic.
- Tombol **"Lainnya…"** membuka BottomSheet: "Guru tidak datang (kosong)", "Izin", "Sakit", "Dinas", kolom catatan opsional. Opsi "kosong" dinonaktifkan dengan keterangan "Bisa dipilih mulai 08:00" sebelum batas tercapai. Aksi "kosong" meminta konfirmasi dialog.
- Hari libur: EmptyState dengan nama hari libur. Hari tanpa jadwal: EmptyState biasa.

**3. Riwayat**
- Daftar dikelompokkan per tanggal (header tanggal), tiap item: jam, mapel, guru, StatusChip. Infinite scroll (paginasi). Filter chip: Semua / Terlambat / Kosong.

**4. Profil**
- Nama, email, daftar kelas, versi aplikasi, tombol "Keluar" (dengan konfirmasi), pilihan tema (Sistem/Terang/Gelap).

### 9.5 Perilaku, error, dan aksesibilitas
- Tampilkan pesan server apa adanya (sudah berbahasa Indonesia). Tanpa internet: banner merah "Tidak ada koneksi" dan tombol absen dinonaktifkan (V1 tidak offline).
- Cegah ketuk ganda (disable saat request berjalan).
- Label aksesibilitas pada semua tombol/ikon; ukuran font mengikuti pengaturan sistem; jangan mengandalkan warna saja.
- Mikro-interaksi halus (≤200 ms), hormati pengaturan "kurangi gerakan".

### 9.6 Gaya panel admin web
Pakai tema Filament dengan warna primer `#2F5BEA`, font Plus Jakarta Sans, sidebar kolaps, mode gelap aktif. Badge status di tabel memakai skema warna yang sama dengan mobile (Hadir hijau, Terlambat kuning, Kosong merah, Izin/Sakit/Dinas ungu).

---

## 10. Keamanan
- Password bcrypt; kebijakan minimal 8 karakter. Admin bisa reset password sekretaris.
- Token Sanctum per perangkat; logout menghapus token; akun nonaktif tidak bisa login dan tokennya dicabut.
- Rate limit login. Validasi semua input di server. Gunakan transaksi pada pencatatan absensi.
- Otorisasi di server (jangan percaya klien): sekretaris hanya mengakses kelasnya (policy/query scope).
- Produksi: HTTPS wajib, `APP_DEBUG=false`, CORS dibatasi, backup database harian terjadwal.
- Jangan simpan data sensitif di log. Audit log untuk perubahan absensi.

---

## 11. Struktur Backend (Laravel)

```
app/
├─ Models/            (User, SchoolClass, Subject, Teacher, Period, Schedule, Attendance, Holiday, Setting, AuditLog)
├─ Services/AttendanceService.php
├─ Http/Controllers/Api/{AuthController,AttendanceController}.php
├─ Filament/{Pages,Resources,Widgets}/...
├─ Exports/           (rekap Excel)
database/{migrations,seeders}/
routes/api.php
tests/{Feature,Unit}/
```

---

## 12. Pengujian

**Wajib otomatis (PHPUnit/Pest):**
- Semua baris tabel "Contoh baku" Bagian 6 (gunakan `Carbon::setTestNow`).
- Koreksi dalam dan di luar jendela `edit_lock_min`; admin tanpa batas.
- Dua sekretaris satu kelas: yang kedua ditolak dengan pesan benar.
- Sekretaris kelas lain ditolak; hari bukan hari jadwal ditolak; hari libur ditolak.
- Bentrok guru dan bentrok kelas ditolak; guru yang sama di slot berbeda diterima; periode tumpang tindih ditolak.
- Login: akun nonaktif, role admin lewat API mobile, password salah.

**Manual (checklist akhir):** alur penuh dengan 2 HP berbeda, ganti pengaturan toleransi lalu cek hasil, ekspor Excel/PDF terbuka benar, tampilan mode terang/gelap, layar kecil (360dp).

---

## 13. Deployment

**Dev (Laragon):** Laragon Start All → `composer install` → `.env` (DB `absensi_guru`, user `root`) → `php artisan migrate --seed` → akses `http://absensi-guru.test`. Untuk uji dari HP di Wi-Fi yang sama, pakai IP laptop (mis. `http://192.168.x.x`) dan izinkan lewat firewall; set URL API di `mobile/.env` (`EXPO_PUBLIC_API_URL`).

**Produksi (server sekolah):** PHP-FPM + Nginx/Apache, MySQL, HTTPS (sertifikat valid atau domain internal), `php artisan optimize`, scheduler/cron untuk backup DB, `APP_ENV=production`. Pindah dari Laragon cukup mengganti `DB_*` dan `APP_URL` di `.env` lalu `php artisan migrate`.

**APK:** set `EXPO_PUBLIC_API_URL` ke URL produksi → `eas build -p android --profile preview` → bagikan file APK. Dokumentasikan langkah di `docs/DEPLOY.md`.

---

## 14. Rencana Fase dan Kriteria Selesai

| Fase | Pekerjaan | Selesai bila |
|---|---|---|
| 1. Fondasi | Setup Laravel, migration, model, seeder, Sanctum, `AttendanceService` + semua test Bagian 12 | Semua test hijau; `migrate --seed` jalan di Laragon |
| 2. API | Auth, today, store, history sesuai Bagian 7 + `docs/API.md` | Bisa diuji lewat Postman/Insomnia dengan data seeder |
| 3. Panel admin | Filament: master data, grid jadwal (anti-bentrok), absensi, pengaturan | Admin bisa menyusun jadwal satu kelas penuh dan bentrok ditolak dengan pesan jelas |
| 4. Dashboard dan laporan | Dashboard, rekap bulanan, ekspor Excel/PDF, audit log | Ekspor membuka benar, angka rekap cocok dengan data uji |
| 5. Mobile | Expo: login, Beranda (semua state SlotCard), BottomSheet, Riwayat, Profil, tema, penanganan error | Alur absen end-to-end di HP asli; semua state tampil benar |
| 6. Pemolesan | Skeleton, empty state, aksesibilitas, ikon/splash aplikasi, nama paket Android | Checklist manual Bagian 12 lulus |
| 7. Rilis | Deploy ke server sekolah, build APK, `DEPLOY.md` | APK terpasang di HP sekretaris dan terhubung ke server sekolah |

Kerjakan per fase, commit per fase, dan jalankan test sebelum lanjut.

---

## 15. Rencana Lanjutan (setelah V1, jangan dibuat sekarang)
- Pengingat admin di dalam aplikasi dan push notification Android via FCM tanpa bunyi.
- Mode offline ringan (antrean lokal + sinkron) → karena waktu dari server, perlu desain khusus untuk waktu absen offline.
- Foto/catatan bukti saat guru kosong.
- Guru pengganti dan tukar jam.
- Dukungan iOS.

---

## 16. Asumsi dan Catatan untuk Agent
- Hari sekolah default Senin–Sabtu; Minggu tidak ditampilkan di grid.
- Satu tahun ajaran aktif pada satu waktu; kelas diarsipkan dengan membedakan `academic_year`.
- Tidak ada pendaftaran mandiri; semua akun dibuat admin.
- Semua teks antarmuka berbahasa Indonesia.
- Catat setiap keputusan yang tidak tercantum di dokumen ini ke `docs/DECISIONS.md`.
- Jangan menambah fitur di luar Bagian 2 tanpa persetujuan.
