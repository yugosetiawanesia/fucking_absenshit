<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanHarianExport implements FromArray, WithStyles, WithEvents, WithColumnWidths, ShouldAutoSize
{
    public function __construct(
        protected array $reportData,
    ) {
    }

    public function array(): array
    {
        $rows = [];

        // Title row starting at column C
        $rows[] = ['', '', 'LAPORAN HARIAN ABSENSI'];
        $rows[] = ['Tanggal', $this->reportData['tanggal_format'] ?? ''];
        $rows[] = ['Kelas', $this->reportData['kelas']['nama_kelas'] ?? ''];

        $semester = '';
        if (!empty($this->reportData['semester_active'])) {
            $semesterActive = $this->reportData['semester_active'];
            $semester = ucfirst((string) ($semesterActive['semester'] ?? '')) . ' ' . ((string) ($semesterActive['tahun_ajaran'] ?? ''));
        }
        $rows[] = ['Semester', $semester];

        $rows[] = [];

        // Header row
        $header = ['NO', 'NIS', 'NAMA SISWA', 'L/P', 'Status', 'Datang', 'Pulang', 'Keterangan'];
        $rows[] = $header;

        // Data rows
        $dataRows = $this->reportData['rows'] ?? [];
        foreach ($dataRows as $index => $siswa) {
            $line = [
                $index + 1,
                $siswa['nis'] ?? '',
                $siswa['nama'] ?? '',
                $siswa['jenis_kelamin'] ?? '',
                $siswa['status_text'] ?? '',
                !empty($siswa['jam_datang']) ? \Carbon\Carbon::parse($siswa['jam_datang'])->format('H:i') : '-',
                !empty($siswa['jam_pulang']) ? \Carbon\Carbon::parse($siswa['jam_pulang'])->format('H:i') : '-',
                $siswa['keterangan'] ?: '-',
            ];
            $rows[] = $line;
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 18,
            'C' => 35,
            'D' => 7,
            'E' => 14,
            'F' => 14,
            'G' => 14,
            'H' => 30,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $dataRows = $this->reportData['rows'] ?? [];
                $studentCount = is_countable($dataRows) ? count($dataRows) : 0;

                $headerRow = 5;
                $firstDataRow = 6;
                $lastRow = $headerRow + $studentCount;

                $lastCol = 'H';

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
                $sheet->getStyle('E'.$headerRow.':E'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('F'.$headerRow.':G'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('H'.$headerRow.':H'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('C'.$firstDataRow.':C'.$lastRow)->getAlignment()->setWrapText(true);
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
