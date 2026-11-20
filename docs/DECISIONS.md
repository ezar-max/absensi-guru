# Log Keputusan Arsitektur & Asumsi Teknis (DECISIONS.md)

Dokumen ini mencatat asumsi dan keputusan desain teknis untuk proyek **Aplikasi Absensi Guru Sekolah** sesuai Bagian 16 dalam spesifikasi `RENCANA_APLIKASI_ABSENSI_GURU.md`.

---

## 1. Lingkungan & Timezone
- **Timezone**: Ditetapkan secara ketat ke `Asia/Jakarta` (`APP_TIMEZONE=Asia/Jakarta`). Semua kalkulasi waktu menggunakan jam server. Jam perangkat HP tidak dipercaya.
- **Locale**: `id` (Bahasa Indonesia) dengan fallback `en`.
- **Database**: MySQL 8.4 di Laragon, database `absensi_guru` (dan `absensi_guru_test` untuk pengujian unit/feature), charset `utf8mb4_unicode_ci`.

## 2. Struktur Monorepo
- `backend/`: Laravel 11 + Laravel Sanctum (API token mobile) + Filament v3 (Panel Admin Web).
- `mobile/`: React Native dengan Expo SDK 58, Expo Router, dan TypeScript.
- `docs/`: Dokumentasi arsitektur, API contract (`API.md`), panduan deployment (`DEPLOY.md`), dan catatan keputusan (`DECISIONS.md`).

## 3. Desain Model & Penamaan Tabel
- Model Kelas menggunakan nama `SchoolClass` dengan tabel `classes` untuk menghindari bentrok dengan reserved keyword PHP `class`.
- Pivot sekretaris ke kelas menggunakan tabel `class_user` (`user_id`, `class_id`) dengan relasi `BelongsToMany` di `User` dan `SchoolClass`. Satu kelas dapat memiliki banyak sekretaris, dan satu sekretaris dapat mengampu lebih dari satu kelas.
- Jam pelajaran (`periods`): Memiliki validasi pencegahan tumpang tindih waktu antar periode pada model `Period::saving()`.
- Jadwal pelajaran (`schedules`): Memiliki validasi anti-bentrok guru dan anti-bentrok kelas pada event model `Schedule::saving()` dan diperkuat oleh dua unique constraint di MySQL:
  - `UNIQUE(class_id, day_of_week, period_id)`
  - `UNIQUE(teacher_id, day_of_week, period_id)`
- Default settings disimpan di tabel `settings` key-value (`late_tolerance_min`: 10, `absent_threshold_pct`: 50, `edit_lock_min`: 15).

## 4. Aturan Bisnis Kehadiran (AttendanceService)
- Urutan validasi kehadiran diuji secara ketat:
  1. Hak akses sekretaris terhadap kelas jadwal.
  2. Hari jadwal sesuai dengan hari server saat ini (`Carbon::now()->dayOfWeekIso`).
  3. Pengecekan hari libur (`holidays`).
  4. Jam sekarang $\ge$ jam mulai periode.
  5. Jam sekarang $\le$ jam selesai periode (untuk sekretaris; admin diperbolehkan melewati).
- **Integritas Waktu Datang (Anti-Kecurangan Koreksi)**:
  Jika slot pernah dicatat datang dan kemudian dikoreksi (misal menjadi izin lalu diubah lagi menjadi datang), waktu kedatangan pertama (`arrived_at`) dan menit keterlambatan (`late_minutes`) tetap dipertahankan dan diverifikasi silang melalui `audit_logs`. Sekretaris tidak dapat "memperbaiki" menit keterlambatan melalui manipulasi koreksi status.
- **Konkurensi Banyak Sekretaris**:
  Jika dua sekretaris menekan tombol absensi pada slot yang sama secara simultan, database lock (`lockForUpdate`) dan unique index `uq_attendance_schedule_date` menjamin transaksi pertama berhasil, sedangkan transaksi kedua menangkap *duplicate entry* dan mengembalikan pesan ramah: *"Slot ini sudah diabsen oleh sekretaris lain."*

## 5. UI/UX Mobile (Expo)
- Menggunakan desain token warna dan tipografi *Plus Jakarta Sans* sesuai Bagian 9.2 spesifikasi.
- Jam berdetak real-time pada beranda disinkronkan dengan `server_time` dari API `/api/attendance/today`.
- Penanganan status offline: Jika perangkat kehilangan koneksi, banner peringatan ditampilkan dan tombol absensi dinonaktifkan untuk mencegah kegagalan mutasi status lokal tanpa jam server.
