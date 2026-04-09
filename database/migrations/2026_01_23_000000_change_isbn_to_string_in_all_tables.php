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
            'kunjungan_books',
        ];

        $driver = DB::connection()->getDriverName();

        foreach ($tables as $table) {
            // Using raw SQL because doctrine/dbal might not be installed
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `{$table}` MODIFY `ISBN` VARCHAR(255) NOT NULL");
            } elseif ($driver === 'pgsql') {
                DB::statement('ALTER TABLE "' . $table . '" ALTER COLUMN "ISBN" TYPE VARCHAR(255)');
            }
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

        $driver = DB::connection()->getDriverName();

        foreach ($tables as $table) {
            try {
                if ($driver === 'mysql') {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `ISBN` INT NOT NULL");
                } elseif ($driver === 'pgsql') {
                    // Casting might fail if non-numeric data exists, using USING clause to be safe or explicit
                    DB::statement('ALTER TABLE "' . $table . '" ALTER COLUMN "ISBN" TYPE INTEGER USING "ISBN"::integer');
                }
            } catch (\Exception $e) {
                // Log or ignore if data prevents reversion
            }
        }
    }
};
