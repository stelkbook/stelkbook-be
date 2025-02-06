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
            'kategori' => 'required|in:X,XI,XII,NA',    
            'penerbit' => 'required',
            'penulis' => 'required',
            'ISBN' => 'required',
            'isi' => 'required|file|mimes:pdf',
            'cover' => 'required|image|mimes:jpeg,png,jpg',
        ]);

        if ($request->hasFile('isi')) {
            $validateData['isi'] = $request->file('isi')->store('books', 'public');
        }
        if ($request->hasFile('cover')) {
            $validateData['cover'] = $request->file('cover')->store('covers', 'public');
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
        if ($book->isi) {
            Storage::disk('public')->delete($book->isi);
        }
        if ($book->cover) {
            Storage::disk('public')->delete($book->cover);
        }

        $book->delete();

        return response()->json(['message' => 'Buku berhasil dihapus']);
    }
}
