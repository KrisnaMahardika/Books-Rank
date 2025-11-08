<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Rating;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RatingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ini_set('memory_limit', '256M');

        $bookIds = Book::pluck('id')->toArray();
        if (empty($bookIds)) {
            $this->command->error('Pastikan data buku sudah di-seed.');
            return;
        }

        $total = 500000;
        $batch = 5000;

        for ($i = 0; $i < $total; $i += $batch) {
            Rating::factory()->count($batch)->state(function () use ($bookIds) {
                return [
                    'book_id' => fake()->randomElement($bookIds),
                ];
            })->create();

            $this->command->info(" Batch " . (($i / $batch) + 1) . " selesai (" . ($i + $batch) . " data total)");
        }
        $this->command->info("Data Rating berhasil dibuat");

    } 
}
