<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // ----------------------------------------------------------------
        // Units — satuan yang umum dipakai di industri F&B / retail
        // ----------------------------------------------------------------
        $units = [
            // Berat
            ['name' => 'Kilogram',   'symbol' => 'kg',   'description' => 'Satuan berat'],
            ['name' => 'Gram',       'symbol' => 'g',    'description' => 'Satuan berat kecil'],
            ['name' => 'Ton',        'symbol' => 'ton',  'description' => 'Satuan berat besar'],

            // Volume
            ['name' => 'Liter',      'symbol' => 'L',    'description' => 'Satuan volume'],
            ['name' => 'Mililiter',  'symbol' => 'mL',   'description' => 'Satuan volume kecil'],

            // Satuan hitung
            ['name' => 'Pcs',        'symbol' => 'pcs',  'description' => 'Per buah / satuan'],
            ['name' => 'Lusin',      'symbol' => 'lsn',  'description' => '12 buah'],
            ['name' => 'Pack',       'symbol' => 'pack', 'description' => 'Per kemasan pack'],
            ['name' => 'Dus',        'symbol' => 'dus',  'description' => 'Per dus / karton'],
            ['name' => 'Karton',     'symbol' => 'ktn',  'description' => 'Per karton besar'],
            ['name' => 'Sachet',     'symbol' => 'sct',  'description' => 'Per sachet'],
            ['name' => 'Botol',      'symbol' => 'btl',  'description' => 'Per botol'],
            ['name' => 'Kaleng',     'symbol' => 'klg',  'description' => 'Per kaleng'],
            ['name' => 'Lembar',     'symbol' => 'lbr',  'description' => 'Per lembar'],
            ['name' => 'Roll',       'symbol' => 'roll', 'description' => 'Per roll'],
            ['name' => 'Ikat',       'symbol' => 'ikt',  'description' => 'Per ikat'],
            ['name' => 'Porsi',      'symbol' => 'prs',  'description' => 'Per porsi saji'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(
                ['symbol' => $unit['symbol']],
                array_merge($unit, ['is_active' => true])
            );
        }

        // ----------------------------------------------------------------
        // Categories — kategori produk umum F&B / warehouse
        // ----------------------------------------------------------------
        $categories = [
            ['name' => 'Sayur & Buah',    'description' => 'Produk segar sayuran dan buah-buahan'],
            ['name' => 'Daging & Ikan',   'description' => 'Daging segar, frozen meat, dan produk ikan'],
            ['name' => 'Dairy & Telur',   'description' => 'Susu, keju, yogurt, dan telur'],
            ['name' => 'Bahan Kering',    'description' => 'Beras, tepung, gula, minyak, dan bahan pokok kering'],
            ['name' => 'Bumbu & Rempah',  'description' => 'Bumbu masak, rempah, dan saus'],
            ['name' => 'Minuman',         'description' => 'Minuman kemasan, jus, dan bahan minuman'],
            ['name' => 'Packaging',       'description' => 'Kemasan, wadah, plastik, dan label'],
            ['name' => 'Peralatan',       'description' => 'Peralatan dapur dan operasional'],
            ['name' => 'Kebersihan',      'description' => 'Produk kebersihan dan sanitasi'],
            ['name' => 'Lainnya',         'description' => 'Kategori umum untuk produk lainnya'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                array_merge($category, ['is_active' => true])
            );
        }

        $this->command->info('✓ ' . count($units) . ' satuan dan ' . count($categories) . ' kategori berhasil di-seed.');
    }
}
