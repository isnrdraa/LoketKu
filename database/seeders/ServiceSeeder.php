<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $tiket = ServiceCategory::firstOrCreate(
            ['name' => 'Tiket Masuk'],
            ['description' => 'Tiket masuk area wisata', 'is_active' => true]
        );

        $wahana = ServiceCategory::firstOrCreate(
            ['name' => 'Wahana & Atraksi'],
            ['description' => 'Berbagai wahana dan atraksi wisata', 'is_active' => true]
        );

        $fnb = ServiceCategory::firstOrCreate(
            ['name' => 'Makanan & Minuman'],
            ['description' => 'Konsumsi di area wisata', 'is_active' => true]
        );

        $services = [
            // Tiket Masuk
            ['category_id' => $tiket->id, 'name' => 'Tiket Dewasa', 'price' => 25000],
            ['category_id' => $tiket->id, 'name' => 'Tiket Anak-anak', 'price' => 15000],
            ['category_id' => $tiket->id, 'name' => 'Tiket Lansia', 'price' => 10000],
            ['category_id' => $tiket->id, 'name' => 'Tiket Rombongan (>20 orang)', 'price' => 20000],

            // Wahana
            ['category_id' => $wahana->id, 'name' => 'Perahu Dayung', 'price' => 20000],
            ['category_id' => $wahana->id, 'name' => 'Flying Fox', 'price' => 35000],
            ['category_id' => $wahana->id, 'name' => 'ATV Trail', 'price' => 50000],
            ['category_id' => $wahana->id, 'name' => 'Kolam Renang', 'price' => 15000],

            // F&B
            ['category_id' => $fnb->id, 'name' => 'Air Mineral', 'price' => 5000],
            ['category_id' => $fnb->id, 'name' => 'Paket Makan Siang', 'price' => 30000],
        ];

        foreach ($services as $s) {
            Service::firstOrCreate(
                ['category_id' => $s['category_id'], 'name' => $s['name']],
                ['price' => $s['price'], 'is_active' => true]
            );
        }
    }
}
