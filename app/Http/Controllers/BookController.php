<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class BookController extends Controller
{
    public function store(Request $request){
        try {
            $validateData = $request->validate([
                'judul' => 'required',
                'deskripsi' => 'required',
                'sekolah' => 'required|in:SD,SMP,SMK',
                'kategori' => 'required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',    
                'penerbit' => 'required',
                'penulis' => 'required',
                'tahun' => 'required|digits:4',
                'ISBN' => 'required|unique:books',
                'cover' => 'required|image|mimes:jpeg,png,jpg',
                'isi' => 'required|file|mimes:pdf',
            ]);

            // Format folder penyimpanan
            $folderName = 'Buku_' . $validateData['kategori'] . '_' . str_replace(' ', '_', $validateData['judul']);

            if ($request->hasFile('cover')) {
                $validateData['cover'] = $request->file('cover')->store($folderName . '/covers', 'public');
            }
            if ($request->hasFile('isi')) {
                $validateData['isi'] = $request->file('isi')->store($folderName . '/books', 'public');
            }

            $book = Book::create($validateData);

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

    public function show($id){
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

    public function update(Request $request, $id){
        try {
            $book = Book::findOrFail($id);

            $validateData = $request->validate([
                'judul' => 'sometimes|required',
                'deskripsi' => 'sometimes|required',
                'sekolah' => 'sometimes|required|in:SD,SMP,SMK',
                'kategori' => 'sometimes|required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',    
                'penerbit' => 'sometimes|required',
                'penulis' => 'sometimes|required',
                'tahun' => 'sometimes|required|digits:4',
                'ISBN' => 'sometimes|required|unique:books,ISBN,' . $book->id,
                'cover' => 'sometimes|image|mimes:jpeg,png,jpg',
                'isi' => 'sometimes|file|mimes:pdf',
            ]);

            if ($request->hasFile('cover')) {
                if ($book->cover) {
                    Storage::disk('public')->delete($book->cover);
                }
                $validateData['cover'] = $request->file('cover')->store('books/covers', 'public');
            }
            if ($request->hasFile('isi')) {
                if ($book->isi) {
                    Storage::disk('public')->delete($book->isi);
                }
                $validateData['isi'] = $request->file('isi')->store('books/pdf', 'public');
            }

            $book->update($validateData);

            return response()->json([
                'message' => 'Buku berhasil diperbarui',
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

    public function destroy($id){
        try {
            $book = Book::findOrFail($id);

            if ($book->cover) {
                Storage::disk('public')->delete($book->cover);
            }
            if ($book->isi) {
                Storage::disk('public')->delete($book->isi);
            }

            $book->delete();

            return response()->json(['message' => 'Buku berhasil dihapus']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Buku tidak ditemukan'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Terjadi kesalahan', 'error' => $e->getMessage()], 500);
        }
    }
}
