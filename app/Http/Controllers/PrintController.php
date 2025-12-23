<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function bulanan(Request $request)
    {
        // Get data from session
        $reportData = session('print_laporan_bulanan');
        
        if (!$reportData) {
            return redirect()->back()->with('error', 'Data laporan tidak ditemukan');
        }
        
        // Debug: Log the structure of data we're receiving
        \Log::info('PrintController - Report Data Structure:', [
            'has_rekap_siswa' => isset($reportData['rekap_siswa']),
            'rekap_siswa_count' => isset($reportData['rekap_siswa']) ? count($reportData['rekap_siswa']) : 0,
            'first_student_name' => isset($reportData['rekap_siswa'][0]) ? $reportData['rekap_siswa'][0]['nama'] ?? 'N/A' : 'N/A',
            'first_student_has_detail_harian' => isset($reportData['rekap_siswa'][0]['detail_harian']),
            'detail_harian_sample' => isset($reportData['rekap_siswa'][0]['detail_harian']) ? 
                array_slice($reportData['rekap_siswa'][0]['detail_harian'], 0, 3, true) : []
        ]);
        
        // Clear session after use
        session()->forget('print_laporan_bulanan');
        
        return view('print.laporan-bulanan', compact('reportData'));
    }
    
    public function harian(Request $request)
    {
        // Get data from session
        $reportData = session('print_laporan_harian');
        
        if (!$reportData) {
            return redirect()->back()->with('error', 'Data laporan tidak ditemukan');
        }
        
        // Clear session after use
        session()->forget('print_laporan_harian');
        
        return view('print.laporan-harian', compact('reportData'));
    }
}
