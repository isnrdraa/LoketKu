<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Admin utama
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Kasir contoh
        User::firstOrCreate(
            ['username' => 'kasir1'],
            [
                'name' => 'Kasir 1',
                'password' => bcrypt('kasir123'),
                'role' => 'cashier',
                'is_active' => true,
            ]
        );

        // Default pengaturan toko
        $defaults = [
            'store_name'    => config('app.name', 'LoketKu'),
            'store_address' => config('app.store_address', 'Alamat Toko Anda'),
            'store_phone'   => config('app.store_phone', '08123456789'),
            'store_footer'  => config('app.store_footer', 'Terima kasih telah berkunjung!'),
            'timezone'      => env('APP_TIMEZONE', 'Asia/Jakarta'),
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
