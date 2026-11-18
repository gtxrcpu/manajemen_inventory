@forelse($comparison as $row)
    <article class="space-y-1.5 border-t border-gray-100 py-3 text-sm">
        <p class="font-semibold text-gray-900">{{ $row['product']->name }}</p>
        <p class="text-xs text-gray-500">Invoice: <strong class="text-gray-700">{{ $row['invoice_quantity'] === null ? '&mdash;' : \App\Support\QuantityFormatter::format($row['invoice_quantity']) }}</strong> &middot; PO: <strong class="text-gray-700">{{ $row['po_quantity'] === null ? '&mdash;' : \App\Support\QuantityFormatter::format($row['po_quantity']) }}</strong> &middot; Receipt: <strong class="text-gray-700">{{ $row['received_quantity'] === null ? '&mdash;' : \App\Support\QuantityFormatter::format($row['received_quantity']) }}</strong></p>
        @if($row['invoice_po_quantity_delta'] !== null || $row['invoice_receipt_quantity_delta'] !== null)
            <p class="text-xs text-gray-600">Selisih qty (Invoice - PO): <strong>{{ $row['invoice_po_quantity_delta'] === null ? '&mdash;' : \App\Support\QuantityFormatter::format($row['invoice_po_quantity_delta']) }}</strong> &middot; (Invoice - Receipt): <strong>{{ $row['invoice_receipt_quantity_delta'] === null ? '&mdash;' : \App\Support\QuantityFormatter::format($row['invoice_receipt_quantity_delta']) }}</strong></p>
        @endif
        @if($row['invoice_unit_price'] !== null)
            <p class="text-xs text-gray-600">Harga Invoice: Rp {{ number_format((float) $row['invoice_unit_price'], 2, ',', '.') }} &middot; PO: {{ $row['po_unit_price'] === null ? '&mdash;' : 'Rp '.number_format((float) $row['po_unit_price'], 2, ',', '.') }} @if($row['invoice_po_unit_price_delta'] !== null) &middot; Delta: Rp {{ number_format($row['invoice_po_unit_price_delta'], 2, ',', '.') }} @endif</p>
        @endif
        <p class="text-xs {{ $row['price_mismatch'] ? 'font-semibold text-red-600' : 'text-gray-500' }}">{{ $row['invoice_status'] }} &middot; {{ $row['receipt_status'] }} @if($row['price_mismatch']) &middot; Harga berbeda dari PO @endif</p>
    </article>
@empty
    <p class="text-sm text-gray-500 py-2">Tidak ada item untuk dibandingkan.</p>
@endforelse
