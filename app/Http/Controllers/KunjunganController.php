<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\Kunjungan;
use App\Models\KunjunganBook;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;


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
                DB::raw("TO_CHAR(tanggal_kunjungan, 'YYYY-MM-DD') as name"),
                DB::raw('COUNT(*) as pengunjung')
            )
            ->whereDate('tanggal_kunjungan', '>=', $now->copy()->subDays(6)->toDateString())
            ->groupBy('name')
            ->orderBy('name')
            ->get();
    
        // Rekap bulanan di tahun berjalan
        $bulanan = DB::table('kunjungans')
            ->select(
                DB::raw("EXTRACT(MONTH FROM tanggal_kunjungan) as bulan"),
                DB::raw("TRIM(TO_CHAR(tanggal_kunjungan, 'Month')) as name"),
                DB::raw('COUNT(*) as pengunjung')
            )
            ->whereYear('tanggal_kunjungan', $now->year)
            ->groupBy('bulan', 'name')
            ->orderBy('bulan')
            ->get();
    
        // Rekap tahunan semua tahun
        $tahunan = DB::table('kunjungans')
            ->select(
                DB::raw("EXTRACT(YEAR FROM tanggal_kunjungan) as name"),
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

// Menampilkan semua kunjungan buku

    public function indexKunjunganBook()
    {
        // Ambil semua data KunjunganBook dengan relasi book
        $kunjunganBooks = KunjunganBook::with('book')->latest()->get();

        // Transformasi untuk menambahkan URL cover
        $kunjunganBooks->transform(function ($item) {
            // Periksa apakah book ada dan memiliki cover
            if ($item->book && $item->book->cover) {
                $cover = trim($item->book->cover);
                if (str_starts_with($cover, 'http://') || str_starts_with($cover, 'https://')) {
                    $item->cover_url = $cover;
                } elseif (str_starts_with($cover, 'covers/') || str_starts_with($cover, 'books/')) {
                    $item->cover_url = $cover;
                } else {
                    $item->cover_url = Storage::url($cover);
                }
            } else {
                $item->cover_url = null;
            }
            return $item;
        });

        return response()->json([
            'message' => 'Data kunjungan buku berhasil diambil.',
            'data' => $kunjunganBooks
        ]);
    }





// Menampilkan kunjungan buku hari ini
    public function kunjunganBookHariIni()
    {
        $kunjunganBooks = KunjunganBook::with('book')
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->get();

        $kunjunganBooks->transform(function ($item) {
            if ($item->book && $item->book->cover) {
                $cover = trim($item->book->cover);
                if (str_starts_with($cover, 'http://') || str_starts_with($cover, 'https://')) {
                    $item->cover_url = $cover;
                } elseif (str_starts_with($cover, 'covers/') || str_starts_with($cover, 'books/')) {
                    $item->cover_url = $cover;
                } else {
                    $item->cover_url = Storage::url($cover);
                }
            } else {
                $item->cover_url = null;
            }
            return $item;
        });

        return response()->json([
            'message' => 'Data kunjungan buku hari ini berhasil diambil.',
            'data' => $kunjunganBooks
        ]);
    }


// Rekap total kunjungan per buku
    public function rekapKunjunganBook()
    {
        $rekap = DB::table('kunjungan_books')
            ->join('books', 'kunjungan_books.book_id', '=', 'books.id')
            ->select(
                'kunjungan_books.book_id',
                'books.judul',
                'books.cover',
                'books.kategori',
                'books.sekolah',
                DB::raw('ROUND(COUNT(*) / 2) as total_kunjungan')
            )
            ->groupBy('kunjungan_books.book_id', 'books.judul', 'books.cover', 'books.kategori', 'books.sekolah')
            ->orderByDesc('total_kunjungan')
            ->get();

        $rekap->transform(function ($item) {
            $cover = $item->cover ? trim($item->cover) : null;
            if ($cover && (str_starts_with($cover, 'http://') || str_starts_with($cover, 'https://'))) {
                $item->cover_url = $cover;
            } elseif ($cover && (str_starts_with($cover, 'covers/') || str_starts_with($cover, 'books/'))) {
                $item->cover_url = $cover;
            } else {
                $item->cover_url = $cover ? Storage::url($cover) : null;
            }
            return $item;
        });

        return response()->json([
            'message' => 'Rekap kunjungan buku berhasil diambil.',
            'data' => $rekap
        ]);
    }



}


