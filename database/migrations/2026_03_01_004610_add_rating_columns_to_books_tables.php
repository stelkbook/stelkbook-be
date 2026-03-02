<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'books', 'book_siswas', 'book_gurus', 'book_perpuses',
            'book_1_classes', 'book_2_classes', 'book_3_classes', 'book_4_classes', 'book_5_classes', 'book_6_classes',
            'book_7_classes', 'book_8_classes', 'book_9_classes', 'book_10_classes', 'book_11_classes', 'book_12_classes',
            'book_non_akademiks'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->decimal('average_rating', 3, 2)->default(0)->after('isi');
                $table->integer('total_ratings')->default(0)->after('average_rating');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'books', 'book_siswas', 'book_gurus', 'book_perpuses',
            'book_1_classes', 'book_2_classes', 'book_3_classes', 'book_4_classes', 'book_5_classes', 'book_6_classes',
            'book_7_classes', 'book_8_classes', 'book_9_classes', 'book_10_classes', 'book_11_classes', 'book_12_classes',
            'book_non_akademiks'
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn(['average_rating', 'total_ratings']);
            });
        }
    }
};
