<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    public function index(): View
    {
        $configurationGroups = [
            [
                'title' => 'Akses & pengguna',
                'description' => 'Atur akun, role, dan permission pengguna aplikasi.',
                'icon' => 'admin_panel_settings',
                'status' => 'Tersedia',
                'statusClass' => 'bg-green-50 text-green-700',
                'items' => ['Role dan permission'],
                'route' => route('admin.roles.index'),
                'action' => 'Kelola akses',
            ],
            [
                'title' => 'Master data',
                'description' => 'Kelola produk, lokasi, kategori, satuan, dan supplier.',
                'icon' => 'database',
                'status' => 'Tersedia',
                'statusClass' => 'bg-green-50 text-green-700',
                'items' => ['Warehouse dan outlet', 'Kategori dan satuan', 'Supplier', 'Status aktif/nonaktif'],
                'route' => route('admin.master-data.index', 'products'),
                'action' => 'Buka master data',
            ],
            [
                'title' => 'Persediaan',
                'description' => 'Tentukan batas minimum stok untuk produk pada setiap lokasi.',
                'icon' => 'inventory_2',
                'status' => 'Tersedia',
                'statusClass' => 'bg-green-50 text-green-700',
                'items' => ['Batas stok minimum per lokasi'],
                'route' => route('low-stock.thresholds'),
                'action' => 'Atur batas minimum',
            ],
            [
                'title' => 'Notifikasi',
                'description' => 'Atur penerima, template, dan status alert stok minimum WhatsApp.',
                'icon' => 'notifications_active',
                'status' => 'Tersedia',
                'statusClass' => 'bg-green-50 text-green-700',
                'items' => ['Penerima dan template WhatsApp', 'Aktif/nonaktif alert'],
                'route' => route('admin.whatsapp-alerts.index'),
                'action' => 'Atur notifikasi',
            ],
            [
                'title' => 'Aturan operasional',
                'description' => 'Sebagian aturan masih mengikuti nilai tetap dan perlu keputusan bisnis sebelum dapat diubah.',
                'icon' => 'rule',
                'status' => 'Perlu ketentuan',
                'statusClass' => 'bg-amber-50 text-amber-700',
                'items' => ['Ambang peringatan kedaluwarsa (saat ini 30 hari)', 'Matriks approval lintas transaksi'],
                'route' => null,
                'action' => null,
            ],
        ];

        return view('admin.settings.index', compact('configurationGroups'));
    }
}
