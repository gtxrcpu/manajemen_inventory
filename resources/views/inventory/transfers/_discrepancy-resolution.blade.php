@if($hasDiscrepancies)
    <div class="rounded-2xl border border-rose-200 bg-white shadow-xs overflow-hidden">
        {{-- Header --}}
        <div class="border-b border-rose-100 bg-rose-50/60 p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-rose-100">
                    <span class="material-symbols-outlined text-[18px] text-rose-700">warning</span>
                </div>
                <div>
                    <h2 class="font-semibold text-rose-900">Discrepancy Transfer</h2>
                    <p class="mt-0.5 text-xs text-rose-800">Barang yang belum diterima tetap tercatat sebagai persediaan dalam perjalanan sampai diterima atau diputuskan hilang/rusak.</p>
                </div>
            </div>
        </div>

        @unless($resolutionSchemaReady)
            <div class="mx-5 mt-4 sm:mx-6 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3">
                <span class="material-symbols-outlined text-[16px] text-amber-600 mt-0.5">info</span>
                <p class="text-xs text-amber-900">Riwayat selisih tetap tersimpan. Pencatatan keputusan discrepancy tersedia setelah migration fitur dipasang.</p>
            </div>
        @endunless

        <div class="divide-y divide-gray-100 p-5 sm:p-6 space-y-0">
            @foreach($transfer->items as $item)
                @if((float) $item->discrepancy_quantity > 0 || $item->discrepancyResolutions->isNotEmpty())
                    <article class="py-4 first:pt-0 last:pb-0 space-y-3">
                        {{-- Item header --}}
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full {{ (float) $item->discrepancy_quantity > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    <span class="material-symbols-outlined text-[14px]">{{ (float) $item->discrepancy_quantity > 0 ? 'priority_high' : 'check' }}</span>
                                </span>
                                <p class="text-sm font-semibold text-gray-900">{{ $item->product->name }}</p>
                            </div>
                            @if((float) $item->discrepancy_quantity > 0)
                                <span class="rounded-full bg-rose-50 border border-rose-200 px-2.5 py-0.5 text-xs font-semibold text-rose-700">
                                    Sisa {{ \App\Support\QuantityFormatter::format($item->discrepancy_quantity) }} {{ $item->product->unit->symbol }}
                                </span>
                            @else
                                <span class="rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">
                                    Sudah diputuskan
                                </span>
                            @endif
                        </div>

                        {{-- Discrepancy reason --}}
                        @if($item->discrepancy_reason)
                            <div class="flex items-start gap-2 rounded-lg bg-gray-50 border border-gray-100 p-2.5 text-xs text-gray-600 ml-9">
                                <span class="material-symbols-outlined text-[14px] text-gray-400 mt-0.5">comment</span>
                                <p>Alasan penerimaan: {{ $item->discrepancy_reason }}</p>
                            </div>
                        @endif

                        {{-- Resolution history --}}
                        @foreach($item->discrepancyResolutions as $resolution)
                            <div class="flex items-start gap-2 ml-9 text-xs text-gray-600">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 mt-0.5">
                                    <span class="material-symbols-outlined text-[12px] text-slate-500">gavel</span>
                                </span>
                                <div>
                                    <p>
                                        <span class="font-semibold text-gray-800">{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</span>
                                        &middot; {{ \App\Support\QuantityFormatter::format($resolution->quantity) }} {{ $item->product->unit->symbol }}
                                        &middot; {{ $resolution->resolver?->name }}
                                    </p>
                                    <p class="mt-0.5 text-gray-500 italic">&ldquo;{{ $resolution->reason }}&rdquo;</p>
                                </div>
                            </div>
                        @endforeach

                        {{-- Resolution form --}}
                        @if($resolutionSchemaReady && $canResolveDiscrepancy && $transfer->status === 'discrepancy_open' && (float) $item->discrepancy_quantity > 0)
                            <form method="POST" action="{{ route('inventory.transfers.discrepancies.resolve', [$transfer, $item->id]) }}"
                                  class="ml-9 rounded-xl border border-gray-200 bg-gray-50/50 p-4 space-y-3">
                                @csrf
                                <p class="text-xs font-semibold text-gray-800">Catat Penyelesaian</p>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="block text-xs">
                                        <span class="font-semibold text-gray-600">Keputusan</span>
                                        <select name="resolution_type" required class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-[#DC143C] focus:ring-[#DC143C]/20 transition">
                                            <option value="lost">Hilang di perjalanan</option>
                                            <option value="damaged">Rusak / tidak layak</option>
                                            <option value="other">Lainnya</option>
                                        </select>
                                    </label>
                                    <label class="block text-xs">
                                        <span class="font-semibold text-gray-600">Jumlah</span>
                                        <span class="text-gray-400 ml-1">(maks. {{ \App\Support\QuantityFormatter::format($item->discrepancy_quantity) }})</span>
                                        <input type="number" name="quantity" required min="0.001" max="{{ $item->discrepancy_quantity }}" step="0.001" value="{{ $item->discrepancy_quantity }}"
                                               class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm font-medium focus:border-[#DC143C] focus:ring-[#DC143C]/20 transition">
                                    </label>
                                </div>
                                <label class="block text-xs">
                                    <span class="font-semibold text-gray-600">Alasan / bukti tindak lanjut</span>
                                    <textarea name="reason" required minlength="5" maxlength="5000" rows="2" placeholder="Jelaskan tindak lanjut discrepancy..."
                                              class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 text-sm focus:border-[#DC143C] focus:ring-[#DC143C]/20 transition"></textarea>
                                </label>
                                <button class="w-full rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-rose-700 transition">
                                    <span class="material-symbols-outlined align-middle text-[14px] mr-1">gavel</span>
                                    Catat Penyelesaian Discrepancy
                                </button>
                            </form>
                        @endif
                    </article>
                @endif
            @endforeach
        </div>
    </div>
@endif
