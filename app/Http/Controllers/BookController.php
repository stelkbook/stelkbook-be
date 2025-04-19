<?php

namespace App\Http\Controllers;

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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validateData = $request->validate([
                'judul' => 'required|unique:books',
                'deskripsi' => 'required',
                'sekolah' => 'nullable|in:SD,SMP,SMK',
                'kategori' => 'required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'required',
                'penulis' => 'required',
                'tahun' => 'required|digits:4',
                'ISBN' => 'required|string|unique:books',
                'cover' => 'required|image|mimes:jpeg,png,jpg',
                'isi' => 'required|file|mimes:pdf',
            ]);
    
            // Otomatis set sekolah berdasarkan kategori jika bukan NA
            if ($validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            } else {
                switch ($validateData['kategori']) {
                    case 'I':
                    case 'II':
                    case 'III':
                    case 'IV':
                    case 'V':
                    case 'VI':
                        $validateData['sekolah'] = 'SD';
                        break;
                    case 'VII':
                    case 'VIII':
                    case 'IX':
                        $validateData['sekolah'] = 'SMP';
                        break;
                    case 'X':
                    case 'XI':
                    case 'XII':
                        $validateData['sekolah'] = 'SMK';
                        break;
                }
            }
    
            // Simpan file cover
            if ($request->hasFile('cover')) {
                $coverFile = $request->file('cover');
                $coverFileName = $coverFile->getClientOriginalName();
                $validateData['cover'] = $coverFile->storeAs('covers', $coverFileName, 'public');
            }
    
            // Simpan file isi (PDF)
            if ($request->hasFile('isi')) {
                $isiFile = $request->file('isi');
                $isiFileName = $isiFile->getClientOriginalName();
                $validateData['isi'] = $isiFile->storeAs('books', $isiFileName, 'public');
            }
    
            // Simpan ke tabel utama
            $book = Book::create($validateData);
            $validateData['book_id'] = $book->id;
    
            // Simpan ke tabel sesuai kategori
            if ($validateData['kategori'] === 'NA') {
                BookNonAkademik::create($validateData);
            } else {
                switch ($validateData['kategori']) {
                    case 'I': Book1Class::create($validateData); break;
                    case 'II': Book2Class::create($validateData); break;
                    case 'III': Book3Class::create($validateData); break;
                    case 'IV': Book4Class::create($validateData); break;
                    case 'V': Book5Class::create($validateData); break;
                    case 'VI': Book6Class::create($validateData); break;
                    case 'VII': Book7Class::create($validateData); break;
                    case 'VIII': Book8Class::create($validateData); break;
                    case 'IX': Book9Class::create($validateData); break;
                    case 'X': Book10Class::create($validateData); break;
                    case 'XI': Book11Class::create($validateData); break;
                    case 'XII': Book12Class::create($validateData); break;
                }
            }
    
            // Semua buku masuk ke Perpus
            BookPerpus::create($validateData);
            BookGuru::create($validateData);
            // BookSiswa::create($validateData);

            // Buku non-NA juga masuk ke Guru & Siswa
            if ($validateData['kategori'] !== 'NA') {
                BookSiswa::create($validateData);
            }
    
            return response()->json([
                'message' => 'Buku berhasil ditambahkan',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan saat menambahkan buku',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    

    public function index()
    {
        $books = Book::all(); // Ambil semua buku dari database

        return response()->json([
            'success' => true,
            'books' => $books
        ], 200);
    }

    public function show($id)
    {
        try {
            $book = DB::table('books')->where('id', $id)->first();
    
            if (!$book) {
                return response()->json(['message' => 'Buku tidak ditemukan'], 404);
            }

            $book->pdf_url = Storage::url($book->isi);
            $book->cover_url = Storage::url($book->cover);
    
            return response()->json($book, 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }

    public function previewPdf($id)
    {
        try {
            $book = Book::findOrFail($id);
            
            if (!$book->isi) {
                return response()->json(['message' => 'File PDF tidak tersedia'], 404);
            }
    
            $path = storage_path('app/public/' . $book->isi);
    
            if (!file_exists($path)) {
                return response()->json(['message' => 'File PDF tidak ditemukan'], 404);
            }
    
            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $book = Book::findOrFail($id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // Jika cover tidak dikirim, gunakan cover yang sudah ada
            if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }
    
            // Handle file upload
            if ($request->hasFile('cover')) {
                if ($book->cover) {
                    Storage::disk('public')->delete($book->cover);
                }
                $coverFile = $request->file('cover');
                $validateData['cover'] = $coverFile->storeAs('covers', $coverFile->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) {
                    Storage::disk('public')->delete($book->isi);
                }
                $isiFile = $request->file('isi');
                $validateData['isi'] = $isiFile->storeAs('books', $isiFile->getClientOriginalName(), 'public');
            }
    
            $book->update($validateData);
            $validateData['book_id'] = $book->id;
    
            $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // Handle perubahan kategori
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari tabel lama
                if ($oldKategori !== 'NA' && isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                } elseif ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke tabel baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // Update tabel terkait jika kategori tidak berubah
                if ($book->kategori === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$book->kategori])) {
                        $classMapping[$book->kategori]::updateOrCreate(
                            ['book_id' => $book->id],
                            $validateData
                        );
                    }
                }
            }
    
            // Selalu update tabel Perpus
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui di semua kategori',
                'book' => $book,
                'pdf_url' => $book->isi ? Storage::url($book->isi) : null,
                'cover_url' => $book->cover ? Storage::url($book->cover) : null,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    

    

    public function updateKelas1Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas1 = Book1Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas1->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // Set sekolah berdasarkan kategori
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }
    
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $coverPath = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $isiPath = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
          
            
            $book->update($validateData);
            $validateData['book_id'] = $book->id;
    
            // ===== Kategori Mapping untuk Kelas 1 =====
            $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // Handle kategori
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    $classMapping[$validateData['kategori']]::create($validateData);
                }
            } else {
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }


    
    public function updateKelas2Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas2 = Book2Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas2->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // ===== Set sekolah berdasarkan kategori =====
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }

         
    
            // ===== File Handling =====
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $validateData['cover'] = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $validateData['isi'] = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }

            
            $book->update($validateData);
            $validateData['book_id'] = $book->id;
    
            $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // ===== Handle Kategori Berubah =====
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // ===== Kalau kategori tidak berubah =====
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    public function updateKelas3Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas3 = Book3Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas3->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // ===== Set sekolah berdasarkan kategori =====
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }

           

    
            // ===== File Handling =====
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $validateData['cover'] = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $validateData['isi'] = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
           
             $book->update($validateData);
             $validateData['book_id'] = $book->id;
     
             $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // ===== Handle Kategori Berubah =====
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // ===== Kalau kategori tidak berubah =====
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    public function updateKelas4Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas4 = Book4Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas4->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // ===== Set sekolah berdasarkan kategori =====
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }

           

    
            // ===== File Handling =====
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $validateData['cover'] = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $validateData['isi'] = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
            
            $book->update($validateData);
            $validateData['book_id'] = $book->id;
    
            $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // ===== Handle Kategori Berubah =====
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // ===== Kalau kategori tidak berubah =====
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    public function updateKelas5Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas5 = Book5Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas5->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // ===== Set sekolah berdasarkan kategori =====
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }

           
    
            // ===== File Handling =====
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $validateData['cover'] = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $validateData['isi'] = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
             
             $book->update($validateData);
             $validateData['book_id'] = $book->id;
     
             $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // ===== Handle Kategori Berubah =====
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // ===== Kalau kategori tidak berubah =====
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    public function updateKelas6Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas6 = Book6Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas6->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // ===== Set sekolah berdasarkan kategori =====
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }

            
    
            // ===== File Handling =====
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $validateData['cover'] = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $validateData['isi'] = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
             
             $book->update($validateData);
             $validateData['book_id'] = $book->id;
     
             $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // ===== Handle Kategori Berubah =====
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // ===== Kalau kategori tidak berubah =====
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    public function updateKelas7Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas7 = Book7Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas7->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // ===== Set sekolah berdasarkan kategori =====
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }

            
    
            // ===== File Handling =====
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $validateData['cover'] = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $validateData['isi'] = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
           
             $book->update($validateData);
             $validateData['book_id'] = $book->id;
     
             $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // ===== Handle Kategori Berubah =====
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // ===== Kalau kategori tidak berubah =====
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    public function updateKelas8Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas8 = Book8Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas8->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // ===== Set sekolah berdasarkan kategori =====
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }

           
    
            // ===== File Handling =====
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $validateData['cover'] = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $validateData['isi'] = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
          
            $book->update($validateData);
            $validateData['book_id'] = $book->id;
    
            $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // ===== Handle Kategori Berubah =====
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // ===== Kalau kategori tidak berubah =====
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    public function updateKelas9Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas9 = Book9Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas9->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // ===== Set sekolah berdasarkan kategori =====
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }

           
    
            // ===== File Handling =====
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $validateData['cover'] = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $validateData['isi'] = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
          
            $book->update($validateData);
            $validateData['book_id'] = $book->id;
    
            $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // ===== Handle Kategori Berubah =====
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // ===== Kalau kategori tidak berubah =====
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    public function updateKelas10Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas10 = Book10Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas10->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // ===== Set sekolah berdasarkan kategori =====
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }

            
    
            // ===== File Handling =====
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $validateData['cover'] = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $validateData['isi'] = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
            
            $book->update($validateData);
            $validateData['book_id'] = $book->id;
    
            $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // ===== Handle Kategori Berubah =====
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // ===== Kalau kategori tidak berubah =====
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    public function updateKelas11Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas11 = Book11Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas11->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // ===== Set sekolah berdasarkan kategori =====
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }
          
            // ===== File Handling =====
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $validateData['cover'] = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $validateData['isi'] = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
          
           $book->update($validateData);
           $validateData['book_id'] = $book->id;
   
           $classMapping = [
            'I' => Book1Class::class,
            'II' => Book2Class::class,
            'III' => Book3Class::class,
            'IV' => Book4Class::class,
            'V' => Book5Class::class,
            'VI' => Book6Class::class,
            'VII' => Book7Class::class,
            'VIII' => Book8Class::class,
            'IX' => Book9Class::class,
            'X' => Book10Class::class,
            'XI' => Book11Class::class,
            'XII' => Book12Class::class,
        ];
    
            // ===== Handle Kategori Berubah =====
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // ===== Kalau kategori tidak berubah =====
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    public function updateKelas12Book(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookKelas12 = Book12Class::findOrFail($id);
            $book = Book::findOrFail($bookKelas12->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // ===== Set sekolah berdasarkan kategori =====
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }

    
            // ===== File Handling =====
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $validateData['cover'] = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $validateData['isi'] = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
            
             $book->update($validateData);
             $validateData['book_id'] = $book->id;
     
             $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // ===== Handle Kategori Berubah =====
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus dari kategori lama
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambah ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // ===== Kalau kategori tidak berubah =====
                if ($validateData['kategori'] === 'NA') {
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // ===== Update Book Perpus (Selalu) =====
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateNonAkademikBook(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $bookNonAkademik = BookNonAkademik::findOrFail($id);
            $book = Book::findOrFail($bookNonAkademik->book_id);
            $oldKategori = $book->kategori;
    
            $validateData = $request->validate([
                'judul' => 'sometimes|required|unique:books,judul,' . $book->id,
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|nullable|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);
    
            // Set sekolah berdasarkan kategori
            $kategoriSekolahMap = [
                'I' => 'SD', 'II' => 'SD', 'III' => 'SD',
                'IV' => 'SD', 'V' => 'SD', 'VI' => 'SD',
                'VII' => 'SMP', 'VIII' => 'SMP', 'IX' => 'SMP',
                'X' => 'SMK', 'XI' => 'SMK', 'XII' => 'SMK',
            ];
    
            if (isset($validateData['kategori'])) {
                if ($validateData['kategori'] === 'NA') {
                    $validateData['sekolah'] = null;
                } else {
                    $validateData['sekolah'] = $kategoriSekolahMap[$validateData['kategori']] ?? null;
                }
            }

              // Jika cover tidak dikirim, gunakan cover yang sudah ada
              if (!isset($validateData['cover'])) {
                $validateData['cover'] = $book->cover;
            }
            if (!isset($validateData['isi'])) {
                $validateData['isi'] = $book->isi;
            }
    
            if (isset($validateData['kategori']) && $validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }   
    
            
    
            // Update file jika ada perubahan
            if ($request->hasFile('cover')) {
                if ($book->cover) Storage::disk('public')->delete($book->cover);
                $coverPath = $request->file('cover')->storeAs('covers', $request->file('cover')->getClientOriginalName(), 'public');
            }
    
            if ($request->hasFile('isi')) {
                if ($book->isi) Storage::disk('public')->delete($book->isi);
                $isiPath = $request->file('isi')->storeAs('books', $request->file('isi')->getClientOriginalName(), 'public');
            }
    
            
            $book->update($validateData);
            $validateData['book_id'] = $book->id;
    
            // Kategori Mapping untuk kelas-kelas
            $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            // Jika kategori berubah dari NA ke kategori lain
            if (isset($validateData['kategori']) && $validateData['kategori'] !== $oldKategori) {
                // Hapus buku dari kategori lama jika sebelumnya 'NA'
                if ($oldKategori === 'NA') {
                    BookNonAkademik::where('book_id', $book->id)->delete();
                } elseif (isset($classMapping[$oldKategori])) {
                    $classMapping[$oldKategori]::where('book_id', $book->id)->delete();
                }
    
                // Tambahkan ke kategori baru
                if ($validateData['kategori'] === 'NA') {
                    // Jika kategori baru 'NA', simpan buku di tabel Non Akademik
                    BookNonAkademik::create($validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    // Jika kategori baru adalah kategori kelas, tambahkan buku ke kategori kelas yang sesuai
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::create($validateData);
                    }
                }
            } else {
                // Jika kategori tidak berubah
                if ($validateData['kategori'] === 'NA') {
                    // Jika kategori tetap 'NA', update atau buat data di tabel Non Akademik
                    BookNonAkademik::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookSiswa::where('book_id', $book->id)->delete();
                    BookGuru::where('book_id', $book->id)->delete();
                } else {
                    // Jika kategori bukan 'NA', simpan buku ke kategori kelas yang sesuai
                    BookSiswa::updateOrCreate(['book_id' => $book->id], $validateData);
                    BookGuru::updateOrCreate(['book_id' => $book->id], $validateData);
                    if (isset($classMapping[$validateData['kategori']])) {
                        $classMapping[$validateData['kategori']]::updateOrCreate(['book_id' => $book->id], $validateData);
                    }
                }
            }
    
            // Selalu update atau buat data di Book Perpus
            BookPerpus::updateOrCreate(['book_id' => $book->id], $validateData);
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku Non Akademik berhasil diperbarui',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
    
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    
    
    

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            // 1️⃣ Cari buku utama di tabel `books`
            $book = Book::findOrFail($id);
            $kategori = $book->kategori;
    
            // 2️⃣ Hapus cover dan isi buku jika ada
            if ($book->cover) {
                Storage::disk('public')->delete($book->cover);
            }
            if ($book->isi) {
                Storage::disk('public')->delete($book->isi);
            }
    
            // 3️⃣ Hapus buku di semua tabel terkait
            BookSiswa::where('book_id', $book->id)->delete();
            BookGuru::where('book_id', $book->id)->delete();
            BookPerpus::where('book_id', $book->id)->delete();
            BookNonAkademik::where('book_id', $book->id)->delete();
    
            // 4️⃣ Hapus dari tabel kelas yang sesuai
            $classMapping = [
                'I' => Book1Class::class,
                'II' => Book2Class::class,
                'III' => Book3Class::class,
                'IV' => Book4Class::class,
                'V' => Book5Class::class,
                'VI' => Book6Class::class,
                'VII' => Book7Class::class,
                'VIII' => Book8Class::class,
                'IX' => Book9Class::class,
                'X' => Book10Class::class,
                'XI' => Book11Class::class,
                'XII' => Book12Class::class,
            ];
    
            if ($kategori !== 'NA' && isset($classMapping[$kategori])) {
                $classModel = $classMapping[$kategori];
                $classModel::where('book_id', $book->id)->delete();
            }
    
            // 5️⃣ Hapus buku utama di tabel `books`
            $book->delete();
    
            DB::commit();
    
            return response()->json([
                'message' => 'Buku dan semua data terkait berhasil dihapus'
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus buku',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

      public function getSiswaBooks()
    {
        return response()->json(DB::table('book_siswas')->get(), 200);
    }
      public function getKelas1Books()
    {
        return response()->json(DB::table('book_1_classes')->get(), 200);
    }
      public function getKelas2Books()
    {
        return response()->json(DB::table('book_2_classes')->get(), 200);
    }
      public function getKelas3Books()
    {
        return response()->json(DB::table('book_3_classes')->get(), 200);
    }
      public function getKelas4Books()
    {
        return response()->json(DB::table('book_4_classes')->get(), 200);
    }
      public function getKelas5Books()
    {
        return response()->json(DB::table('book_5_classes')->get(), 200);
    }
      public function getKelas6Books()
    {
        return response()->json(DB::table('book_6_classes')->get(), 200);
    }
      public function getKelas7Books()
    {
        return response()->json(DB::table('book_7_classes')->get(), 200);
    }
      public function getKelas8Books()
    {
        return response()->json(DB::table('book_8_classes')->get(), 200);
    }
      public function getKelas9Books()
    {
        return response()->json(DB::table('book_9_classes')->get(), 200);
    }
      public function getKelas10Books()
    {
        return response()->json(DB::table('book_10_classes')->get(), 200);
    }
      public function getKelas11Books()
    {
        return response()->json(DB::table('book_11_classes')->get(), 200);
    }
      public function getKelas12Books()
    {
        return response()->json(DB::table('book_12_classes')->get(), 200);
    }

    public function getGuruBooks()
    {
        return response()->json(DB::table('book_gurus')->get(), 200);
    }

    public function getPerpusBooks()
    {
        return response()->json(DB::table('book_perpuses')->get(), 200);
    }

    public function getNonAkademikBooks()
    {
        return response()->json(DB::table('book_non_akademiks')->get(), 200);
    }

    // 🔍 GET Buku berdasarkan ID di tabel SISWA
    public function getSiswaBookById($id)
    {
        $book = DB::table('book_siswas')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas1BookById($id)
    {
        $book = DB::table('book_1_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas2BookById($id)
    {
        $book = DB::table('book_2_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas3BookById($id)
    {
        $book = DB::table('book_3_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas4BookById($id)
    {
        $book = DB::table('book_4_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas5BookById($id)
    {
        $book = DB::table('book_5_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas6BookById($id)
    {
        $book = DB::table('book_6_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas7BookById($id)
    {
        $book = DB::table('book_7_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas8BookById($id)
    {
        $book = DB::table('book_8_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas9BookById($id)
    {
        $book = DB::table('book_9_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas10BookById($id)
    {
        $book = DB::table('book_10_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas11BookById($id)
    {
        $book = DB::table('book_11_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }
    public function getKelas12BookById($id)
    {
        $book = DB::table('book_12_classes')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }

    // 🔍 GET Buku berdasarkan ID di tabel GURU
    public function getGuruBookById($id)
    {
        $book = DB::table('book_gurus')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }



    // 🔍 GET Buku berdasarkan ID di tabel PERPUS
        public function getPerpusBookById($id)
        {
            $book = DB::table('book_perpuses')->where('id', $id)->first();
            return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
        }

    // 🔍 GET Buku berdasarkan ID di tabel NON AKADEMIK
    public function getNonAkademikBookById($id)
    {
        $book = DB::table('book_non_akademiks')->where('id', $id)->first();
        return $book ? response()->json($book, 200) : response()->json(['message' => 'Buku tidak ditemukan'], 404);
    }

    public function deleteNonAkademikBook($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookNA = BookNonAkademik::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookNA->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        BookPerpus::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookNA->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Non Akademik dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function deleteKelas1Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass1 = Book1Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass1->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book1Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass1->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 1 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteKelas2Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass2 = Book2Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass2->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book2Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass2->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 2 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteKelas3Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass3 = Book3Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass3->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book3Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass3->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 3 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteKelas4Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass4 = Book4Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass4->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book4Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass4->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 4 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteKelas5Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass5 = Book5Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass5->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book5Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass5->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 5 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteKelas6Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass6 = Book6Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass6->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book6Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass6->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 6 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteKelas7Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass7 = Book7Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass7->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book7Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass7->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 7 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteKelas8Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass8 = Book8Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass8->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book8Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass8->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 8 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteKelas9Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass9 = Book9Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass9->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book9Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass9->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 9 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteKelas10Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass10 = Book10Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass10->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book10Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass10->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 10 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteKelas11Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass11 = Book11Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass11->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book11Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass11->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 11 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}
    public function deleteKelas12Book($id)
{
    try {
        // Cari data di tabel book_non_akademiks berdasarkan ID
        $bookclass12 = Book12Class::findOrFail($id);

        // Cari data utama buku di tabel books berdasarkan book_id
        $book = Book::findOrFail($bookclass12->book_id);

        // Hapus file cover dan isi jika ada
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        // Hapus dari tabel book_perpuses
        Book9Class::where('book_id', $book->id)->delete();

        // Hapus dari tabel book_non_akademiks
        $bookclass12->delete();

        // Hapus dari tabel utama books
        $book->delete();

        return response()->json([
            'message' => 'Buku Kelas 12 dan data terkait berhasil dihapus'
        ]);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Buku tidak ditemukan'], 404);
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan saat menghapus buku',
            'error' => $e->getMessage()
        ], 500);
    }
}


}
