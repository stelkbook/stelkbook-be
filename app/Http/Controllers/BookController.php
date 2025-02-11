<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function store(Request $request){
        $validateData = $request->validate([
            'judul' => 'required',
            'deskripsi' => 'required',
            'sekolah' => 'required|in:SD,SMP,SMK',
            'kategori' => 'required|in:I,II,III,IV,V,VI,VII,VIII,IX,X,XI,XII,NA',    
            'penerbit' => 'required',
            'penulis' => 'required',
            'tahun' => 'required|digits:4',
            'ISBN' => 'required',
            'cover' => 'required|image|mimes:jpeg,png,jpg',
            'isi' => 'required|file|mimes:pdf',
        ]);

        if ($request->hasFile('cover')) {
            $validateData['cover'] = $request->file('cover')->store('covers', 'public');
        }
        if ($request->hasFile('isi')) {
            $validateData['isi'] = $request->file('isi')->store('books', 'public');
        }

        $book = Book::create($validateData);

        return response()->json([
            'message' => 'Buku berhasil ditambahkan',
            'book' => $book,
            'pdf_url' => Storage::url($book->isi),
            'cover_url' => Storage::url($book->cover),
        ]);
    }

    public function show(Book $book){
        return response()->json([
            'book' => $book,
            'pdf_url' => $book->isi ? Storage::url($book->isi) : null,
            'cover_url' => $book->cover ? Storage::url($book->cover) : null,
        ]);
    }

    public function destroy(Book $book){
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }

        $book->delete();

        return response()->json(['message' => 'Buku berhasil dihapus']);
    }
}
