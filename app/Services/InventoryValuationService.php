<?php

namespace App\Services;

use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryValuationService
{
    /**
     * Replays the stock ledger using moving weighted average cost. Invoice prices
     * remain provisional until Finance accepts their invoice. Movements with no
     * linked invoice price are shown as unvalued instead of being treated as Rp0.
     */
    public function snapshot(?int $locationId = null, ?int $productId = null): Collection
    {
        $transferSourceByReceiptBatch = DB::table('stock_transfer_receipt_items as receipt_items')
            ->join('stock_transfer_items as transfer_items', 'transfer_items.id', '=', 'receipt_items.stock_transfer_item_id')
            ->whereNotNull('receipt_items.stock_in_item_id')
            ->pluck('transfer_items.stock_out_item_id', 'receipt_items.stock_in_item_id')->all();
        $legacyTransferSources = DB::table('stock_transfer_items')
            ->whereNotNull('stock_in_item_id')->pluck('stock_out_item_id', 'stock_in_item_id')->all();
        $transferSourceByReceiptBatch = $transferSourceByReceiptBatch + $legacyTransferSources;

        $movements = StockMovement::query()->with([
            'product:id,sku,name,unit_id',
            'product.unit:id,symbol',
            'location:id,name',
            'stockInItem.stockIn.purchaseInvoice.items',
            'stockOutItem.stockOut',
            'stockOpnameItem.opname',
            'stockAdjustment',
        ])->get()->sortBy(function (StockMovement $movement) {
            $recordedAt = $movement->stockInItem?->stockIn?->created_at
                ?? $movement->stockOutItem?->stockOut?->created_at
                ?? $movement->stockOpnameItem?->opname?->created_at
                ?? $movement->stockAdjustment?->created_at
                ?? $movement->created_at;

            return sprintf('%s-%010d', $recordedAt?->format('YmdHis.u') ?? '', $movement->id);
        })->values();

        $balances = [];
        $transferIssueCosts = [];

        foreach ($movements as $movement) {
            $key = $movement->product_id.':'.$movement->location_id;
            $balances[$key] ??= [
                'product_id' => $movement->product_id,
                'sku' => $movement->product?->sku,
                'product_name' => $movement->product?->name,
                'location_id' => $movement->location_id,
                'location_name' => $movement->location?->name,
                'unit_symbol' => $movement->product?->unit?->symbol,
                'quantity' => 0.0,
                'value' => 0.0,
                'provisional_quantity' => 0.0,
                'unpriced_quantity' => 0.0,
                'unallocated_cost' => 0.0,
                'document_variance' => 0.0,
                'sources' => [],
            ];

            $bucket =& $balances[$key];
            $quantity = (float) $movement->quantity;

            if ($movement->type === 'in') {
                $unitCost = null;
                $provisional = false;
                $provisionalShare = null;
                $unpricedShare = null;
                $invoice = $movement->stockInItem?->stockIn?->purchaseInvoice;
                $invoiceLine = $invoice?->items?->firstWhere('product_id', $movement->product_id);
                if ($invoiceLine) {
                    $invoiceSubtotal = (float) $invoice->items_subtotal;
                    $lineSubtotal = (float) $invoiceLine->quantity * (float) $invoiceLine->unit_price;
                    $lineShare = $invoiceSubtotal > 0 ? $lineSubtotal / $invoiceSubtotal : 0;
                    $discountShare = $invoiceSubtotal > 0
                        ? min(1, (float) $invoice->discount_total / $invoiceSubtotal)
                        : 0;
                    $unitCost = round((float) $invoiceLine->unit_price * (1 - $discountShare), 4);
                    $provisional = $invoice->status !== 'accepted';
                    $sourceKey = 'invoice:'.$invoice->id;
                    $sourceLabel = 'Invoice '.$invoice->invoice_number.' · receipt '.$movement->stockInItem?->stockIn?->reference;
                    $lineQuantity = max((float) $invoiceLine->quantity, 0.0005);
                    $unallocatedCostPerUnit = (((float) $invoice->tax_total + (float) $invoice->shipping_total + (float) $invoice->other_fee_total) * $lineShare) / $lineQuantity;
                    $documentVariancePerUnit = (abs((float) $invoice->total_variance) * $lineShare) / $lineQuantity;
                    $sourceBreakdown = [['key' => $sourceKey, 'label' => $sourceLabel, 'share' => 1.0]];
                } elseif ($movement->stock_in_item_id && isset($transferSourceByReceiptBatch[$movement->stock_in_item_id])) {
                    $sourceIssueId = (int) $transferSourceByReceiptBatch[$movement->stock_in_item_id];
                    $transferSource = $transferIssueCosts[$sourceIssueId] ?? null;
                    $unitCost = $transferSource['unit_cost'] ?? null;
                    $provisionalShare = $transferSource['provisional_share'] ?? 0;
                    $unpricedShare = $transferSource['unpriced_share'] ?? 1;
                    $unallocatedCostPerUnit = $transferSource['unallocated_cost_per_unit'] ?? 0;
                    $documentVariancePerUnit = $transferSource['document_variance_per_unit'] ?? 0;
                    $sourceBreakdown = $transferSource['sources'] ?? [];
                    if (! $sourceBreakdown) $sourceBreakdown = [['key' => 'transfer:'.$sourceIssueId, 'label' => 'Transfer tanpa sumber biaya lengkap', 'share' => 1.0]];
                } else {
                    $provisionalShare = 0;
                    $unpricedShare = 1;
                    $unallocatedCostPerUnit = 0;
                    $documentVariancePerUnit = 0;
                    $sourceKey = $movement->stock_in_item_id ? 'receipt:'.$movement->stock_in_item_id : 'legacy:'.$movement->id;
                    $sourceLabel = $movement->stockInItem?->stockIn?->reference
                        ? 'Tanpa invoice · receipt '.$movement->stockInItem->stockIn->reference
                        : 'Legacy / adjustment · sumber biaya belum diketahui';
                    $sourceBreakdown = [['key' => $sourceKey, 'label' => $sourceLabel, 'share' => 1.0]];
                }

                $bucket['quantity'] += $quantity;
                $unpricedShare ??= $unitCost === null ? 1 : 0;
                $provisionalShare ??= $provisional ? 1 : 0;
                $bucket['unpriced_quantity'] += $quantity * $unpricedShare;
                $bucket['unallocated_cost'] += $quantity * $unallocatedCostPerUnit;
                $bucket['document_variance'] += $quantity * $documentVariancePerUnit;
                foreach ($sourceBreakdown as $source) {
                    $sourceKey = $source['key'];
                    $bucket['sources'][$sourceKey] ??= ['label' => $source['label'], 'quantity' => 0.0];
                    $bucket['sources'][$sourceKey]['quantity'] += $quantity * (float) $source['share'];
                }
                if ($unitCost !== null) {
                    $bucket['value'] += $quantity * (1 - $unpricedShare) * $unitCost;
                    $bucket['provisional_quantity'] += $quantity * $provisionalShare;
                }
            } else {
                $pricedQuantity = max(0, $bucket['quantity'] - $bucket['unpriced_quantity']);
                $unitCost = $pricedQuantity > 0.0005 ? $bucket['value'] / $pricedQuantity : null;
                $missingShare = $bucket['quantity'] > 0.0005
                    ? min(1, $bucket['unpriced_quantity'] / $bucket['quantity'])
                    : 0;
                $provisionalShare = $bucket['quantity'] > 0.0005
                    ? min(1, $bucket['provisional_quantity'] / $bucket['quantity'])
                    : 0;

                if ($movement->stock_out_item_id) {
                    $sourceBreakdown = [];
                    foreach ($bucket['sources'] as $sourceKey => $source) {
                        $share = $bucket['quantity'] > 0.0005 ? (float) $source['quantity'] / $bucket['quantity'] : 0;
                        if ($share > 0.0005) $sourceBreakdown[] = ['key' => $sourceKey, 'label' => $source['label'], 'share' => $share];
                    }
                    $transferIssueCosts[$movement->stock_out_item_id] = [
                        'unit_cost' => $unitCost,
                        'provisional_share' => $provisionalShare,
                        'unpriced_share' => $missingShare,
                        'unallocated_cost_per_unit' => $bucket['quantity'] > 0.0005 ? $bucket['unallocated_cost'] / $bucket['quantity'] : 0,
                        'document_variance_per_unit' => $bucket['quantity'] > 0.0005 ? $bucket['document_variance'] / $bucket['quantity'] : 0,
                        'sources' => $sourceBreakdown,
                    ];
                }

                $remainingRatio = $bucket['quantity'] > 0.0005 ? max(0, 1 - ($quantity / $bucket['quantity'])) : 0;
                foreach ($bucket['sources'] as &$source) $source['quantity'] *= $remainingRatio;
                unset($source);
                $pricedOut = $quantity * (1 - $missingShare);
                $bucket['value'] = max(0, $bucket['value'] - ($unitCost === null ? 0 : $pricedOut * $unitCost));
                $bucket['unpriced_quantity'] = max(0, $bucket['unpriced_quantity'] - ($quantity * $missingShare));
                $bucket['provisional_quantity'] = max(0, $bucket['provisional_quantity'] - ($quantity * $provisionalShare));
                $bucket['unallocated_cost'] = max(0, $bucket['unallocated_cost'] * $remainingRatio);
                $bucket['document_variance'] = max(0, $bucket['document_variance'] * $remainingRatio);
                $bucket['quantity'] -= $quantity;
            }
            unset($bucket);
        }

        $inTransitItems = DB::table('stock_transfer_items as items')
            ->join('stock_transfers as transfers', 'transfers.id', '=', 'items.stock_transfer_id')
            ->join('products', 'products.id', '=', 'items.product_id')
            ->join('units', 'units.id', '=', 'products.unit_id')
            ->join('locations as source_locations', 'source_locations.id', '=', 'transfers.source_location_id')
            ->join('locations as destination_locations', 'destination_locations.id', '=', 'transfers.destination_location_id')
            ->whereIn('transfers.status', ['in_transit', 'partially_received', 'discrepancy_open'])
            ->selectRaw('items.product_id, items.stock_out_item_id, transfers.destination_location_id, transfers.reference, products.sku, products.name as product_name, units.symbol as unit_symbol, source_locations.name as source_name, destination_locations.name as destination_name');
        if (Schema::hasTable('stock_transfer_discrepancy_resolutions')) {
            $resolvedDiscrepancies = DB::table('stock_transfer_discrepancy_resolutions')
                ->select('stock_transfer_item_id')->selectRaw('SUM(quantity) as resolved_quantity')->groupBy('stock_transfer_item_id');
            $inTransitItems->leftJoinSub($resolvedDiscrepancies, 'resolutions', fn ($join) => $join->on('resolutions.stock_transfer_item_id', '=', 'items.id'))
                ->selectRaw('(items.quantity - items.received_quantity - COALESCE(resolutions.resolved_quantity, 0)) as outstanding_quantity');
        } else {
            $inTransitItems->selectRaw('(items.quantity - items.received_quantity) as outstanding_quantity');
        }
        $inTransitItems = $inTransitItems->get();

        foreach ($inTransitItems as $item) {
            $quantity = max(0, (float) $item->outstanding_quantity);
            if ($quantity <= 0.0005) continue;

            $sourceCost = $transferIssueCosts[$item->stock_out_item_id] ?? null;
            $unitCost = $sourceCost['unit_cost'] ?? null;
            $unpricedShare = $sourceCost['unpriced_share'] ?? 1;
            $provisionalShare = $sourceCost['provisional_share'] ?? 0;
            $unallocatedCostPerUnit = $sourceCost['unallocated_cost_per_unit'] ?? 0;
            $documentVariancePerUnit = $sourceCost['document_variance_per_unit'] ?? 0;
            $sourceBreakdown = $sourceCost['sources'] ?? [['key' => 'transfer:'.$item->stock_out_item_id, 'label' => 'Transfer tanpa sumber biaya lengkap', 'share' => 1.0]];
            $key = $item->product_id.':transit:'.$item->destination_location_id;
            $balances[$key] ??= [
                'product_id' => $item->product_id,
                'sku' => $item->sku,
                'product_name' => $item->product_name,
                'location_id' => 'transit:'.$item->destination_location_id,
                'location_name' => 'Dalam perjalanan ke '.$item->destination_name,
                'unit_symbol' => $item->unit_symbol,
                'quantity' => 0.0,
                'value' => 0.0,
                'provisional_quantity' => 0.0,
                'unpriced_quantity' => 0.0,
                'unallocated_cost' => 0.0,
                'document_variance' => 0.0,
                'sources' => [],
                'transfer_references' => [],
            ];
            $balances[$key]['quantity'] += $quantity;
            $balances[$key]['unpriced_quantity'] += $quantity * $unpricedShare;
            $balances[$key]['unallocated_cost'] += $quantity * $unallocatedCostPerUnit;
            $balances[$key]['document_variance'] += $quantity * $documentVariancePerUnit;
            foreach ($sourceBreakdown as $source) {
                $balances[$key]['sources'][$source['key']] ??= ['label' => $source['label'], 'quantity' => 0.0];
                $balances[$key]['sources'][$source['key']]['quantity'] += $quantity * (float) $source['share'];
            }
            if ($unitCost !== null) {
                $balances[$key]['value'] += $quantity * (1 - $unpricedShare) * $unitCost;
                $balances[$key]['provisional_quantity'] += $quantity * $provisionalShare;
            }
            $balances[$key]['transfer_references'][] = $item->reference;
        }

        return collect($balances)->filter(fn (array $row) => abs($row['quantity']) > 0.0005)
            ->map(function (array $row) {
                $pricedQuantity = max(0, $row['quantity'] - $row['unpriced_quantity']);
                $row['unit_cost'] = $pricedQuantity > 0.0005 ? $row['value'] / $pricedQuantity : null;
                $row['valuation_status'] = $row['unpriced_quantity'] > 0.0005
                    || $row['unallocated_cost'] > 0.005
                    || $row['document_variance'] > 0.01
                    ? 'incomplete'
                    : ($row['provisional_quantity'] > 0.0005 ? 'provisional' : 'confirmed');
                $row['source_invoices'] = collect($row['sources'] ?? [])->filter(fn ($source) => $source['quantity'] > 0.0005)->pluck('label')->unique()->values()->all();
                $row['quantity'] = round($row['quantity'], 3);
                $row['value'] = round($row['value'], 2);
                $row['unit_cost'] = $row['unit_cost'] === null ? null : round($row['unit_cost'], 4);
                $row['provisional_quantity'] = round($row['provisional_quantity'], 3);
                $row['unpriced_quantity'] = round($row['unpriced_quantity'], 3);
                $row['unallocated_cost'] = round($row['unallocated_cost'], 2);
                $row['document_variance'] = round($row['document_variance'], 2);
                return $row;
            })->when($locationId !== null, fn ($rows) => $rows->where('location_id', $locationId))
            ->when($productId !== null, fn ($rows) => $rows->where('product_id', $productId))
            ->sortBy([['location_name', 'asc'], ['product_name', 'asc']])->values();
    }
}
