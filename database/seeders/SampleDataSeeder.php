<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    /**
     * Buat kategori dan layanan contoh untuk wisata.
     */
    public function run(): void
    {
        $data = [
            [
                'name'        => 'Tiket Masuk',
                'description' => 'Tiket masuk kawasan wisata',
                'services'    => [
                    ['name' => 'Tiket Dewasa',      'price' => 25000],
                    ['name' => 'Tiket Anak-anak',   'price' => 15000],
                    ['name' => 'Tiket Manula (60+)', 'price' => 10000],
                ],
            ],
            [
                'name'        => 'Parkir',
                'description' => 'Layanan parkir kendaraan',
                'services'    => [
                    ['name' => 'Parkir Motor',  'price' => 5000],
                    ['name' => 'Parkir Mobil',  'price' => 10000],
                    ['name' => 'Parkir Bus/Truk', 'price' => 20000],
                ],
            ],
            [
                'name'        => 'Wahana',
                'description' => 'Tiket wahana dan permainan',
                'services'    => [
                    ['name' => 'Wahana A',   'price' => 20000],
                    ['name' => 'Wahana B',   'price' => 25000],
                    ['name' => 'Paket Wahana (3 wahana)', 'price' => 50000],
                ],
            ],
            [
                'name'        => 'Sewa Alat',
                'description' => 'Sewa perlengkapan wisata',
                'services'    => [
                    ['name' => 'Sewa Ban Renang', 'price' => 15000],
                    ['name' => 'Sewa Pelampung',  'price' => 10000],
                    ['name' => 'Sewa Payung',     'price' => 5000],
                ],
            ],
        ];

        foreach ($data as $cat) {
            $category = ServiceCategory::create([
                'name'        => $cat['name'],
                'description' => $cat['description'],
                'is_active'   => true,
            ]);

            foreach ($cat['services'] as $svc) {
                Service::create([
                    'service_category_id' => $category->id,
                    'name'      => $svc['name'],
                    'price'     => $svc['price'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
