<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryIds = Category::pluck('id')->toArray();
        $authorIds = Author::pluck('id')->toArray();
        
        if (empty($categoryIds) || empty($authorIds)) {
            $this->command->error('Pastikan data kategori dan author sudah di-seed.');
            return;
        }

        $total = 100000;
        $batch = 5000;

        for ($i = 0; $i < $total; $i += $batch) {
            Book::factory()->count($batch)->state(function () use ($categoryIds, $authorIds) {
                return [
                    'category_id' => fake()->randomElement($categoryIds),
                    'author_id' => fake()->randomElement($authorIds)
                ];
            })->create();

            $this->command->info(" Batch " . (($i / $batch) + 1) . " selesai (" . ($i + $batch) . " data total)");
        }
        $this->command->info("Data buku berhasil dibuat");
    }
}
