<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Kunjungan;
use Carbon\Carbon;

class KunjunganController extends Controller
{
    public function index()
    {
        $kunjungans = Kunjungan::with('user')->latest()->get();

        return response()->json([
            'message' => 'Data kunjungan berhasil diambil.',
            'data' => $kunjungans
        ]);
    }

    
    public function rekap()
    {
        // Ambil waktu sekarang dalam timezone Asia/Makassar
        $now = Carbon::now('Asia/Makassar');
    
        // Rekap harian 7 hari terakhir
        $harian = DB::table('kunjungans')
            ->select(
                DB::raw('DATE(tanggal_kunjungan) as name'),
                DB::raw('COUNT(*) as pengunjung')
            )
            ->whereDate('tanggal_kunjungan', '>=', $now->copy()->subDays(6)->toDateString())
            ->groupBy('name')
            ->orderBy('name')
            ->get();
    
        // Rekap bulanan di tahun berjalan
        $bulanan = DB::table('kunjungans')
            ->select(
                DB::raw('MONTH(tanggal_kunjungan) as bulan'),
                DB::raw('MONTHNAME(tanggal_kunjungan) as name'),
                DB::raw('COUNT(*) as pengunjung')
            )
            ->whereYear('tanggal_kunjungan', $now->year)
            ->groupBy('bulan', 'name')
            ->orderBy('bulan')
            ->get();
    
        // Rekap tahunan semua tahun
        $tahunan = DB::table('kunjungans')
            ->select(
                DB::raw('YEAR(tanggal_kunjungan) as name'),
                DB::raw('COUNT(*) as pengunjung')
            )
            ->groupBy('name')
            ->orderBy('name')
            ->get();
    
        return response()->json([
            'hari' => $harian,
            'bulan' => $bulanan,
            'tahun' => $tahunan,
        ]);
    }
    

public function indexHariIni()
{
    $today = now()->toDateString();

    $kunjungans = Kunjungan::with('user')
        ->whereDate('tanggal_kunjungan', $today)
        ->latest()
        ->get();

    return response()->json([
        'message' => 'Data kunjungan hari ini berhasil diambil.',
        'data' => $kunjungans
    ]);
}

public function rekap2()
{
    // Hari Ini
    $today = now()->toDateString();
    $yesterday = now()->subDay()->toDateString();

    $harian = [
        'hari_ini' => DB::table('kunjungans')
            ->whereDate('tanggal_kunjungan', $today)
            ->count(),
        'kemarin' => DB::table('kunjungans')
            ->whereDate('tanggal_kunjungan', $yesterday)
            ->count()
    ];

    // Bulan Ini vs Bulan Lalu
    $thisMonth = now()->month;
    $lastMonth = now()->subMonth()->month;
    $year = now()->year;
    $lastMonthYear = now()->subMonth()->year;

    $bulanan = [
        'bulan_ini' => DB::table('kunjungans')
            ->whereYear('tanggal_kunjungan', $year)
            ->whereMonth('tanggal_kunjungan', $thisMonth)
            ->count(),
        'bulan_lalu' => DB::table('kunjungans')
            ->whereYear('tanggal_kunjungan', $lastMonthYear)
            ->whereMonth('tanggal_kunjungan', $lastMonth)
            ->count()
    ];

    // Tahun Ini vs Tahun Lalu
    $tahun = now()->year;
    $tahunLalu = now()->subYear()->year;

    $tahunan = [
        'tahun_ini' => DB::table('kunjungans')
            ->whereYear('tanggal_kunjungan', $tahun)
            ->count(),
        'tahun_lalu' => DB::table('kunjungans')
            ->whereYear('tanggal_kunjungan', $tahunLalu)
            ->count()
    ];

    return response()->json([
        'harian' => $harian,
        'bulanan' => $bulanan,
        'tahunan' => $tahunan,
    ]);
}

}


