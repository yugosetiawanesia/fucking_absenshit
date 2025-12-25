<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian - {{ $reportData['tanggal_format'] }}</title>
    <style>
        @page {
            size: 330mm 210mm;
            margin: 8mm 8mm;
        }

        @media print {
            body { font-size: 9px; margin: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            .table-container { overflow: visible !important; }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }

        .pdf body,
        body.pdf {
            font-size: 9px;
            margin: 0;
        }

        body.pdf .table-container {
            overflow: visible !important;
        }

        body.pdf * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 10px;
            line-height: 1.1;
        }
        
        .header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }

        body.pdf .header {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        body.pdf .header-logo,
        body.pdf .header-text {
            display: table-cell;
            vertical-align: middle;
        }

        body.pdf .header-logo {
            width: 55px;
        }

        .header-logo {
            width: 55px;
            height: 55px;
            object-fit: contain;
        }

        .header-text {
            flex: 1;
            text-align: center;
        }
        
        .school-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .school-address {
            font-size: 10px;
            color: #444;
            margin-bottom: 4px;
            line-height: 1.2;
        }
        
        .report-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .report-info {
            font-size: 10px;
            color: #666;
            margin-bottom: 2px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 6px;
            margin-bottom: 15px;
        }
        
        .stat-card {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }
        
        .stat-label {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        
        .stat-value {
            font-size: 12px;
            font-weight: bold;
        }
        
        .total { color: #6b7280; }
        .hadir { color: #16a34a; }
        .izin { color: #2563eb; }
        .sakit { color: #ca8a04; }
        .alpa { color: #dc2626; }
        .libur { color: #9333ea; }
        
        .table-container {
            margin-bottom: 15px;
            overflow-x: auto;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8px;
            table-layout: fixed;
        }
        
        .attendance-table th, 
        .attendance-table td {
            border: 1px solid #000;
            padding: 1px;
            text-align: center;
            vertical-align: middle;
            min-width: 12px;
            height: 16px;
        }

        .attendance-table th.col-no,
        .attendance-table td.col-no {
            width: 8mm;
        }

        .attendance-table th.col-nis,
        .attendance-table td.col-nis {
            width: 22mm;
            text-align: left;
            padding-left: 3px;
        }

        .attendance-table th.col-nama,
        .attendance-table td.col-nama {
            width: 40mm;
            text-align: left;
            padding-left: 3px;
        }

        .attendance-table th.col-gender,
        .attendance-table td.col-gender {
            width: 8mm;
        }

        .attendance-table th.col-status,
        .attendance-table td.col-status {
            width: 12mm;
        }

        .attendance-table th.col-jam,
        .attendance-table td.col-jam {
            width: 12mm;
        }

        .attendance-table th.col-ket,
        .attendance-table td.col-ket {
            width: 30mm;
            text-align: left;
            padding-left: 3px;
        }
        
        .gender-badge {
            display: inline-block;
            padding: 1px 3px;
            border-radius: 3px;
            font-size: 7px;
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
            padding: 1px 3px;
            border-radius: 3px;
            font-size: 7px;
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
        
        .status-libur {
            background-color: #f3e8ff;
            color: #7c3aed;
        }
        
        .printed-at {
            position: fixed;
            right: 8mm;
            bottom: 6mm;
            font-size: 8px;
            color: #666;
            text-align: right;
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
        @php
            $logoPath = $reportData['school_logo_path'] ?? null;
            $logoUrl = null;
            if (!empty($logoPath)) {
                $logoUrl = str_starts_with($logoPath, 'http')
                    ? $logoPath
                    : asset('storage/' . ltrim($logoPath, '/'));
            }
        @endphp

        @if(!empty($logoUrl))
            <img class="header-logo" src="{{ $logoUrl }}" alt="Logo" />
        @else
            <div class="header-logo"></div>
        @endif

        <div class="header-text">
            <div class="school-name">{{ $reportData['school_name'] }}</div>
            <div class="report-title">LAPORAN HARIAN ABSENSI</div>
            @if(!empty($reportData['school_address']))
                <div class="school-address">{{ $reportData['school_address'] }}</div>
            @endif
            <div class="report-info">
                Tanggal : {{ $reportData['tanggal_format'] }} | Kelas : {{ $reportData['kelas']['nama_kelas'] }}
                @if($reportData['semester_active'])
                    | Semester : {{ ucfirst($reportData['semester_active']['semester']) }} {{ $reportData['semester_active']['tahun_ajaran'] }}
                @endif
            </div>
        </div>
    </div>
    
    <div class="stats-grid">
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
    </div>
    
    <div class="table-container">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="col-no">NO</th>
                    <th class="col-nis">NIS</th>
                    <th class="col-nama">NAMA SISWA</th>
                    <th class="col-gender">L/P</th>
                    <th class="col-status">Status</th>
                    <th class="col-jam">Datang</th>
                    <th class="col-jam">Pulang</th>
                    <th class="col-ket">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['rows'] as $index => $siswa)
                    <tr>
                        <td class="col-no">{{ $index + 1 }}</td>
                        <td class="col-nis">{{ $siswa['nis'] }}</td>
                        <td class="col-nama">{{ $siswa['nama'] }}</td>
                        <td class="col-gender">
                            <span class="gender-badge {{ $siswa['jenis_kelamin'] === 'L' ? 'gender-l' : 'gender-p' }}">
                                {{ $siswa['jenis_kelamin'] === 'L' ? 'L' : 'P' }}
                            </span>
                        </td>
                        <td class="col-status">
                            <span class="status-badge status-{{ $siswa['status'] }}">
                                {{ $siswa['status_text'] }}
                            </span>
                        </td>
                        <td class="col-jam">{{ $siswa['jam_datang'] ? \Carbon\Carbon::parse($siswa['jam_datang'])->format('H:i') : '-' }}</td>
                        <td class="col-jam">{{ $siswa['jam_pulang'] ? \Carbon\Carbon::parse($siswa['jam_pulang'])->format('H:i') : '-' }}</td>
                        <td class="col-ket">{{ $siswa['keterangan'] ?: '-' }}</td>
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
    
    <div class="printed-at">
        Dicetak pada: {{ now()->locale('id')->translatedFormat('l, d F Y H:i') }}
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
