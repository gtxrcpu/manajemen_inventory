<?php

namespace App\Services;

use App\Models\Location;
use App\Models\LowStockAlert;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class ManagementReportDashboardService
{
    public function __construct(
        private readonly InventoryReportService $reports,
        private readonly InventoryValuationService $valuation,
    ) {}

    public function build(User $user, ?int $locationId, int $days): array
    {
        $from = Carbon::today()->subDays($days - 1);
        $to = Carbon::today();
        $locationFilter = $locationId ? ['location_id' => $locationId] : [];

        $stockRows = $this->reports->query('stock', $locationFilter, $user)->get();
        $stockedPairs = $stockRows->filter(fn ($row) => (float) $row->available > 0.0005)->count();

        $trendRows = StockMovement::query()
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw("SUM(CASE WHEN type = 'in' THEN 1 ELSE 0 END) as stock_in_count")
            ->selectRaw("SUM(CASE WHEN type = 'out' THEN 1 ELSE 0 END) as stock_out_count")
            ->groupByRaw('DATE(created_at)')->orderBy('day')->get()->keyBy('day');

        $trend = collect(CarbonPeriod::create($from, $to))->map(function (Carbon $day) use ($trendRows) {
            $row = $trendRows->get($day->toDateString());
            return [
                'date' => $day->toDateString(),
                'label' => $day->format('d M'),
                'stock_in' => (int) ($row->stock_in_count ?? 0),
                'stock_out' => (int) ($row->stock_out_count ?? 0),
            ];
        })->values();

        $valuationRows = $this->valuation->snapshot($locationId);
        $valueTotal = (float) $valuationRows->sum('value');
        $valueStatusCounts = [
            'confirmed' => $valuationRows->where('valuation_status', 'confirmed')->count(),
            'provisional' => $valuationRows->where('valuation_status', 'provisional')->count(),
            'incomplete' => $valuationRows->where('valuation_status', 'incomplete')->count(),
        ];
        $valueStatusTotal = max(1, array_sum($valueStatusCounts));
        $circumference = 2 * M_PI * 43;
        $offset = 0.0;
        $quality = collect([
            ['key' => 'confirmed', 'label' => 'Terkonfirmasi', 'color' => '#0f9f78'],
            ['key' => 'provisional', 'label' => 'Sementara', 'color' => '#e7a52b'],
            ['key' => 'incomplete', 'label' => 'Belum lengkap', 'color' => '#dc143c'],
        ])->map(function (array $item) use ($valueStatusCounts, $valueStatusTotal, $circumference, &$offset) {
            $count = $valueStatusCounts[$item['key']];
            $length = $circumference * ($count / $valueStatusTotal);
            $row = [...$item, 'count' => $count, 'dash' => $length.' '.($circumference - $length), 'offset' => -$offset];
            $offset += $length;
            return $row;
        })->all();

        $topValueRows = $valuationRows->filter(fn ($row) => (float) $row['value'] > 0)
            ->sortByDesc('value')->take(5)->values();
        $maxTopValue = max(1, (float) ($topValueRows->max('value') ?? 0));
        $topValueRows = $topValueRows->map(function (array $row) use ($maxTopValue) {
            $row['bar_percent'] = min(100, (float) $row['value'] / $maxTopValue * 100);
            return $row;
        });

        $lowStockBase = LowStockAlert::query()->active()
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId));
        $lowStockCount = (clone $lowStockBase)->count();
        $lowStockAlerts = (clone $lowStockBase)->with(['product.unit', 'location'])
            ->latest('triggered_at')->limit(6)->get();

        $expiryRows = $this->reports->query('expiry', $locationFilter, $user)->get();
        $expiryRisks = $expiryRows->filter(fn ($row) => in_array($row->status, ['Kedaluwarsa', 'Segera kedaluwarsa'], true))
            ->take(6)->values();
        $expiredBatchCount = $expiryRows->where('status', 'Kedaluwarsa')->count();
        $nearExpiryBatchCount = $expiryRows->where('status', 'Segera kedaluwarsa')->count();

        $openPurchaseOrders = PurchaseOrder::query()->where('status', 'issued')
            ->when($locationId, fn ($query) => $query->where('location_id', $locationId))->count();
        $inTransitTransfers = StockTransfer::query()->whereIn('status', ['in_transit', 'partially_received', 'discrepancy_open'])
            ->when($locationId, fn ($query) => $query->where(function ($scope) use ($locationId) {
                $scope->where('source_location_id', $locationId)->orWhere('destination_location_id', $locationId);
            }))->count();
        $openDiscrepancies = StockTransfer::query()->where('status', 'discrepancy_open')
            ->when($locationId, fn ($query) => $query->where(function ($scope) use ($locationId) {
                $scope->where('source_location_id', $locationId)->orWhere('destination_location_id', $locationId);
            }))->count();

        $maxTrend = max(1, (int) $trend->max(fn ($row) => max($row['stock_in'], $row['stock_out'])));
        $pointCount = max(1, $trend->count() - 1);
        $trend = $trend->map(function (array $point, int $index) use ($maxTrend, $pointCount) {
            $x = 42 + (($index / $pointCount) * 636);
            $point['x'] = round($x, 2);
            $point['in_y'] = round(204 - ($point['stock_in'] / $maxTrend * 154), 2);
            $point['out_y'] = round(204 - ($point['stock_out'] / $maxTrend * 154), 2);
            return $point;
        });

        return [
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'selectedLocationId' => $locationId,
            'periodDays' => $days,
            'dateFrom' => $from->toDateString(),
            'dateTo' => $to->toDateString(),
            'stockedPairs' => $stockedPairs,
            'stockPairCount' => $stockRows->count(),
            'lowStockCount' => $lowStockCount,
            'lowStockAlerts' => $lowStockAlerts,
            'openPurchaseOrders' => $openPurchaseOrders,
            'inTransitTransfers' => $inTransitTransfers,
            'openDiscrepancies' => $openDiscrepancies,
            'valueTotal' => $valueTotal,
            'valueStatusCounts' => $valueStatusCounts,
            'valueQuality' => $quality,
            'valueQualityTotal' => array_sum($valueStatusCounts),
            'topValueRows' => $topValueRows,
            'trend' => $trend,
            'trendInPoints' => $trend->map(fn ($point) => $point['x'].','.$point['in_y'])->implode(' '),
            'trendOutPoints' => $trend->map(fn ($point) => $point['x'].','.$point['out_y'])->implode(' '),
            'trendMax' => $maxTrend,
            'expiryRisks' => $expiryRisks,
            'expiredBatchCount' => $expiredBatchCount,
            'nearExpiryBatchCount' => $nearExpiryBatchCount,
            'reportFilters' => array_filter([
                'location_id' => $locationId,
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ], fn ($value) => $value !== null && $value !== ''),
        ];
    }
}
