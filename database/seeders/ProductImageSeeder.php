<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan folder products ada di disk public
        Storage::disk('public')->makeDirectory('products');

        // Ambil semua data produk yang memiliki image_path
        $products = DB::table('products')->whereNotNull('image_path')->get();

        if ($products->isEmpty()) {
            $this->command->warn('Tidak ada data produk yang ditemukan di database.');
            return;
        }

        $this->command->info('Sedang membuat file gambar placeholder...');

        $count = 0;
        foreach ($products as $product) {
            $path = $product->image_path; // contoh: "products/uuid.webp"

            // Jika file fisiknya belum ada di storage, buatkan gambar dummy
            if (!Storage::disk('public')->exists($path)) {
                $label = $product->name ?? 'Product Image';
                $imageContent = $this->generateDummyImage($label);
                
                Storage::disk('public')->put($path, $imageContent);
                $count++;
            }
        }

        $this->command->info("Selesai! {$count} file gambar placeholder berhasil dibuat.");
    }

    private function generateDummyImage(string $text): string
    {
        $width = 600;
        $height = 600;

        // Buat canvas gambar
        $img = imagecreatetruecolor($width, $height);

        // Warna background (abu-abu muda) dan teks (abu-abu tua)
        $bgColor = imagecolorallocate($img, 226, 232, 240);
        $textColor = imagecolorallocate($img, 71, 85, 105);

        imagefill($img, 0, 0, $bgColor);

        // Tulis teks nama produk di tengah gambar
        $displayText = substr($text, 0, 25);
        $font = 5; // Built-in GD font size
        $textWidth = imagefontwidth($font) * strlen($displayText);
        $textHeight = imagefontheight($font);
        
        $x = (int) (($width - $textWidth) / 2);
        $y = (int) (($height - $textHeight) / 2);

        imagestring($img, $font, $x, $y, $displayText, $textColor);

        // Output ke buffer sebagai WebP / PNG
        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($img);
        } else {
            imagepng($img);
        }
        $data = ob_get_clean();
        imagedestroy($img);

        return $data;
    }
}