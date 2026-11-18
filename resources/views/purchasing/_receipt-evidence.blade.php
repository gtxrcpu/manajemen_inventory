<div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
    <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-semibold text-slate-800">Foto bukti kedatangan</p>
        <span class="text-[10px] text-slate-500">{{ $receipt->evidences->count() }} foto</span>
    </div>
    @if($receipt->evidences->isNotEmpty())
        <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
            @foreach($receipt->evidences as $evidence)
                <a href="{{ route('inventory.stock-in.evidence', [$receipt, $evidence]) }}" target="_blank" rel="noopener" class="group overflow-hidden rounded-lg border border-slate-200 bg-white" aria-label="Lihat foto bukti {{ $loop->iteration }}">
                    <img src="{{ route('inventory.stock-in.evidence', [$receipt, $evidence]) }}" alt="Foto kedatangan supplier {{ $loop->iteration }}" loading="lazy" class="h-28 w-full object-cover transition duration-200 group-hover:scale-[1.03] sm:h-36">
                    <span class="block truncate px-2 py-1.5 text-[10px] text-slate-600">{{ $evidence->original_name }}</span>
                </a>
            @endforeach
        </div>
    @else
        <p class="mt-1 text-[11px] text-slate-500">Belum ada foto bukti untuk penerimaan ini.</p>
    @endif
</div>
