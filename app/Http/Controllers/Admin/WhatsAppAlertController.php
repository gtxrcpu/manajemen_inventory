<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\NotificationProvider;
use App\Http\Controllers\Controller;
use App\Models\WhatsAppAlertLog;
use App\Models\WhatsAppAlertSetting;
use App\Models\Location;
use App\Models\PurchaseOrderWhatsAppLog;
use App\Models\StockTransferApprovalWhatsAppLog;
use App\Models\StockInApprovalWhatsAppLog;
use App\Models\StockTransferArrivalWhatsAppLog;
use App\Models\StockTransferReceiptWhatsAppLog;
use App\Services\LowStockAlertService;
use App\Services\PurchaseOrderArrivalAlertService;
use App\Services\TransferApprovalAlertService;
use App\Services\StockInApprovalAlertService;
use App\Services\TransferArrivalAlertService;
use App\Services\TransferReceiptAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WhatsAppAlertController extends Controller
{
    public function status(NotificationProvider $provider): JsonResponse
    {
        return response()->json([
            'connection' => $provider->status(),
            'qr' => $provider->qr(),
        ])->header('Cache-Control', 'no-store');
    }

    public function index(NotificationProvider $provider, LowStockAlertService $lowStock, PurchaseOrderArrivalAlertService $arrivalAlerts, TransferApprovalAlertService $transferAlerts, StockInApprovalAlertService $stockInAlerts, TransferArrivalAlertService $transferArrivalAlerts, TransferReceiptAlertService $transferReceiptAlerts): View
    {
        $settings = WhatsAppAlertSetting::query()->firstOrCreate(['id' => 1]);
        $locations = Location::query()->where('is_active', true)->orderBy('name')->get();
        $outletPhonesByLocation = $locations->where('type', 'outlet')->mapWithKeys(fn (Location $location) => [
            $location->id => collect($lowStock->outletWhatsAppRecipientsForLocation((int) $location->id)),
        ]);
        $warehouseStaffPhonesByLocation = $locations->where('type', 'warehouse')->mapWithKeys(fn (Location $location) => [
            $location->id => collect($arrivalAlerts->staffWhatsAppRecipientsForLocation((int) $location->id)),
        ]);
        $warehousePicPhonesByLocation = $locations->where('type', 'warehouse')->mapWithKeys(fn (Location $location) => [
            $location->id => collect($transferAlerts->picWarehouseRecipientsForLocation((int) $location->id)),
        ]);
        $mappedPhones = collect($settings->location_recipients ?? [])->flatten()->all();
        $unmappedLegacyRecipients = array_values(array_diff($settings->recipients ?? [], $mappedPhones));

        $historyQueries = [
            DB::table('whatsapp_alert_logs as log')
                ->join('low_stock_alerts as alert', 'alert.id', '=', 'log.low_stock_alert_id')
                ->join('products', 'products.id', '=', 'alert.product_id')
                ->join('locations', 'locations.id', '=', 'alert.location_id')
                ->selectRaw("'Stok minimum' as category, products.name as subject, locations.name as context, log.recipient, log.status, log.error_message, COALESCE(log.sent_at, log.last_attempt_at, log.created_at) as happened_at, log.created_at"),
            DB::table('purchase_order_whatsapp_alert_logs as log')
                ->join('purchase_orders as po', 'po.id', '=', 'log.purchase_order_id')
                ->join('suppliers', 'suppliers.id', '=', 'po.supplier_id')
                ->join('locations', 'locations.id', '=', 'po.location_id')
                ->selectRaw("'Kedatangan supplier' as category, po.reference as subject, CONCAT(suppliers.name, ' - ', locations.name) as context, log.recipient, log.status, log.error_message, COALESCE(log.sent_at, log.last_attempt_at, log.created_at) as happened_at, log.created_at"),
            DB::table('stock_transfer_approval_whatsapp_logs as log')
                ->join('stock_transfers as transfer', 'transfer.id', '=', 'log.stock_transfer_id')
                ->join('locations as source', 'source.id', '=', 'transfer.source_location_id')
                ->join('locations as destination', 'destination.id', '=', 'transfer.destination_location_id')
                ->selectRaw("'Persetujuan transfer' as category, transfer.reference as subject, CONCAT(source.name, ' -> ', destination.name) as context, log.recipient, log.status, log.error_message, COALESCE(log.sent_at, log.last_attempt_at, log.created_at) as happened_at, log.created_at"),
            DB::table('stock_transfer_arrival_whatsapp_logs as log')
                ->join('stock_transfers as transfer', 'transfer.id', '=', 'log.stock_transfer_id')
                ->join('locations as source', 'source.id', '=', 'transfer.source_location_id')
                ->join('locations as destination', 'destination.id', '=', 'transfer.destination_location_id')
                ->selectRaw("'Estimasi tiba transfer' as category, transfer.reference as subject, CONCAT(source.name, ' -> ', destination.name) as context, log.recipient, log.status, log.error_message, COALESCE(log.sent_at, log.last_attempt_at, log.created_at) as happened_at, log.created_at"),
            DB::table('stock_transfer_receipt_whatsapp_logs as log')
                ->join('stock_transfer_receipts as receipt', 'receipt.id', '=', 'log.stock_transfer_receipt_id')
                ->join('stock_transfers as transfer', 'transfer.id', '=', 'receipt.stock_transfer_id')
                ->join('locations as source', 'source.id', '=', 'transfer.source_location_id')
                ->join('locations as destination', 'destination.id', '=', 'transfer.destination_location_id')
                ->selectRaw("'Konfirmasi penerimaan' as category, transfer.reference as subject, CONCAT(destination.name, ' -> ', source.name) as context, log.recipient, log.status, log.error_message, COALESCE(log.sent_at, log.last_attempt_at, log.created_at) as happened_at, log.created_at"),
            DB::table('stock_in_approval_whatsapp_logs as log')
                ->join('stock_ins as stock_in', 'stock_in.id', '=', 'log.stock_in_id')
                ->join('locations', 'locations.id', '=', 'stock_in.location_id')
                ->selectRaw("'Persetujuan Stock In' as category, stock_in.reference as subject, locations.name as context, log.recipient, log.status, log.error_message, COALESCE(log.sent_at, log.last_attempt_at, log.created_at) as happened_at, log.created_at"),
        ];
        $historyQuery = array_shift($historyQueries);
        foreach ($historyQueries as $historyQueryPart) $historyQuery->unionAll($historyQueryPart);
        $history = DB::query()->fromSub($historyQuery, 'message_history')
            ->orderByDesc('happened_at')->orderByDesc('created_at')
            ->paginate(10, ['*'], 'history_page');

        return view('admin.whatsapp-alerts.index', [
            'settings' => $settings,
            'locations' => $locations,
            'outletPhonesByLocation' => $outletPhonesByLocation,
            'warehouseStaffPhonesByLocation' => $warehouseStaffPhonesByLocation,
            'warehousePicPhonesByLocation' => $warehousePicPhonesByLocation,
            'unmappedLegacyRecipients' => $unmappedLegacyRecipients,
            'history' => $history,
            'connection' => $provider->status(),
            'qr' => $provider->qr(),
        ]);
    }

    public function update(Request $request, NotificationProvider $provider, LowStockAlertService $lowStock, PurchaseOrderArrivalAlertService $arrivalAlerts, TransferApprovalAlertService $transferAlerts, StockInApprovalAlertService $stockInAlerts, TransferArrivalAlertService $transferArrivalAlerts, TransferReceiptAlertService $transferReceiptAlerts): RedirectResponse
    {
        $data = $request->validate([
            'location_recipients' => ['nullable', 'array'],
            'location_recipients.*' => ['nullable', 'string', 'max:4000'],
            'warehouse_staff_recipients' => ['nullable', 'array'],
            'warehouse_staff_recipients.*' => ['nullable', 'string', 'max:4000'],
            'warehouse_pic_recipients' => ['nullable', 'array'],
            'warehouse_pic_recipients.*' => ['nullable', 'string', 'max:4000'],
            'message_template' => ['required', 'string', 'max:1000'],
            'low_stock_enabled' => ['nullable', 'boolean'],
            'supplier_arrival_enabled' => ['nullable', 'boolean'],
            'transfer_approval_enabled' => ['nullable', 'boolean'],
            'transfer_arrival_enabled' => ['nullable', 'boolean'],
            'transfer_receipt_enabled' => ['nullable', 'boolean'],
            'stock_in_approval_enabled' => ['nullable', 'boolean'],
            'risk_accepted' => ['nullable', 'boolean'],
        ]);

        $activeLocationIds = Location::query()->where('is_active', true)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $submittedLocationIds = array_map('strval', array_merge(array_keys($data['location_recipients'] ?? []), array_keys($data['warehouse_staff_recipients'] ?? []), array_keys($data['warehouse_pic_recipients'] ?? [])));
        if (array_diff($submittedLocationIds, $activeLocationIds)) {
            throw ValidationException::withMessages(['location_recipients' => 'Lokasi penerima tidak valid atau sudah nonaktif. Muat ulang halaman lalu coba lagi.']);
        }
        $warehouseIds = Location::query()->where('is_active', true)->where('type', 'warehouse')->pluck('id')->map(fn ($id) => (string) $id)->all();
        if (array_diff(array_map('strval', array_keys($data['warehouse_staff_recipients'] ?? [])), $warehouseIds)
            || array_diff(array_map('strval', array_keys($data['warehouse_pic_recipients'] ?? [])), $warehouseIds)) {
            throw ValidationException::withMessages(['warehouse_staff_recipients' => 'Nomor Staff dan PIC hanya dapat dipetakan ke gudang aktif.']);
        }
        $outletIds = Location::query()->where('is_active', true)->where('type', 'outlet')->pluck('id')->map(fn ($id) => (string) $id)->all();
        if (array_diff(array_map('strval', array_keys($data['location_recipients'] ?? [])), $outletIds)) {
            throw ValidationException::withMessages(['location_recipients' => 'Nomor tambahan umum hanya dapat dipetakan ke outlet aktif.']);
        }

        $locationRecipients = [];
        foreach (($data['location_recipients'] ?? []) as $locationId => $input) {
            $recipients = collect(preg_split('/[,;\r\n]+/', (string) $input))
                ->map(fn (string $phone) => preg_replace('/\D+/', '', $phone))
                ->filter()->unique()->values();
            foreach ($recipients as $phone) {
                if (! preg_match('/^[1-9][0-9]{7,14}$/', $phone)) {
                    throw ValidationException::withMessages(['location_recipients' => 'Gunakan nomor internasional, misalnya 6281234567890.']);
                }
            }
            if ($recipients->isNotEmpty()) $locationRecipients[(string) $locationId] = $recipients->all();
        }

        $normalizeByLocation = function (array $inputs, string $field) use ($activeLocationIds): array {
            $normalized = [];
            foreach ($inputs as $locationId => $input) {
                if (! in_array((string) $locationId, $activeLocationIds, true)) continue;
                $recipients = collect(preg_split('/[,;\r\n]+/', (string) $input))
                    ->map(fn (string $phone) => preg_replace('/\D+/', '', $phone))->filter()->unique()->values();
                foreach ($recipients as $phone) {
                    if (! preg_match('/^[1-9][0-9]{7,14}$/', $phone)) {
                        throw ValidationException::withMessages([$field => 'Gunakan nomor internasional, misalnya 6281234567890.']);
                    }
                }
                if ($recipients->isNotEmpty()) $normalized[(string) $locationId] = $recipients->all();
            }
            return $normalized;
        };
        $warehouseStaffRecipients = $normalizeByLocation($data['warehouse_staff_recipients'] ?? [], 'warehouse_staff_recipients');
        $warehousePicRecipients = $normalizeByLocation($data['warehouse_pic_recipients'] ?? [], 'warehouse_pic_recipients');

        $riskAccepted = (bool) ($data['risk_accepted'] ?? false);
        $lowStockEnabled = (bool) ($data['low_stock_enabled'] ?? false);
        $supplierArrivalEnabled = (bool) ($data['supplier_arrival_enabled'] ?? false);
        $transferApprovalEnabled = (bool) ($data['transfer_approval_enabled'] ?? false);
        $transferArrivalEnabled = (bool) ($data['transfer_arrival_enabled'] ?? false);
        $transferReceiptEnabled = (bool) ($data['transfer_receipt_enabled'] ?? false);
        $stockInApprovalEnabled = (bool) ($data['stock_in_approval_enabled'] ?? false);
        $enabled = $lowStockEnabled || $supplierArrivalEnabled || $transferApprovalEnabled || $transferArrivalEnabled || $transferReceiptEnabled || $stockInApprovalEnabled;
        if ($enabled && ! config('services.whatsapp.provider_approved')) {
            throw ValidationException::withMessages(['low_stock_enabled' => 'Provider WhatsApp belum disetujui untuk diaktifkan. Tinjau risiko provider dan hasil audit dependency terlebih dahulu.']);
        }
        if ($enabled && ! $riskAccepted) {
            throw ValidationException::withMessages(['risk_accepted' => 'Persetujuan risiko wajib dicentang sebelum alert WhatsApp diaktifkan.']);
        }
        $hasOutletRecipient = Location::query()->where('is_active', true)->where('type', 'outlet')->get()
            ->contains(fn (Location $location) => ! empty($lowStock->outletWhatsAppRecipientsForLocation((int) $location->id)));
        $warehouses = Location::query()->where('is_active', true)->where('type', 'warehouse')->get();
        $outlets = Location::query()->where('is_active', true)->where('type', 'outlet')->get();
        $hasWarehouseStaffRecipient = $warehouses->contains(fn (Location $location) =>
            ! empty($arrivalAlerts->staffWhatsAppRecipientsForLocation((int) $location->id))
            || ! empty($warehouseStaffRecipients[(string) $location->id] ?? []));
        if ($lowStockEnabled && empty($locationRecipients) && empty($warehouseStaffRecipients) && ! $hasOutletRecipient && ! $warehouses->contains(fn (Location $location) => ! empty($arrivalAlerts->staffWhatsAppRecipientsForLocation((int) $location->id)))) {
            throw ValidationException::withMessages(['location_recipients' => 'Tambahkan nomor penerima stok minimum pada lokasi atau pastikan PIC/Staff Outlet memiliki nomor WhatsApp.']);
        }
        if ($supplierArrivalEnabled && ! $hasWarehouseStaffRecipient) {
            throw ValidationException::withMessages(['warehouse_staff_recipients' => 'Tambahkan nomor staf Gudang di profil atau nomor tambahan Staff Gudang sebelum mengaktifkan alert kedatangan supplier.']);
        }
        $hasWarehousePicRecipient = $warehouses->contains(fn (Location $location) =>
            ! empty($transferAlerts->picWarehouseRecipientsForLocation((int) $location->id))
            || ! empty($warehousePicRecipients[(string) $location->id] ?? []));
        if ($transferApprovalEnabled && ! $hasWarehousePicRecipient) {
            throw ValidationException::withMessages(['warehouse_pic_recipients' => 'Tambahkan PIC Gudang bernomor WhatsApp atau nomor tambahan PIC Gudang sebelum mengaktifkan alert persetujuan transfer.']);
        }
        $hasTransferOutletStaffRecipient = $outlets->contains(fn (Location $location) => ! empty($transferArrivalAlerts->staffRecipientsForOutlet((int) $location->id)));
        $hasTransferWarehouseStaffRecipient = $warehouses->contains(fn (Location $location) =>
            ! empty($transferReceiptAlerts->staffRecipientsForWarehouse((int) $location->id))
            || ! empty($warehouseStaffRecipients[(string) $location->id] ?? []));
        if ($transferArrivalEnabled && ! $hasTransferOutletStaffRecipient) {
            throw ValidationException::withMessages(['transfer_arrival_enabled' => 'Pastikan minimal satu Staff Outlet aktif di outlet tujuan memiliki nomor WhatsApp.']);
        }
        if ($transferReceiptEnabled && ! $hasTransferWarehouseStaffRecipient) {
            throw ValidationException::withMessages(['transfer_receipt_enabled' => 'Pastikan minimal satu Staff Gudang aktif di gudang asal memiliki nomor WhatsApp.']);
        }
        $hasStockInPicRecipient = $outlets->contains(fn (Location $location) =>
            ! empty($stockInAlerts->picRecipientsForLocation((int) $location->id))
            || ! empty($locationRecipients[(string) $location->id] ?? []));
        if ($stockInApprovalEnabled && ! $hasStockInPicRecipient) {
            throw ValidationException::withMessages(['stock_in_approval_enabled' => 'Pastikan PIC Outlet memiliki nomor WhatsApp sebelum mengaktifkan alert persetujuan Stock In.']);
        }
        $settings = WhatsAppAlertSetting::query()->firstOrCreate(['id' => 1]);
        $settings->update([
            'enabled' => $enabled,
            'low_stock_enabled' => $lowStockEnabled,
            'supplier_arrival_enabled' => $supplierArrivalEnabled,
            'transfer_approval_enabled' => $transferApprovalEnabled,
            'transfer_arrival_enabled' => $transferArrivalEnabled,
            'transfer_receipt_enabled' => $transferReceiptEnabled,
            'stock_in_approval_enabled' => $stockInApprovalEnabled,
            'location_recipients' => $locationRecipients,
            'warehouse_staff_recipients' => $warehouseStaffRecipients,
            'warehouse_pic_recipients' => $warehousePicRecipients,
            'message_template' => $data['message_template'],
            'risk_accepted_at' => $riskAccepted ? ($settings->risk_accepted_at ?? now()) : null,
        ]);

        WhatsAppAlertLog::query()->with('alert.location')->whereIn('status', ['queued', 'sending'])
            ->get()->each(function (WhatsAppAlertLog $log) use ($lowStockEnabled, $riskAccepted, $lowStock) {
                if ($lowStockEnabled && $riskAccepted && $lowStock->isWhatsAppRecipientForAlert($log->alert, $log->recipient)) return;

                $log->update([
                    'status' => 'failed',
                    'error_message' => $lowStockEnabled
                        ? 'Nomor penerima tidak terdaftar untuk lokasi alert ini.'
                        : 'Alert stok minimum dinonaktifkan sebelum pesan dikirim.',
                ]);
            });

        PurchaseOrderWhatsAppLog::query()->with('purchaseOrder')->whereIn('status', ['queued', 'sending'])
            ->get()->each(function (PurchaseOrderWhatsAppLog $log) use ($supplierArrivalEnabled, $riskAccepted, $arrivalAlerts) {
                if ($supplierArrivalEnabled && $riskAccepted && $arrivalAlerts->isRecipientForOrder($log->purchaseOrder, $log->recipient)) return;

                $log->update([
                    'status' => 'failed',
                    'error_message' => $supplierArrivalEnabled
                        ? 'Nomor penerima tidak terdaftar untuk gudang PO ini.'
                        : 'Alert kedatangan supplier dinonaktifkan sebelum pesan dikirim.',
                ]);
            });

        StockTransferApprovalWhatsAppLog::query()->with('transfer')->whereIn('status', ['queued', 'sending'])
            ->get()->each(function (StockTransferApprovalWhatsAppLog $log) use ($transferApprovalEnabled, $riskAccepted, $transferAlerts) {
                if ($transferApprovalEnabled && $riskAccepted && $transferAlerts->isRecipientForTransfer($log->transfer, $log->recipient)) return;

                $log->update([
                    'status' => 'failed',
                    'error_message' => $transferApprovalEnabled
                        ? 'Nomor penerima tidak terdaftar untuk gudang transfer ini.'
                        : 'Alert persetujuan transfer dinonaktifkan sebelum pesan dikirim.',
                ]);
            });

        StockInApprovalWhatsAppLog::query()->with('stockIn')->whereIn('status', ['queued', 'sending'])
            ->get()->each(function (StockInApprovalWhatsAppLog $log) use ($stockInApprovalEnabled, $riskAccepted, $stockInAlerts) {
                if ($stockInApprovalEnabled && $riskAccepted && $stockInAlerts->isRecipientForStockIn($log->stockIn, $log->recipient)) return;
                $log->update(['status' => 'failed', 'error_message' => $stockInApprovalEnabled
                    ? 'Nomor penerima tidak lagi terdaftar sebagai PIC untuk outlet Stock In ini.'
                    : 'Alert persetujuan Stock In dinonaktifkan sebelum pesan dikirim.']);
            });

        StockTransferArrivalWhatsAppLog::query()->with('transfer')->whereIn('status', ['queued', 'sending'])
            ->get()->each(function (StockTransferArrivalWhatsAppLog $log) use ($transferArrivalEnabled, $riskAccepted, $transferArrivalAlerts) {
                if ($transferArrivalEnabled && $riskAccepted && $transferArrivalAlerts->isRecipientForTransfer($log->transfer, $log->recipient)) return;

                $log->update(['status' => 'failed', 'error_message' => $transferArrivalEnabled
                    ? 'Nomor penerima tidak lagi terdaftar sebagai Staff Outlet di lokasi tujuan transfer.'
                    : 'Alert ETA transfer dinonaktifkan sebelum pesan dikirim.']);
            });

        StockTransferReceiptWhatsAppLog::query()->with('receipt.transfer')->whereIn('status', ['queued', 'sending'])
            ->get()->each(function (StockTransferReceiptWhatsAppLog $log) use ($transferReceiptEnabled, $riskAccepted, $transferReceiptAlerts) {
                if ($transferReceiptEnabled && $riskAccepted && $transferReceiptAlerts->isRecipientForReceipt($log->receipt, $log->recipient)) return;

                $log->update(['status' => 'failed', 'error_message' => $transferReceiptEnabled
                    ? 'Nomor penerima tidak lagi terdaftar sebagai Staff Gudang di lokasi asal.'
                    : 'Alert status transfer dinonaktifkan sebelum pesan dikirim.']);
            });

        if ($enabled) {
            if ($lowStockEnabled) $lowStock->queueActiveWhatsAppAlerts();
            if ($supplierArrivalEnabled) $arrivalAlerts->queueUpcoming();
            if ($transferApprovalEnabled) $transferAlerts->queuePendingApprovals();
            if ($stockInApprovalEnabled) $stockInAlerts->queuePendingApprovals();
            if (! ($provider->status()['connected'] ?? false)) {
                return back()->with('status', 'Pengaturan tersimpan. Alert yang dipilih menunggu layanan WhatsApp siap untuk dikirim.');
            }
        }

        return back()->with('status', $enabled
            ? 'Pilihan alert WhatsApp tersimpan dan akan dikirim melalui antrean.'
            : 'Semua alert WhatsApp dinonaktifkan.');
    }
}
