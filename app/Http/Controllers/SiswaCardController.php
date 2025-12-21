<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorPNG;

class SiswaCardController extends Controller
{
    public function show(Request $request, Siswa $siswa)
    {
        $siswa->loadMissing(['kelas']);

        $generator = new BarcodeGeneratorPNG();
        $barcodePng = $generator->getBarcode($siswa->barcode, $generator::TYPE_CODE_128, 3, 80);
        $barcodeDataUri = 'data:image/png;base64,' . base64_encode($barcodePng);

        $pdf = Pdf::loadView('pdf.kartu-siswa', [
            'siswa' => $siswa,
            'barcodeDataUri' => $barcodeDataUri,
        ])->setPaper([0, 0, 243.78, 153.07], 'portrait');

        return $pdf->download('kartu-' . $siswa->nis . '.pdf');
    }
}
