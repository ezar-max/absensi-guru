# Dokumentasi Kontrak API Mobile (API.md)

Base URL: `http://<host>/api`  
Format data: `JSON` (`Accept: application/json`, `Content-Type: application/json`)  
Autentikasi: HTTP Bearer Token (`Authorization: Bearer <token>`)

---

## Format Error Standar (422 Unprocessable Entity)
Semua error validasi bisnis mengembalikan pesan dalam bahasa Indonesia dengan struktur:
```json
{
  "message": "Pesan error utama",
  "errors": {
    "message": [
      "Pesan error utama"
    ]
  }
}
```
*Catatan Mobile*: Tampilkan `errors.message[0]` langsung ke pengguna.

## Autentikasi Gagal (401 Unauthorized)
Token tidak valid atau kedaluwarsa. Aplikasi mobile wajib melakukan logout otomatis dan mengarahkan kembali ke halaman Login.

---

## 1. POST `/api/login`
Autentikasi akun sekretaris kelas. Role `admin` dan akun nonaktif ditolak.  
**Rate limit:** 10 permintaan per menit.

### Request Body
```json
{
  "email": "sekretaris1@sekolah.test",
  "password": "password",
  "device": "Samsung Galaxy A52" // opsional
}
```

### Response Sukses (200 OK)
```json
{
  "token": "1|abcdef123456...",
  "user": {
    "id": 2,
    "name": "Sekretaris Utama (Siti Aisyah)",
    "email": "sekretaris1@sekolah.test",
    "role": "secretary"
  },
  "classes": [
    {
      "id": 1,
      "name": "X IPA 1"
    }
  ]
}
```

---

## 2. POST `/api/logout`
Mencabut dan menghapus token Sanctum yang sedang aktif.

### Header
`Authorization: Bearer <token>`

### Response Sukses (200 OK)
```json
{
  "message": "Berhasil keluar."
}
```

---

## 3. GET `/api/attendance/today`
Mengambil daftar jadwal dan status kehadiran hari ini untuk kelas yang dipegang sekretaris.

### Header
`Authorization: Bearer <token>`

### Response Sukses (200 OK)
```json
{
  "server_time": "2026-11-20T07:31:05+07:00",
  "holiday": null,
  "data": [
    {
      "schedule_id": 1,
      "class": "X IPA 1",
      "period": "Jam ke-1",
      "start": "07:00",
      "end": "07:45",
      "subject": "Matematika",
      "teacher": "Pak Ahmad S.Pd",
      "attendance": {
        "id": 5,
        "status": "terlambat",
        "arrived_at": "07:30:12",
        "late_minutes": 30,
        "note": null,
        "created_at": "2026-11-20T07:30:12+07:00"
      }
    },
    {
      "schedule_id": 2,
      "class": "X IPA 1",
      "period": "Jam ke-2",
      "start": "07:45",
      "end": "08:30",
      "subject": "Matematika",
      "teacher": "Pak Ahmad S.Pd",
      "attendance": null
    }
  ]
}
```
*Catatan:*
- `server_time`: Wajib dipakai mobile untuk sinkronisasi jam dan penghitungan mundur.
- `attendance`: Bernilai `null` jika belum ada pencatatan kehadiran untuk slot tersebut.

---

## 4. POST `/api/attendance`
Mencatat atau mengoreksi kehadiran guru pada slot jadwal hari ini.

### Header
`Authorization: Bearer <token>`

### Request Body
```json
{
  "schedule_id": 1,
  "action": "datang", // 'datang' | 'kosong' | 'izin' | 'sakit' | 'dinas'
  "note": "Catatan opsional keterangan"
}
```

### Response Sukses (200 OK)
```json
{
  "message": "Absensi berhasil dicatat.",
  "data": {
    "id": 5,
    "schedule_id": 1,
    "date": "2026-11-20",
    "status": "hadir",
    "arrived_at": "07:05:00",
    "late_minutes": 0,
    "note": null,
    "recorded_by": 2,
    "updated_by": null,
    "created_at": "2026-11-20T07:05:00.000000Z",
    "updated_at": "2026-11-20T07:05:00.000000Z"
  }
}
```

---

## 5. GET `/api/attendance/history`
Mengambil riwayat absensi kelas sekretaris dengan paginasi (30 data per halaman), urutan terbaru dulu.

### Query Params
- `page`: nomor halaman (opsional, default: 1)
- `status`: filter status (opsional: `hadir`, `terlambat`, `kosong`, `izin`, `sakit`, `dinas`)

