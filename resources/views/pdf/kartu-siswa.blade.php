<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kartu Pelajar</title>
    <style>
        @page { margin: 10px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        .card { border: 1px solid #111827; border-radius: 8px; padding: 10px; }
        .barcode { text-align: center; margin-bottom: 8px; }
        .barcode img { width: 100%; max-width: 260px; height: auto; }
        .row { margin-top: 4px; }
        .label { display: inline-block; width: 42px; color: #374151; }
        .value { font-weight: 600; }
        .header { text-align: center; font-weight: 700; font-size: 11px; margin-bottom: 8px; }
        .school { font-size: 9px; font-weight: 700; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="school">SMPN 1 PIANI</div>
            <div>Kartu Pelajar</div>
        </div>

        <div class="barcode">
            <img src="{{ $barcodeDataUri }}" alt="BARCODE">
            <div style="margin-top: 2px; font-size: 9px;">{{ $siswa->barcode }}</div>
        </div>

        <div class="row"><span class="label">Nama</span>: <span class="value">{{ $siswa->nama }}</span></div>
        <div class="row"><span class="label">NIS</span>: <span class="value">{{ $siswa->nis }}</span></div>
        <div class="row"><span class="label">Kelas</span>: <span class="value">{{ $siswa->kelas?->nama_kelas }}</span></div>
    </div>
</body>
</html>
