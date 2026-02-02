<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'books',
            'book_siswas',
            'book_gurus',
            'book_perpuses',
            'book_non_akademiks',
            'book_1_classes',
            'book_2_classes',
            'book_3_classes',
            'book_4_classes',
            'book_5_classes',
            'book_6_classes',
            'book_7_classes',
            'book_8_classes',
            'book_9_classes',
            'book_10_classes',
            'book_11_classes',
            'book_12_classes',
        ];

        foreach ($tables as $table) {
            // Using raw SQL because doctrine/dbal is likely not installed
            // Modifying ISBN to VARCHAR(255) to support proper ISBN formats (including hyphens and length > 11 digits)
            DB::statement("ALTER TABLE `{$table}` MODIFY `ISBN` VARCHAR(255) NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'books',
            'book_siswas',
            'book_gurus',
            'book_perpuses',
            'book_non_akademiks',
            'book_1_classes',
            'book_2_classes',
            'book_3_classes',
            'book_4_classes',
            'book_5_classes',
            'book_6_classes',
            'book_7_classes',
            'book_8_classes',
            'book_9_classes',
            'book_10_classes',
            'book_11_classes',
            'book_12_classes',
        ];

        foreach ($tables as $table) {
            try {
                // Attempt to revert to INT, but this might fail if data contains non-numeric chars
                DB::statement("ALTER TABLE `{$table}` MODIFY `ISBN` INT NOT NULL");
            } catch (\Exception $e) {
                // Log or ignore if data prevents reversion
            }
        }
    }
};
