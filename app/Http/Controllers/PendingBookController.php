<?php

namespace App\Http\Controllers;

use App\Models\PendingBook;
use App\Models\Book;
use App\Models\BookSiswa;
use App\Models\BookGuru;
use App\Models\BookPerpus;
use App\Models\Book1Class;
use App\Models\Book2Class;
use App\Models\Book3Class;
use App\Models\Book4Class;
use App\Models\Book5Class;
use App\Models\Book6Class;
use App\Models\Book7Class;
use App\Models\Book8Class;
use App\Models\Book9Class;
use App\Models\Book10Class;
use App\Models\Book11Class;
use App\Models\Book12Class;
use App\Models\BookNonAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\SupabaseStorageService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PendingBookController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseStorageService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function index()
    {
        try {
            $pendingBooks = PendingBook::all();
            return response()->json([
                'success' => true,
                'data' => $pendingBooks
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data buku yang tertunda.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        Log::info('PendingBookController@store: Start', $request->all());

        $validateData = $request->validate([
            'judul' => 'required',
            'kategori' => 'nullable|string',
            'kelas' => 'nullable|string',
            'penulis' => 'nullable|string',
            'penerbit' => 'nullable|string',
            'deskripsi' => 'nullable|string',
            'isi' => 'required|file|mimes:pdf|max:51200',
        ]);

        try {
            if ($request->hasFile('isi')) {
                Log::info('PendingBookController@store: Uploading PDF...');
                $isiFile = $request->file('isi');
                // Temporarily just storing it with random name locally since we can't test Supabase here perfectly, or we use Supabase:
                $isiUrl = $this->supabase->upload($isiFile, 'pdf_buku', 'books/pending_' . $isiFile->hashName());
                if (!$isiUrl) throw new Exception('Gagal mengupload PDF ke Supabase');
                $validateData['isi'] = $isiUrl;
                Log::info('PendingBookController@store: PDF uploaded', ['url' => $isiUrl]);
            }

            $pendingBook = PendingBook::create($validateData);

            return response()->json([
                'success' => true,
                'message' => 'Buku berhasil diajukan dan menunggu persetujuan.',
                'data' => $pendingBook,
            ], 201);
        } catch (Exception $e) {
            Log::error('PendingBookController@store: Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengajukan buku',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $pending = PendingBook::findOrFail($id);
            
            // Map the pending book data into the format expected by the Books table
            $bookData = [
                'judul' => $pending->judul,
                'deskripsi' => $pending->deskripsi,
                'sekolah' => null, // Will determine below
                'kategori' => in_array($pending->kategori, ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII','NA']) ? $pending->kategori : 'NA',
                'penerbit' => $pending->penerbit ?: 'Tidak Diketahui',
                'penulis' => $pending->penulis ?: 'Tidak Diketahui',
                'tahun' => date('Y'),
                'ISBN' => 'PENDING-' . uniqid(), // Placeholder
                'cover' => 'https://via.placeholder.com/300x400?text=Cover+Buku', // Default cover
                'isi' => $pending->isi,
            ];

            // Determine sekolah
            if ($bookData['kategori'] === 'NA') {
                $bookData['sekolah'] = null;
            } else {
                if (in_array($bookData['kategori'], ['I','II','III','IV','V','VI'])) $bookData['sekolah'] = 'SD';
                if (in_array($bookData['kategori'], ['VII','VIII','IX'])) $bookData['sekolah'] = 'SMP';
                if (in_array($bookData['kategori'], ['X','XI','XII'])) $bookData['sekolah'] = 'SMK';
            }

            $book = Book::create($bookData);
            $bookData['book_id'] = $book->id;

            // Save to category specific table
            if ($bookData['kategori'] === 'NA') {
                BookNonAkademik::create($bookData);
            } else {
                switch ($bookData['kategori']) {
                    case 'I': Book1Class::create($bookData); break;
                    case 'II': Book2Class::create($bookData); break;
                    case 'III': Book3Class::create($bookData); break;
                    case 'IV': Book4Class::create($bookData); break;
                    case 'V': Book5Class::create($bookData); break;
                    case 'VI': Book6Class::create($bookData); break;
                    case 'VII': Book7Class::create($bookData); break;
                    case 'VIII': Book8Class::create($bookData); break;
                    case 'IX': Book9Class::create($bookData); break;
                    case 'X': Book10Class::create($bookData); break;
                    case 'XI': Book11Class::create($bookData); break;
                    case 'XII': Book12Class::create($bookData); break;
                }
            }

            // Save to related role tables
            BookPerpus::create($bookData);
            if ($bookData['kategori'] !== 'NA') {
                BookSiswa::create($bookData);
                BookGuru::create($bookData);
            }

            $pending->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Buku berhasil disetujui',
                'book' => $book
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('PendingBookController@approve: Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui buku',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function decline($id)
    {
        try {
            $pending = PendingBook::findOrFail($id);
            // Optionally delete the file from supabase
            // if ($pending->isi && str_starts_with($pending->isi, 'http')) {
            //     $this->supabase->delete('pdf_buku', $pending->isi);
            // }
            $pending->delete();

            return response()->json([
                'success' => true,
                'message' => 'Ajuan buku ditolak dan dihapus.'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menolak buku',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
