<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Absensi Guru - {{ $monthName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #2F5BEA;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            color: #2F5BEA;
            font-size: 16px;
        }
        .header p {
            margin: 4px 0 0;
            color: #666;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: center;
        }
        th {
            background-color: #f2f4f8;
            color: #111;
            font-weight: bold;
        }
        td.text-left {
            text-align: left;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN REKAP KEHADIRAN DAN PENILAIAN GURU</h2>
        <p>Periode Bulan: <b>{{ $monthName }}</b></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="7%">Tanggal</th>
                <th width="12%">Kelas / Jurusan</th>
                <th width="14%">Jam / Mapel</th>
                <th width="15%">Guru</th>
                <th width="10%">Kehadiran</th>
                <th width="10%">Penilaian</th>
                <th width="12%">Catatan</th>
                <th width="12%">Diisi oleh</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['date'])->translatedFormat('d M Y') }}</td>
                    <td>{{ $row['class'] }} / {{ $row['major'] }}</td>
                    <td>{{ $row['period'] }} / {{ $row['subject'] }}</td>
                    <td>{{ $row['teacher'] }}<br><small>NIP: {{ $row['nip'] }}</small></td>
                    <td>{{ $row['teacher_presence'] }}</td>
                    <td>{{ $row['rating'] }}</td>
                    <td class="text-left">{{ $row['note'] }}</td>
                    <td>{{ $row['recorded_by'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Belum ada laporan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->translatedFormat('d F Y H:i:s') }} | Sistem Absensi Guru
    </div>
</body>
</html>

