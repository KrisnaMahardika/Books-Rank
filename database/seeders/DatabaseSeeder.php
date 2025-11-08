<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Memulai proses seeding data...');

        $this->command->info('Menjalankan AuthorSeeder (1000 data)...');
        $this->call(AuthorSeeder::class);

        $this->command->info('Menjalankan CategorySeeder (3000 data)...');
        $this->call(CategorySeeder::class);

        $this->command->info('Menjalankan BookSeeder (100.000 data)...');
        $this->call(BookSeeder::class);

        $this->command->info('Menjalankan RatingSeeder (500.000 data)...');
        $this->call(RatingSeeder::class);

        $this->command->info('Semua data berhasil di-seed!');
    }
}
