<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Bulanan - {{ $reportData['bulan_format'] }}</title>
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

        .printed-at {
            position: fixed;
            right: 8mm;
            bottom: 6mm;
            font-size: 8px;
            color: #666;
            text-align: right;
        }
        
        .report-info {
            font-size: 10px;
            color: #666;
            margin-bottom: 2px;
        }
        
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
            width: 45mm;
            text-align: left;
            padding-left: 3px;
        }

        .attendance-table th.col-lp,
        .attendance-table td.col-lp {
            width: 10mm;
        }

        .attendance-table th.col-total,
        .attendance-table td.col-total {
            width: 8mm;
        }
        
        .attendance-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }
        
        .attendance-table th.student-info {
            text-align: left;
            min-width: 70px;
            max-width: 70px;
            font-size: 8px;
        }
        
        .attendance-table th.date-header {
            min-width: 9px;
            max-width: 9px;
            writing-mode: horizontal-tb;
            text-orientation: mixed;
            transform: none;
            height: 16px;
        }
        
        .student-name {
            text-align: left !important;
            font-weight: bold;
            padding: 3px !important;
            font-size: 8px;
            white-space: normal;
            word-break: break-word;
            line-height: 1.05;
        }
        
        .student-nis {
            text-align: left !important;
            font-size: 8px;
            color: #666;
            white-space: nowrap;
        }
        
        .student-gender {
            display: inline-block;
            padding: 1px 3px;
            border-radius: 6px;
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
        
        .status-hadir {
            background-color: #dcfce7;
            color: #166534;
            font-weight: bold;
        }
        
        .status-izin {
            background-color: #dbeafe;
            color: #1e40af;
            font-weight: bold;
        }
        
        .status-sakit {
            background-color: #fef3c7;
            color: #92400e;
            font-weight: bold;
        }
        
        .status-alpa {
            background-color: #fee2e2;
            color: #991b1b;
            font-weight: bold;
        }
        
        .status-libur {
            background-color: #f3e8ff;
            color: #7c3aed;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }
        
        .print-button {
            margin-bottom: 10px;
            padding: 6px 12px;
            background-color: #1e40af;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 10px;
        }
        
        .legend {
            margin-bottom: 10px;
            font-size: 8px;
        }
        
        .legend-item {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 3px;
        }
        
        .legend-color {
            display: inline-block;
            width: 10px;
            height: 10px;
            margin-right: 2px;
            border: 1px solid #000;
        }
    </style>
</head>
<body class="{{ !empty($isPdf) ? 'pdf' : '' }}">
    @if(empty($isPdf))
        <button class="print-button no-print" onclick="window.print()">Cetak Laporan</button>
    @endif
    
    <div class="header">
        @php
            $logoPath = $reportData['school_logo_path'] ?? null;
            $logoUrl = null;
            if (!empty($logoPath)) {
                if (!empty($isPdf)) {
                    $localPath = public_path('storage/' . ltrim($logoPath, '/'));
                    if (is_file($localPath)) {
                        $logoUrl = 'file://' . $localPath;
                    }
                } else {
                    $logoUrl = str_starts_with($logoPath, 'http')
                        ? $logoPath
                        : asset('storage/' . ltrim($logoPath, '/'));
                }
            }
        @endphp

        @if(!empty($logoUrl))
            <img class="header-logo" src="{{ $logoUrl }}" alt="Logo" />
        @else
            <div class="header-logo"></div>
        @endif

        <div class="header-text">
            <div class="school-name">{{ $reportData['school_name'] }}</div>
            <div class="report-title">LAPORAN ABSENSI BULANAN</div>
            @if(!empty($reportData['school_address']))
                <div class="school-address">{{ $reportData['school_address'] }}</div>
            @endif
            <div class="report-info">
                Bulan : {{ $reportData['bulan_format'] }} | Kelas : {{ $reportData['kelas']['nama_kelas'] }}
                @if($reportData['semester_active'])
                    | Semester : {{ ucfirst($reportData['semester_active']['semester']) }} {{ $reportData['semester_active']['tahun_ajaran'] }}
                @endif
            </div>
        </div>
    </div>
    
    <div class="legend">
        <div class="legend-item">
            <span class="legend-color status-hadir"></span>H
        </div>
        <div class="legend-item">
            <span class="legend-color status-izin"></span>I
        </div>
        <div class="legend-item">
            <span class="legend-color status-sakit"></span>S
        </div>
        <div class="legend-item">
            <span class="legend-color status-alpa"></span>A
        </div>
        <div class="legend-item">
            <span class="legend-color status-libur"></span>L
        </div>
    </div>
    
    <div class="table-container">
        <table class="attendance-table">
                    <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-nis">NIS</th>
                    <th class="col-nama">Nama</th>
                    <th class="col-lp">L/P</th>
                    @php
                        $bulan = $reportData['bulan'] ?? \Carbon\CarbonImmutable::now()->format('Y-m');
                        $tanggal = \Carbon\CarbonImmutable::createFromFormat('Y-m', $bulan);
                        $period = \Carbon\CarbonPeriod::create($tanggal->startOfMonth(), $tanggal->endOfMonth());
                    @endphp
                    @foreach($period as $date)
                        <th class="date-header">{{ $date->format('d') }}</th>
                    @endforeach
                    <th class="col-total">H</th>
                    <th class="col-total">I</th>
                    <th class="col-total">S</th>
                    <th class="col-total">A</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData['rekap_siswa'] as $index => $rekap)
                    <tr>
                        <td class="col-no text-center">{{ $index + 1 }}</td>
                        <td class="col-nis student-nis">{{ $rekap['nis'] }}</td>
                        <td class="col-nama student-name">{{ $rekap['nama'] }}</td>
                        <td class="col-lp text-center">
                            <span class="student-gender {{ $rekap['jenis_kelamin'] === 'L' ? 'gender-l' : 'gender-p' }}">
                                {{ $rekap['jenis_kelamin'] === 'L' ? 'L' : 'P' }}
                            </span>
                        </td>

                        @php
                            $totalH = 0;
                            $totalI = 0;
                            $totalS = 0;
                            $totalA = 0;
                        @endphp
                        
                        @foreach($period as $date)
                            @php
                                $dateStr = $date->format('Y-m-d');
                                $detail = $rekap['detail_harian'][$dateStr] ?? null;
                                $status = $detail['status'] ?? 'alpa';

                                if ($status === 'hadir') {
                                    $totalH++;
                                } elseif ($status === 'izin') {
                                    $totalI++;
                                } elseif ($status === 'sakit') {
                                    $totalS++;
                                } elseif ($status === 'alpa') {
                                    $totalA++;
                                }
                            @endphp

                            <td class="status-{{ $status }}" title="{{ $detail['keterangan'] ?? '' }}">
                                @php
                                    $label = match ($status) {
                                        'hadir' => 'H',
                                        'izin' => 'I',
                                        'sakit' => 'S',
                                        'alpa' => 'A',
                                        'libur' => 'L',
                                        default => strtoupper(substr((string) $status, 0, 1)),
                                    };
                                @endphp
                                {{ $label }}
                            </td>
                        @endforeach

                        <td class="col-total">{{ $totalH }}</td>
                        <td class="col-total">{{ $totalI }}</td>
                        <td class="col-total">{{ $totalS }}</td>
                        <td class="col-total">{{ $totalA }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 8 + $period->count() }}" class="text-center" style="padding: 15px; font-size: 10px;">
                            Tidak ada data siswa untuk kelas ini
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="printed-at">
        Dicetak pada: {{ now()->locale('id')->translatedFormat('l, d F Y H:i') }}
    </div>
    
    @if(empty($isPdf))
        <script>
            // Auto print when page loads
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    @endif
</body>
</html>
