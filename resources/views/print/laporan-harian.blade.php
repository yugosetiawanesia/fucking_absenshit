<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian - {{ $reportData['tanggal_format'] }}</title>
    <style>
        @media print {
            body { font-size: 12px; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .school-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .report-info {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            border-radius: 4px;
        }
        
        .stat-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .stat-value {
            font-size: 16px;
            font-weight: bold;
        }
        
        .persentase { color: #16a34a; }
        .total { color: #6b7280; }
        .hadir { color: #16a34a; }
        .izin { color: #2563eb; }
        .sakit { color: #ca8a04; }
        .alpa { color: #dc2626; }
        .libur { color: #9333ea; }
        
        .table-container {
            margin-bottom: 25px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        td {
            font-size: 11px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .gender-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .gender-l {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .gender-p {
            background-color: #fce7f3;
            color: #be185d;
        }
        
        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .status-hadir {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .status-izin {
            background-color: #dbeafe;
            color: #1e40af;
        }
        
        .status-sakit {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .status-alpa {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .print-button {
            margin-bottom: 20px;
            padding: 10px 20px;
            background-color: #1e40af;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .print-button:hover {
            background-color: #1e3a8a;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">Cetak Laporan</button>
    
    <div class="header">
        <div class="school-name">{{ $reportData['school_name'] }}</div>
        <div class="report-title">LAPORAN HARIAN ABSENSI</div>
        <div class="report-info">Tanggal: {{ $reportData['tanggal_format'] }}</div>
        <div class="report-info">Kelas: {{ $reportData['kelas']['nama_kelas'] }}</div>
        @if($reportData['semester_active'])
            <div class="report-info">Semester: {{ $reportData['semester_active']['semester'] }} {{ $reportData['semester_active']['tahun_ajaran'] }}</div>
        @endif
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label persentase">Persentase</div>
            <div class="stat-value persentase">{{ number_format($reportData['persentase_kehadiran'], 1) }}%</div>
        </div>
        <div class="stat-card">
            <div class="stat-label total">Total Siswa</div>
            <div class="stat-value total">{{ $reportData['total_siswa'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label hadir">Hadir</div>
            <div class="stat-value hadir">{{ $reportData['hadir'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label izin">Izin</div>
            <div class="stat-value izin">{{ $reportData['izin'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label sakit">Sakit</div>
            <div class="stat-value sakit">{{ $reportData['sakit'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label alpa">Alpa</div>
            <div class="stat-value alpa">{{ $reportData['alpa'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label libur">Libur</div>
            <div class="stat-value libur">{{ $reportData['libur'] }}</div>
        </div>
    </div>
    
    <div class="table-container">
        <h3 style="margin-bottom: 15px; font-size: 14px;">Daftar Kehadiran Siswa</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 10%;">NIS</th>
                    <th style="width: 25%;">Nama Siswa</th>
                    <th style="width: 8%;">Gender</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 10%;">Jam Datang</th>
                    <th style="width: 10%;">Jam Pulang</th>
                    <th style="width: 20%;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['rows'] as $index => $siswa)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $siswa['nis'] }}</td>
                        <td>{{ $siswa['nama'] }}</td>
                        <td class="text-center">
                            <span class="gender-badge {{ $siswa['jenis_kelamin'] === 'L' ? 'gender-l' : 'gender-p' }}">
                                {{ $siswa['jenis_kelamin'] === 'L' ? 'L' : 'P' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="status-badge status-{{ $siswa['status'] }}">
                                {{ $siswa['status_text'] }}
                            </span>
                        </td>
                        <td class="text-center">{{ $siswa['jam_datang'] ? \Carbon\Carbon::parse($siswa['jam_datang'])->format('H:i') : '-' }}</td>
                        <td class="text-center">{{ $siswa['jam_pulang'] ? \Carbon\Carbon::parse($siswa['jam_pulang'])->format('H:i') : '-' }}</td>
                        <td>{{ $siswa['keterangan'] ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 20px;">
                            Tidak ada data siswa dalam kelas ini
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <p>Dicetak pada: {{ now()->translatedFormat('l, d F Y H:i') }}</p>
    </div>
    
    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
