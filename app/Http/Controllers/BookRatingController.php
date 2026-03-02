<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookRating;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookRatingController extends Controller
{
    public function getUserRating($bookId)
    {
        $userId = Auth::id();
        $rating = BookRating::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->first();

        return response()->json([
            'rating' => $rating ? $rating->rating : 0,
            'review' => $rating ? $rating->review : ''
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string'
        ]);

        $userId = Auth::id();
        $bookId = $request->book_id;

        DB::beginTransaction();
        try {
            $rating = BookRating::updateOrCreate(
                ['user_id' => $userId, 'book_id' => $bookId],
                ['rating' => $request->rating, 'review' => $request->review]
            );

            // Recalculate average rating
            $stats = BookRating::where('book_id', $bookId)
                ->selectRaw('AVG(rating) as average_rating, COUNT(*) as total_ratings')
                ->first();

            $averageRating = round($stats->average_rating, 2);
            $totalRatings = $stats->total_ratings;

            // Update all book tables to maintain consistency
            $this->updateBookStats($bookId, $averageRating, $totalRatings);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rating berhasil disimpan',
                'average_rating' => $averageRating,
                'total_ratings' => $totalRatings
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store book rating: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan rating'
            ], 500);
        }
    }

    public function destroy($bookId)
    {
        $userId = Auth::id();

        DB::beginTransaction();
        try {
            $rating = BookRating::where('user_id', $userId)
                ->where('book_id', $bookId)
                ->first();

            if (!$rating) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rating tidak ditemukan'
                ], 404);
            }

            $rating->delete();

            // Recalculate average rating
            $stats = BookRating::where('book_id', $bookId)
                ->selectRaw('AVG(rating) as average_rating, COUNT(*) as total_ratings')
                ->first();

            $averageRating = $stats->total_ratings > 0 ? round($stats->average_rating, 2) : 0;
            $totalRatings = $stats->total_ratings;

            // Update all book tables to maintain consistency
            $this->updateBookStats($bookId, $averageRating, $totalRatings);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rating berhasil dihapus',
                'average_rating' => $averageRating,
                'total_ratings' => $totalRatings
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete book rating: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus rating'
            ], 500);
        }
    }

    private function updateBookStats($bookId, $averageRating, $totalRatings)
    {
        $updateData = [
            'average_rating' => $averageRating,
            'total_ratings' => $totalRatings
        ];

        // Main books table
        Book::where('id', $bookId)->update($updateData);

        // Sub-tables
        BookSiswa::where('book_id', $bookId)->update($updateData);
        BookGuru::where('book_id', $bookId)->update($updateData);
        BookPerpus::where('book_id', $bookId)->update($updateData);
        
        // Category tables
        Book1Class::where('book_id', $bookId)->update($updateData);
        Book2Class::where('book_id', $bookId)->update($updateData);
        Book3Class::where('book_id', $bookId)->update($updateData);
        Book4Class::where('book_id', $bookId)->update($updateData);
        Book5Class::where('book_id', $bookId)->update($updateData);
        Book6Class::where('book_id', $bookId)->update($updateData);
        Book7Class::where('book_id', $bookId)->update($updateData);
        Book8Class::where('book_id', $bookId)->update($updateData);
        Book9Class::where('book_id', $bookId)->update($updateData);
        Book10Class::where('book_id', $bookId)->update($updateData);
        Book11Class::where('book_id', $bookId)->update($updateData);
        Book12Class::where('book_id', $bookId)->update($updateData);
        BookNonAkademik::where('book_id', $bookId)->update($updateData);
    }
}
