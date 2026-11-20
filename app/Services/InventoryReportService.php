<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    public const TYPES = ['stock', 'stock_in', 'stock_out', 'opname', 'adjustment', 'movements', 'expiry', 'purchasing'];

    public function definition(string $type): array
    {
        return match ($type) {
            'stock' => ['label' => 'Stok Saat Ini', 'date' => false, 'columns' => [
                'sku' => 'SKU', 'product' => 'Produk', 'category' => 'Kategori', 'location' => 'Lokasi', 'quantity' => 'Saldo Fisik', 'quarantined' => 'Karantina', 'available' => 'Tersedia', 'unit' => 'Satuan']],
            'stock_in' => ['label' => 'Stock In', 'date' => true, 'columns' => [
                'date' => 'Tanggal', 'reference' => 'Referensi', 'product' => 'Produk', 'sku' => 'SKU', 'category' => 'Kategori', 'location' => 'Lokasi', 'quantity' => 'Jumlah', 'unit' => 'Satuan', 'user' => 'Petugas', 'source' => 'Sumber']],
            'stock_out' => ['label' => 'Stock Out', 'date' => true, 'columns' => [
                'date' => 'Tanggal', 'reference' => 'Referensi', 'product' => 'Produk', 'sku' => 'SKU', 'category' => 'Kategori', 'location' => 'Lokasi', 'quantity' => 'Jumlah', 'unit' => 'Satuan', 'user' => 'Petugas', 'destination' => 'Tujuan', 'purpose' => 'Jenis Pengeluaran']],
            'opname' => ['label' => 'Stock Opname', 'date' => true, 'columns' => [
                'date' => 'Tanggal', 'reference' => 'Referensi', 'status' => 'Status', 'product' => 'Produk', 'sku' => 'SKU', 'category' => 'Kategori', 'location' => 'Lokasi', 'system_quantity' => 'Saldo Sistem', 'physical_quantity' => 'Hitung Fisik', 'variance' => 'Selisih', 'unit' => 'Satuan', 'user' => 'Petugas']],
            'adjustment' => ['label' => 'Adjustment', 'date' => true, 'columns' => [
                'date' => 'Tanggal', 'reference' => 'Referensi', 'direction' => 'Arah', 'product' => 'Produk', 'sku' => 'SKU', 'category' => 'Kategori', 'location' => 'Lokasi', 'quantity' => 'Jumlah', 'old_quantity' => 'Saldo Sebelum', 'new_quantity' => 'Saldo Sesudah', 'unit' => 'Satuan', 'user' => 'Petugas', 'reason' => 'Alasan']],
            'movements' => ['label' => 'Pergerakan Stok', 'date' => true, 'columns' => [
                'date' => 'Tanggal', 'type' => 'Jenis', 'reference' => 'Referensi', 'product' => 'Produk', 'sku' => 'SKU', 'category' => 'Kategori', 'location' => 'Lokasi', 'quantity' => 'Jumlah', 'unit' => 'Satuan', 'user' => 'Petugas', 'note' => 'Catatan']],
            'expiry' => ['label' => 'Kedaluwarsa & Umur Stok', 'date' => false, 'columns' => [
                'product' => 'Produk', 'sku' => 'SKU', 'category' => 'Kategori', 'location' => 'Lokasi', 'batch' => 'Nomor Batch', 'quantity' => 'Sisa', 'quarantined' => 'Karantina', 'available' => 'Tersedia', 'unit' => 'Satuan', 'expiry' => 'Kedaluwarsa', 'age_days' => 'Umur Stok (hari)', 'status' => 'Status']],
            'purchasing' => ['label' => 'Pengadaan', 'date' => true, 'columns' => [
                'date' => 'Tanggal PO', 'reference' => 'Referensi PO', 'request' => 'Referensi PR', 'status' => 'Status', 'supplier' => 'Supplier', 'product' => 'Produk', 'sku' => 'SKU', 'category' => 'Kategori', 'location' => 'Lokasi', 'quantity' => 'Jumlah', 'unit' => 'Satuan', 'unit_price' => 'Harga Satuan', 'line_total' => 'Subtotal', 'user' => 'Dibuat Oleh']],
            default => abort(404),
        };
    }

    public function query(string $type, array $filters, User $user): Builder
    {
        $locationId = $filters['location_id'] ?? ($user->isLocationScopedUser() ? $user->location_id : null);
        $productId = $filters['product_id'] ?? null;
        $categoryId = $filters['category_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $from = $filters['date_from'] ?? null;
        $to = $filters['date_to'] ?? null;

        $query = match ($type) {
            'stock' => $this->stockQuery($locationId, $productId, $categoryId),
            'stock_in' => $this->stockInQuery($locationId, $productId, $categoryId, $userId, $from, $to),
            'stock_out' => $this->stockOutQuery($locationId, $productId, $categoryId, $userId, $from, $to),
            'opname' => $this->opnameQuery($locationId, $productId, $categoryId, $userId, $from, $to, $user->hasRole('purchasing')),
            'adjustment' => $this->adjustmentQuery($locationId, $productId, $categoryId, $userId, $from, $to),
            'movements' => $this->movementQuery($locationId, $productId, $categoryId, $userId, $from, $to, $filters['transaction_type'] ?? null),
            'expiry' => $this->expiryQuery($locationId, $productId, $categoryId),
            'purchasing' => $this->purchasingQuery($locationId, $productId, $categoryId, $userId, $from, $to, $filters['transaction_type'] ?? null),
            default => abort(404),
        };

        return $query;
    }

    private function stockQuery(?int $locationId, ?int $productId, ?int $categoryId): Builder
    {
        $available = 'CASE WHEN COALESCE(balances.quantity, 0) - COALESCE(holds.quantity, 0) > 0 THEN COALESCE(balances.quantity, 0) - COALESCE(holds.quantity, 0) ELSE 0 END';
        $balances = DB::table('stock_movements')->select('product_id', 'location_id')
            ->selectRaw("SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as quantity")
            ->groupBy('product_id', 'location_id');
        $holds = DB::table('stock_disposal_items as di')->join('stock_disposals as d', 'd.id', '=', 'di.stock_disposal_id')
            ->where('d.status', 'pending_approval')->select('di.product_id', 'd.location_id')
            ->selectRaw('SUM(di.quantity) as quantity')->groupBy('di.product_id', 'd.location_id');

        return DB::table('products')->join('units', 'units.id', '=', 'products.unit_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->join('locations', function ($join) { $join->where('locations.is_active', true)->whereNull('locations.deleted_at'); })
            ->leftJoinSub($balances, 'balances', fn ($join) => $join->on('balances.product_id', '=', 'products.id')->on('balances.location_id', '=', 'locations.id'))
            ->leftJoinSub($holds, 'holds', fn ($join) => $join->on('holds.product_id', '=', 'products.id')->on('holds.location_id', '=', 'locations.id'))
            ->whereNull('products.deleted_at')->where('products.is_active', true)
            ->when($locationId, fn ($q) => $q->where('locations.id', $locationId))
            ->when($productId, fn ($q) => $q->where('products.id', $productId))
            ->when($categoryId, fn ($q) => $q->where('products.category_id', $categoryId))
            ->selectRaw("products.sku, products.name as product, categories.name as category, locations.name as location, COALESCE(balances.quantity, 0) as quantity, COALESCE(holds.quantity, 0) as quarantined, {$available} as available, units.symbol as unit")
            ->orderBy('products.name')->orderBy('locations.name');
    }

    private function stockInQuery(?int $locationId, ?int $productId, ?int $categoryId, ?int $userId, ?string $from, ?string $to): Builder
    {
        return DB::table('stock_ins as t')->join('stock_in_items as i', 'i.stock_in_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'i.product_id')->join('categories as c', 'c.id', '=', 'p.category_id')->join('units as u', 'u.id', '=', 'p.unit_id')
            ->join('locations as l', 'l.id', '=', 't.location_id')->leftJoin('users as usr', 'usr.id', '=', 't.received_by')
            ->when($locationId, fn ($q) => $q->where('t.location_id', $locationId))->when($productId, fn ($q) => $q->where('p.id', $productId))
            ->when($categoryId, fn ($q) => $q->where('p.category_id', $categoryId))->when($userId, fn ($q) => $q->where('t.received_by', $userId))
            ->when($from, fn ($q) => $q->whereDate('t.received_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('t.received_at', '<=', $to))
            ->selectRaw('t.received_at as date, t.reference, p.name as product, p.sku, c.name as category, l.name as location, i.quantity, u.symbol as unit, usr.name as user, t.source, COALESCE(t.status, \'approved\') as status')
            ->orderByDesc('t.received_at')->orderByDesc('t.id');
    }

    private function stockOutQuery(?int $locationId, ?int $productId, ?int $categoryId, ?int $userId, ?string $from, ?string $to): Builder
    {
        return DB::table('stock_outs as t')->join('stock_out_items as i', 'i.stock_out_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'i.product_id')->join('categories as c', 'c.id', '=', 'p.category_id')->join('units as u', 'u.id', '=', 'p.unit_id')
            ->join('locations as l', 'l.id', '=', 't.location_id')->leftJoin('users as usr', 'usr.id', '=', 't.issued_by')
            ->when($locationId, fn ($q) => $q->where('t.location_id', $locationId))->when($productId, fn ($q) => $q->where('p.id', $productId))
            ->when($categoryId, fn ($q) => $q->where('p.category_id', $categoryId))->when($userId, fn ($q) => $q->where('t.issued_by', $userId))
            ->when($from, fn ($q) => $q->whereDate('t.issued_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('t.issued_at', '<=', $to))
            ->selectRaw("t.issued_at as date, t.reference, p.name as product, p.sku, c.name as category, l.name as location, i.quantity, u.symbol as unit, usr.name as user, t.destination, CASE t.purpose WHEN 'sale' THEN 'Penjualan outlet' WHEN 'internal_use' THEN 'Pemakaian internal' WHEN 'sample' THEN 'Sampel / promosi' WHEN 'other' THEN 'Lainnya' ELSE 'Belum diketahui (transaksi lama)' END as purpose")
            ->orderByDesc('t.issued_at')->orderByDesc('t.id');
    }

    private function opnameQuery(?int $locationId, ?int $productId, ?int $categoryId, ?int $userId, ?string $from, ?string $to, bool $approvedOnly = false): Builder
    {
        return DB::table('stock_opnames as t')->join('stock_opname_items as i', 'i.stock_opname_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'i.product_id')->join('categories as c', 'c.id', '=', 'p.category_id')->join('units as u', 'u.id', '=', 'p.unit_id')
            ->join('locations as l', 'l.id', '=', 't.location_id')->leftJoin('users as usr', 'usr.id', '=', 't.counted_by')
            ->when($locationId, fn ($q) => $q->where('t.location_id', $locationId))->when($productId, fn ($q) => $q->where('p.id', $productId))
            ->when($categoryId, fn ($q) => $q->where('p.category_id', $categoryId))->when($userId, fn ($q) => $q->where('t.counted_by', $userId))
            ->when($approvedOnly, fn ($q) => $q->where('t.status', 'approved'))
            ->when($from, fn ($q) => $q->whereDate('t.created_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('t.created_at', '<=', $to))
            ->selectRaw('t.created_at as date, t.reference, t.status, p.name as product, p.sku, c.name as category, l.name as location, i.system_quantity, i.physical_quantity, i.variance, u.symbol as unit, usr.name as user')
            ->orderByDesc('t.created_at')->orderByDesc('t.id');
    }

    private function adjustmentQuery(?int $locationId, ?int $productId, ?int $categoryId, ?int $userId, ?string $from, ?string $to): Builder
    {
        return DB::table('stock_adjustments as t')->join('products as p', 'p.id', '=', 't.product_id')->join('categories as c', 'c.id', '=', 'p.category_id')
            ->join('units as u', 'u.id', '=', 'p.unit_id')->join('locations as l', 'l.id', '=', 't.location_id')->leftJoin('users as usr', 'usr.id', '=', 't.created_by')
            ->when($locationId, fn ($q) => $q->where('t.location_id', $locationId))->when($productId, fn ($q) => $q->where('p.id', $productId))
            ->when($categoryId, fn ($q) => $q->where('p.category_id', $categoryId))->when($userId, fn ($q) => $q->where('t.created_by', $userId))
            ->when($from, fn ($q) => $q->whereDate('t.created_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('t.created_at', '<=', $to))
            ->selectRaw('t.created_at as date, t.reference, t.direction, p.name as product, p.sku, c.name as category, l.name as location, t.quantity, t.old_quantity, t.new_quantity, u.symbol as unit, usr.name as user, t.reason')
            ->orderByDesc('t.created_at')->orderByDesc('t.id');
    }

    private function movementQuery(?int $locationId, ?int $productId, ?int $categoryId, ?int $userId, ?string $from, ?string $to, ?string $type): Builder
    {
        $query = DB::table('stock_movements as m')->join('products as p', 'p.id', '=', 'm.product_id')->join('categories as c', 'c.id', '=', 'p.category_id')
            ->join('units as u', 'u.id', '=', 'p.unit_id')->join('locations as l', 'l.id', '=', 'm.location_id')->leftJoin('users as usr', 'usr.id', '=', 'm.user_id')
            ->leftJoin('stock_in_items as sii', 'sii.id', '=', 'm.stock_in_item_id')->leftJoin('stock_ins as si', 'si.id', '=', 'sii.stock_in_id')
            ->leftJoin('stock_out_items as soi', 'soi.id', '=', 'm.stock_out_item_id')->leftJoin('stock_outs as so', 'so.id', '=', 'soi.stock_out_id')
            ->leftJoin('stock_opname_items as oi', 'oi.id', '=', 'm.stock_opname_item_id')->leftJoin('stock_opnames as op', 'op.id', '=', 'oi.stock_opname_id')
            ->leftJoin('stock_adjustments as a', 'a.id', '=', 'm.stock_adjustment_id')
            ->leftJoin('stock_disposal_items as di', 'di.id', '=', 'm.stock_disposal_item_id')->leftJoin('stock_disposals as d', 'd.id', '=', 'di.stock_disposal_id')
            ->when($locationId, fn ($q) => $q->where('m.location_id', $locationId))->when($productId, fn ($q) => $q->where('p.id', $productId))
            ->when($categoryId, fn ($q) => $q->where('p.category_id', $categoryId))->when($userId, fn ($q) => $q->where('m.user_id', $userId))
            ->when($from, fn ($q) => $q->whereDate('m.created_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('m.created_at', '<=', $to));
        if ($type === 'stock_in') $query->whereNotNull('m.stock_in_item_id');
        elseif ($type === 'stock_out') $query->whereNotNull('m.stock_out_item_id');
        elseif ($type === 'opname') $query->whereNotNull('m.stock_opname_item_id');
        elseif ($type === 'adjustment') $query->whereNotNull('m.stock_adjustment_id');
        elseif ($type === 'disposal') $query->whereNotNull('m.stock_disposal_item_id');
        return $query->selectRaw("m.created_at as date, CASE WHEN m.stock_in_item_id IS NOT NULL THEN 'Stock In' WHEN m.stock_out_item_id IS NOT NULL THEN 'Stock Out' WHEN m.stock_opname_item_id IS NOT NULL THEN 'Stock Opname' WHEN m.stock_adjustment_id IS NOT NULL THEN 'Adjustment' WHEN m.stock_disposal_item_id IS NOT NULL THEN 'Write-off / Retur' ELSE m.type END as type, COALESCE(si.reference, so.reference, op.reference, a.reference, d.reference, '—') as reference, p.name as product, p.sku, c.name as category, l.name as location, CASE WHEN m.type = 'in' THEN m.quantity ELSE -m.quantity END as quantity, u.symbol as unit, usr.name as user, m.note")
            ->orderByDesc('m.created_at')->orderByDesc('m.id');
    }

    private function expiryQuery(?int $locationId, ?int $productId, ?int $categoryId): Builder
    {
        $sqlite = DB::connection()->getDriverName() === 'sqlite';
        $available = 'CASE WHEN balances.quantity - COALESCE(holds.quantity, 0) > 0 THEN balances.quantity - COALESCE(holds.quantity, 0) ELSE 0 END';
        $today = $sqlite ? "date('now')" : 'CURDATE()';
        $withinThirtyDays = $sqlite ? "date('now', '+30 days')" : 'DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
        $receivedDate = 'COALESCE(b.batch_received_at, si.received_at)';
        $ageDays = $sqlite
            ? "CASE WHEN julianday(date('now')) - julianday(date({$receivedDate})) > 0 THEN CAST(julianday(date('now')) - julianday(date({$receivedDate})) AS INTEGER) ELSE 0 END"
            : "GREATEST(DATEDIFF(CURDATE(), {$receivedDate}), 0)";
        $balances = DB::table('stock_movements')->whereNotNull('batch_id')->select('batch_id', 'location_id')
            ->selectRaw("SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as quantity")->groupBy('batch_id', 'location_id');
        $holds = DB::table('stock_disposal_items as di')->join('stock_disposals as d', 'd.id', '=', 'di.stock_disposal_id')
            ->where('d.status', 'pending_approval')->whereNotNull('di.batch_id')->select('di.batch_id', 'd.location_id')
            ->selectRaw('SUM(di.quantity) as quantity')->groupBy('di.batch_id', 'd.location_id');
        return DB::table('stock_in_items as b')->join('stock_ins as si', 'si.id', '=', 'b.stock_in_id')
            ->join('products as p', 'p.id', '=', 'b.product_id')->join('categories as c', 'c.id', '=', 'p.category_id')->join('units as u', 'u.id', '=', 'p.unit_id')
            ->join('locations as l', 'l.id', '=', 'si.location_id')->joinSub($balances, 'balances', fn ($join) => $join->on('balances.batch_id', '=', 'b.id')->on('balances.location_id', '=', 'si.location_id'))
            ->leftJoinSub($holds, 'holds', fn ($join) => $join->on('holds.batch_id', '=', 'b.id')->on('holds.location_id', '=', 'si.location_id'))
            ->where('balances.quantity', '>', 0)->whereNull('p.deleted_at')->where('si.status', 'approved')
            ->when($locationId, fn ($q) => $q->where('si.location_id', $locationId))->when($productId, fn ($q) => $q->where('p.id', $productId))
            ->when($categoryId, fn ($q) => $q->where('p.category_id', $categoryId))
            ->selectRaw("p.name as product, p.sku, c.name as category, l.name as location, COALESCE(b.batch_number, 'Tanpa nomor') as batch, balances.quantity, COALESCE(holds.quantity, 0) as quarantined, {$available} as available, u.symbol as unit, b.expiry_date as expiry, {$ageDays} as age_days, CASE WHEN COALESCE(holds.quantity, 0) >= balances.quantity THEN 'Karantina' WHEN b.expiry_date IS NULL THEN 'Tanpa tanggal' WHEN b.expiry_date < {$today} THEN 'Kedaluwarsa' WHEN b.expiry_date <= {$withinThirtyDays} THEN 'Segera kedaluwarsa' ELSE 'Aman' END as status")
            ->orderByRaw('b.expiry_date IS NULL')->orderBy('b.expiry_date')->orderBy('p.name');
    }

    private function purchasingQuery(?int $locationId, ?int $productId, ?int $categoryId, ?int $userId, ?string $from, ?string $to, ?string $status): Builder
    {
        return DB::table('purchase_orders as po')->join('purchase_order_items as i', 'i.purchase_order_id', '=', 'po.id')
            ->leftJoin('purchase_requests as pr', 'pr.id', '=', 'po.purchase_request_id')->join('products as p', 'p.id', '=', 'i.product_id')
            ->join('categories as c', 'c.id', '=', 'p.category_id')->join('units as u', 'u.id', '=', 'p.unit_id')->join('locations as l', 'l.id', '=', 'po.location_id')
            ->join('suppliers as s', 's.id', '=', 'po.supplier_id')->leftJoin('users as usr', 'usr.id', '=', 'po.created_by')
            ->when($locationId, fn ($q) => $q->where('po.location_id', $locationId))->when($productId, fn ($q) => $q->where('p.id', $productId))
            ->when($categoryId, fn ($q) => $q->where('p.category_id', $categoryId))->when($userId, fn ($q) => $q->where('po.created_by', $userId))
            ->when($from, fn ($q) => $q->whereDate('po.created_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('po.created_at', '<=', $to))
            ->when($status, fn ($q) => $q->where('po.status', $status))
            ->selectRaw('po.created_at as date, po.reference, pr.reference as request, po.status, s.name as supplier, p.name as product, p.sku, c.name as category, l.name as location, i.quantity, u.symbol as unit, i.unit_price, (i.quantity * i.unit_price) as line_total, usr.name as user')
            ->orderByDesc('po.created_at')->orderByDesc('po.id');
    }
}
