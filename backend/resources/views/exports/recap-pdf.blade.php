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
        <h2>LAPORAN REKAPITULASI KEHADIRAN GURU</h2>
        <p>Periode Bulan: <b>{{ $monthName }}</b></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th class="text-left" width="25%">Nama Guru</th>
                <th width="10%">Total Slot</th>
                <th width="8%">Hadir</th>
                <th width="8%">Terlambat</th>
                <th width="8%">Kosong</th>
                <th width="8%">Izin/Skt/Dns</th>
                <th width="12%">Menit Telat</th>
                <th width="12%">% Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $idx => $row)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td class="text-left">
                        <b>{{ $row['name'] }}</b><br>
                        <small style="color: #666;">NIP: {{ $row['nip'] }}</small>
                    </td>
                    <td>{{ $row['total_slot'] }}</td>
                    <td style="color: #16A34A; font-weight: bold;">{{ $row['hadir'] }}</td>
                    <td style="color: #D97706; font-weight: bold;">{{ $row['terlambat'] }}</td>
                    <td style="color: #DC2626; font-weight: bold;">{{ $row['kosong'] }}</td>
                    <td>{{ $row['izin'] + $row['sakit'] + $row['dinas'] }}</td>
                    <td>{{ $row['total_late_minutes'] }} mnt</td>
                    <td style="font-weight: bold;">{{ $row['percentage'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->translatedFormat('d F Y H:i:s') }} | Sistem Absensi Guru
    </div>
</body>
</html>

