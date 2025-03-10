<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookSiswa;
use App\Models\BookGuru;
use App\Models\BookPerpus;
use App\Models\BookNonAkademik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
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
    
            // Jika kategori NA, sekolah menjadi null
            if ($validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }
    
            // Simpan file cover dengan nama asli di folder 'covers'
            if ($request->hasFile('cover')) {
                $coverFile = $request->file('cover');
                $coverFileName = $coverFile->getClientOriginalName(); // Ambil nama asli file
                $validateData['cover'] = $coverFile->storeAs('covers', $coverFileName, 'public');
            }
    
            // Simpan file isi (PDF) dengan nama asli di folder 'books'
            if ($request->hasFile('isi')) {
                $isiFile = $request->file('isi');
                $isiFileName = $isiFile->getClientOriginalName(); // Ambil nama asli file
                $validateData['isi'] = $isiFile->storeAs('books', $isiFileName, 'public');
            }
    
            // Simpan ke tabel utama
            $book = Book::create($validateData);
    
            // Tambahkan book_id ke data yang akan dimasukkan ke tabel lain
            $validateData['book_id'] = $book->id;
    
            // Buku kategori NA hanya masuk ke Perpus dan Non Akademik
            if ($validateData['kategori'] === 'NA') {
                BookNonAkademik::create($validateData);
            } else {
                // Buku kategori I-XII masuk ke Siswa dan Guru
                BookSiswa::create($validateData);
                BookGuru::create($validateData);
            }
    
            // Semua buku tetap masuk ke tabel Perpus
            BookPerpus::create($validateData);
    
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
            $book = Book::findOrFail($id);

            return response()->json([
                'book' => $book,
                'pdf_url' => $book->isi ? Storage::url($book->isi) : null,
                'cover_url' => $book->cover ? Storage::url($book->cover) : null,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // 1️⃣ Cari buku utama di tabel `books`
            $book = Book::findOrFail($id);
    
            // 2️⃣ Validasi data yang bisa diubah
            $validateData = $request->validate([
                'judul' => 'sometimes|required',
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
    
            // Jika kategori NA, kosongkan sekolah
            if ($validateData['kategori'] === 'NA') {
                $validateData['sekolah'] = null;
            }
    
            // 3️⃣ Update cover jika ada perubahan
            if ($request->hasFile('cover')) {
                // Hapus file cover lama
                if ($book->cover) {
                    Storage::disk('public')->delete($book->cover);
                }
                // Simpan file cover baru dengan nama asli di folder 'covers'
                $coverFile = $request->file('cover');
                $coverFileName = $coverFile->getClientOriginalName(); // Ambil nama asli file
                $validateData['cover'] = $coverFile->storeAs('covers', $coverFileName, 'public');
            }
    
            // 4️⃣ Update isi (PDF) jika ada perubahan
            if ($request->hasFile('isi')) {
                // Hapus file isi lama
                if ($book->isi) {
                    Storage::disk('public')->delete($book->isi);
                }
                // Simpan file isi baru dengan nama asli di folder 'books'
                $isiFile = $request->file('isi');
                $isiFileName = $isiFile->getClientOriginalName(); // Ambil nama asli file
                $validateData['isi'] = $isiFile->storeAs('books', $isiFileName, 'public');
            }
    
            // 5️⃣ Update buku utama di tabel `books`
            $book->update($validateData);
    
            // 6️⃣ Update semua buku dengan `book_id` yang sama di tabel-tabel lain
            BookSiswa::where('book_id', $book->id)->update($validateData);
            BookGuru::where('book_id', $book->id)->update($validateData);
            BookPerpus::where('book_id', $book->id)->update($validateData);
            BookNonAkademik::where('book_id', $book->id)->update($validateData);
    
            return response()->json([
                'message' => 'Buku berhasil diperbarui di semua kategori',
                'book' => $book,
                'pdf_url' => Storage::url($book->isi),
                'cover_url' => Storage::url($book->cover),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    

    public function destroy($id)
    {
        try {
            // 1️⃣ Cari buku utama di tabel `books`
            $book = Book::findOrFail($id);
    
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
    
            // 4️⃣ Hapus buku utama di tabel `books`
            $book->delete();
    
            return response()->json([
                'message' => 'Buku dan semua data terkait berhasil dihapus'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
    

      public function getSiswaBooks()
    {
        return response()->json(DB::table('book_siswas')->get(), 200);
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

}
