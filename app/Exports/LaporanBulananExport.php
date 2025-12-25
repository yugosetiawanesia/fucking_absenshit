<?php

namespace App\Exports;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanBulananExport implements FromArray, WithStyles, WithEvents, WithColumnWidths, ShouldAutoSize
{
    public function __construct(
        protected array $reportData,
    ) {
    }

    public function array(): array
    {
        $bulan = $this->reportData['bulan'] ?? CarbonImmutable::now()->format('Y-m');
        $tanggal = CarbonImmutable::createFromFormat('Y-m', $bulan);
        $period = CarbonPeriod::create($tanggal->startOfMonth(), $tanggal->endOfMonth());

        $days = [];
        foreach ($period as $date) {
            $days[] = $date->day;
        }

        $rows = [];

        // Keep title aligned with the table area (starts at column C)
        $rows[] = ['', '', 'LAPORAN ABSENSI BULANAN'];
        $rows[] = ['Bulan', $this->reportData['bulan_format'] ?? ''];
        $rows[] = ['Kelas', $this->reportData['kelas']['nama_kelas'] ?? ''];

        $semester = '';
        if (!empty($this->reportData['semester_active'])) {
            $semesterActive = $this->reportData['semester_active'];
            $semester = ucfirst((string) ($semesterActive['semester'] ?? '')) . ' ' . ((string) ($semesterActive['tahun_ajaran'] ?? ''));
        }
        $rows[] = ['Semester', $semester];

        $header = ['No', 'NIS', 'NAMA', 'L/P'];
        foreach ($days as $d) {
            $header[] = str_pad((string) $d, 2, '0', STR_PAD_LEFT);
        }
        $header[] = 'H';
        $header[] = 'I';
        $header[] = 'S';
        $header[] = 'A';
        $rows[] = $header;

        $rekapSiswa = $this->reportData['rekap_siswa'] ?? [];
        foreach ($rekapSiswa as $index => $rekap) {
            $line = [
                $index + 1,
                $rekap['nis'] ?? '',
                $rekap['nama'] ?? '',
                $rekap['jenis_kelamin'] ?? '',
            ];

            $detailHarian = $rekap['detail_harian'] ?? [];

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $status = $detailHarian[$dateStr]['status'] ?? 'alpa';

                $label = match ($status) {
                    'hadir' => 'H',
                    'izin' => 'I',
                    'sakit' => 'S',
                    'alpa' => 'A',
                    'libur' => 'L',
                    default => strtoupper(substr((string) $status, 0, 1)),
                };

                $line[] = $label;
            }

            $line[] = (int) ($rekap['hadir'] ?? 0);
            $line[] = (int) ($rekap['izin'] ?? 0);
            $line[] = (int) ($rekap['sakit'] ?? 0);
            $line[] = (int) ($rekap['alpa'] ?? 0);

            $rows[] = $line;
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 24,
            'C' => 26,
            'D' => 5,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $bulan = $this->reportData['bulan'] ?? CarbonImmutable::now()->format('Y-m');
                $tanggal = CarbonImmutable::createFromFormat('Y-m', $bulan);
                $period = CarbonPeriod::create($tanggal->startOfMonth(), $tanggal->endOfMonth());
                $dayCount = $period->count();

                $rekapSiswa = $this->reportData['rekap_siswa'] ?? [];
                $studentCount = is_countable($rekapSiswa) ? count($rekapSiswa) : 0;

                $headerRow = 5;
                $firstDataRow = 6;
                $lastRow = $headerRow + $studentCount;

                // Last column: A-D + days + 4 totals
                $lastColIndex = 4 + $dayCount + 4;
                $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);

                // Merge and style title row (start at column C)
                $sheet->mergeCells('C1:'.$lastCol.'1');
                $sheet->getRowDimension(1)->setRowHeight(20);

                $sheet->getRowDimension(2)->setRowHeight(16);
                $sheet->getRowDimension(3)->setRowHeight(16);
                $sheet->getRowDimension(4)->setRowHeight(16);

                // Make meta labels bold
                $sheet->getStyle('A2:A4')->getFont()->setBold(true);
                $sheet->getStyle('B2:B4')->getAlignment()->setWrapText(true);

                // Freeze panes: keep header row + first 4 columns
                $sheet->freezePane('E'.$firstDataRow);

                // Set column widths for day + totals columns
                for ($i = 5; $i <= $lastColIndex; $i++) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
                    $sheet->getColumnDimension($col)->setWidth(3);
                }

                // Header row styling
                $headerRange = 'A'.$headerRow.':'.$lastCol.$headerRow;
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(18);

                // Data range borders + alignment
                $dataRange = 'A'.$headerRow.':'.$lastCol.max($headerRow, $lastRow);
                $sheet->getStyle($dataRange)->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '111827'],
                        ],
                    ],
                ]);

                // Align key columns
                $sheet->getStyle('A'.$headerRow.':A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('B'.$headerRow.':B'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('C'.$headerRow.':C'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('D'.$headerRow.':D'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('C'.$firstDataRow.':C'.$lastRow)->getAlignment()->setWrapText(true);

                // Center day columns + totals (both header and data)
                $sheet->getStyle('E'.$headerRow.':'.$lastCol.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('C1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('C1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }
}