### Response Sukses (200 OK)
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 5,
      "date": "2026-11-20",
      "status": "hadir",
      "arrived_at": "07:05:00",
      "late_minutes": 0,
      "note": null,
      "created_at": "2026-11-20T07:05:00+07:00",
      "class": "X IPA 1",
      "period": "Jam ke-1",
      "start": "07:00",
      "end": "07:45",
      "subject": "Matematika",
      "teacher": "Pak Ahmad S.Pd",
      "recorded_by": "Sekretaris Utama (Siti Aisyah)"
    }
  ],
  "per_page": 30,
  "total": 1,
  "last_page": 1
}
```

---

## 6. GET `/api/teacher-ratings/today`
Mengambil jadwal pelajaran hari ini untuk kelas sekretaris, status pengisian penilaian, dan waktu server.

### Response Sukses (200 OK)
```json
{
  "server_time": "2026-11-21T07:30:00+07:00",
  "date": "2026-11-21",
  "active_period_id": 1,
  "holiday": null,
  "ratings": [
    { "value": "sangat_baik", "label": "Sangat baik", "criteria": "Guru hadir tepat waktu" }
  ],
  "data": [
    {
      "schedule_id": 1,
      "evaluation_id": null,
      "class_id": 1,
      "period_id": 1,
      "subject_id": 1,
      "teacher_id": 1,
      "class": "X IPA 1",
      "period": "Jam ke-1",
      "start": "07:00",
      "end": "07:45",
      "subject": "Matematika",
      "teacher": "Pak Ahmad S.Pd",
      "rating": null,
      "rating_label": null,
      "teacher_present": null,
      "note": null,
      "created_at": null,
      "created_at": null,
      "can_rate": true,
      "is_upcoming": false,
      "is_expired": false
    }
  ]
}
```
Penilaian hanya dapat dikirim selama jam pelajaran berlangsung. Jadwal yang telah lewat ditolak oleh server.

## 7. POST `/api/teacher-ratings`
Menyimpan penilaian untuk kelas dan jam pelajaran yang dipilih. Pengisian hanya diterima ketika jam tersebut sedang berlangsung.

### Header
`Authorization: Bearer <token>`

### Request Body
```json
{
  "class_id": 1,
  "period_id": 1,
  "subject_id": 1,
  "teacher_id": 1,
  "rating": "baik",
  "teacher_present": true,
  "note": "Catatan opsional"
}
```
`teacher_present` wajib diisi dengan `true` jika guru masuk atau `false` jika tidak masuk. Semua laporan tampil bersama di menu admin **Rekap Kehadiran**, dan catatan tersedia melalui tombol **Lihat**.

## 8. GET `/api/teacher-ratings/options`
Mengambil pilihan form penilaian untuk sekretaris: kelas yang diampu, jam pelajaran, mapel, guru aktif beserta mapel yang diajarkan, dan kriteria nilai.

### Header
`Authorization: Bearer <token>`

### Response Sukses (200 OK)
```json
{
  "classes": [{ "id": 1, "name": "X IPA 1" }],
  "periods": [{ "id": 1, "label": "Jam ke-1", "start": "07:00", "end": "07:45" }],
  "subjects": [{ "id": 1, "name": "Matematika" }],
  "teachers": [{ "id": 1, "name": "Pak Ahmad S.Pd", "subject_ids": [1] }],
  "ratings": [
    { "value": "sangat_baik", "label": "Sangat baik", "criteria": "Guru hadir tepat waktu" },
    { "value": "baik", "label": "Baik", "criteria": "Terlambat sampai 5 menit" },
    { "value": "cukup", "label": "Cukup", "criteria": "Terlambat lebih dari 5 menit" },
    { "value": "kurang", "label": "Kurang", "criteria": "Kelas kosong atau guru tidak hadir" }
  ]
}
```

## 9. GET `/api/notifications`
Mengambil pengingat admin yang ditampilkan di aplikasi. Pengingat juga dikirim sebagai push notification ke perangkat Android yang telah terdaftar.

## 10. POST `/api/notifications/{notification}/read`
Menandai pengingat yang telah dilihat sebagai sudah dibaca.

## 11. POST `/api/notifications/device-token`
Mendaftarkan token perangkat Android milik pengguna yang sedang login untuk menerima FCM.

### Request Body
```json
{
  "token": "FCM_DEVICE_REGISTRATION_TOKEN",
  "platform": "android"
}
```

## 12. DELETE `/api/notifications/device-token`
Menghapus token perangkat milik pengguna yang sedang login, misalnya saat logout.

### Request Body
```json
{
  "token": "FCM_DEVICE_REGISTRATION_TOKEN"
}
```

