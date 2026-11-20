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

