<div>
    <div class="mb-3 flex items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-1.5">
                <span class="material-symbols-outlined text-gray-600 text-[18px]">contacts</span>
                <span class="text-sm font-semibold text-gray-900">Penerima WhatsApp per lokasi dan peran</span>
            </div>
            <p class="mt-1 text-xs text-gray-500">Nomor Staff dan PIC Gudang diatur terpisah. Nomor otomatis berasal dari profil aktif yang ditugaskan.</p>
        </div>
        <span class="shrink-0 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">{{ $locations->count() }} lokasi</span>
    </div>

    <label class="mb-2 block">
        <span class="sr-only">Cari lokasi</span>
        <div class="flex items-center gap-2 rounded-lg border border-gray-300 px-3 focus-within:border-gray-500">
            <span class="material-symbols-outlined text-[18px] text-gray-400">search</span>
            <input type="search" data-location-recipient-filter placeholder="Cari outlet atau gudang..." class="w-full border-0 px-0 py-2.5 text-sm focus:ring-0">
        </div>
    </label>

    <div class="max-h-[26rem] space-y-2 overflow-y-auto pr-1" data-location-recipient-list>
        @forelse($locations as $location)
            @php($outletUsers = $outletPhonesByLocation->get($location->id, collect()))
            @php($warehouseUsers = $warehouseStaffPhonesByLocation->get($location->id, collect()))
            @php($warehousePicUsers = $warehousePicPhonesByLocation->get($location->id, collect()))
            @php($legacyPhones = $settings->location_recipients[(string) $location->id] ?? [])
            @php($staffManual = old('warehouse_staff_recipients.'.$location->id, implode("\n", $settings->warehouse_staff_recipients[(string) $location->id] ?? $legacyPhones)))
            @php($picManual = old('warehouse_pic_recipients.'.$location->id, implode("\n", $settings->warehouse_pic_recipients[(string) $location->id] ?? $legacyPhones)))
            @php($outletManual = old('location_recipients.'.$location->id, implode("\n", $legacyPhones)))
            @php($staffManualCount = collect(preg_split('/[,;\r\n]+/', (string) $staffManual))->filter()->unique()->count())
            @php($picManualCount = collect(preg_split('/[,;\r\n]+/', (string) $picManual))->filter()->unique()->count())
            @php($outletManualCount = collect(preg_split('/[,;\r\n]+/', (string) $outletManual))->filter()->unique()->count())
            @php($automaticPhones = ($location->type === 'outlet' ? $outletUsers : collect($warehouseUsers)->merge(collect($warehousePicUsers)))->pluck('whatsapp_phone')->unique())
            @php($manualCount = $location->type === 'outlet' ? $outletManualCount : ($staffManualCount + $picManualCount))
            @php($activeRecipientCount = $automaticPhones->merge(collect([$staffManualCount, $picManualCount, $outletManualCount])->filter())->count())
            <details data-location-recipient-row data-location-name="{{ mb_strtolower($location->name) }}" class="group rounded-lg border border-gray-200 bg-white">
                <summary class="flex cursor-pointer list-none flex-wrap items-center gap-2.5 px-3 py-3 [&::-webkit-details-marker]:hidden">
                    <span class="material-symbols-outlined text-gray-400 transition-transform group-open:rotate-90">chevron_right</span>
                    <span class="min-w-0 flex-1 truncate text-sm font-semibold text-gray-900">{{ $location->name }}</span>
                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-gray-600">{{ $location->type === 'outlet' ? 'Outlet' : 'Gudang' }}</span>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $activeRecipientCount ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">{{ $activeRecipientCount ? $activeRecipientCount.' penerima terdaftar' : 'Belum ada penerima' }}</span>
                </summary>

                <div class="border-t border-gray-100 px-3 pb-3 pt-2 sm:pl-10">
                    @if($location->type === 'outlet')
                        @if($outletUsers->isNotEmpty())
                            <div class="mb-3 space-y-1">
                                <p class="text-xs font-semibold text-gray-600">PIC/Staff Outlet otomatis menerima stok minimum</p>
                                @foreach($outletUsers as $outletUser)
                                    <p class="text-xs text-gray-600">{{ $outletUser['name'] }} <span class="text-gray-400">· {{ $outletUser['role_label'] }}</span> <span class="font-mono">{{ $outletUser['whatsapp_phone'] }}</span></p>
                                @endforeach
                            </div>
                        @else
                            <p class="mb-3 rounded-lg bg-amber-50 p-2.5 text-xs text-amber-900">Belum ada PIC/Staff Outlet aktif bernomor WhatsApp. Nomor tambahan di bawah dapat menerima alert outlet.</p>
                        @endif
                        <label class="block text-xs font-medium text-gray-700" for="location-recipients-{{ $location->id }}">Nomor tambahan untuk {{ $location->name }}</label>
                        <textarea id="location-recipients-{{ $location->id }}" name="location_recipients[{{ $location->id }}]" rows="2" placeholder="6281234567890" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 font-mono text-sm focus:border-gray-400 focus:ring-0">{{ $outletManual }}</textarea>
                        <span class="mt-1 block text-xs text-gray-500">Nomor ini menerima alert stok minimum dan persetujuan Stock In outlet.</span>
                    @else
                        <div class="mb-3 space-y-1">
                            <p class="text-xs font-semibold text-gray-600">Staff Gudang otomatis untuk info kedatangan PO</p>
                            @forelse($warehouseUsers as $staff)
                                <p class="text-xs text-gray-600">{{ $staff['name'] }} <span class="font-mono">{{ $staff['whatsapp_phone'] }}</span></p>
                            @empty
                                <p class="rounded-lg bg-amber-50 p-2.5 text-xs text-amber-900">Belum ada nomor WA Staff Gudang di profil.</p>
                            @endforelse
                        </div>
                        <label class="block text-xs font-medium text-gray-700" for="warehouse-staff-recipients-{{ $location->id }}">Nomor tambahan Staff Gudang · PO supplier dan alert stok gudang</label>
                        <textarea id="warehouse-staff-recipients-{{ $location->id }}" name="warehouse_staff_recipients[{{ $location->id }}]" rows="2" placeholder="6281234567890" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 font-mono text-sm focus:border-gray-400 focus:ring-0">{{ $staffManual }}</textarea>

                        <div class="mb-2 mt-4 space-y-1">
                            <p class="text-xs font-semibold text-violet-900">PIC Gudang otomatis untuk persetujuan transfer</p>
                            @forelse($warehousePicUsers as $pic)
                                <p class="text-xs text-violet-900">{{ $pic['name'] }} <span class="font-mono">{{ $pic['whatsapp_phone'] }}</span></p>
                            @empty
                                <p class="rounded-lg bg-violet-50 p-2.5 text-xs text-violet-900">Belum ada PIC Gudang aktif bernomor WA.</p>
                            @endforelse
                        </div>
                        <label class="block text-xs font-medium text-gray-700" for="warehouse-pic-recipients-{{ $location->id }}">Nomor tambahan PIC Gudang · persetujuan transfer</label>
                        <textarea id="warehouse-pic-recipients-{{ $location->id }}" name="warehouse_pic_recipients[{{ $location->id }}]" rows="2" placeholder="6281234567890" class="mt-1 w-full rounded-lg border border-gray-300 p-2.5 font-mono text-sm focus:border-gray-400 focus:ring-0">{{ $picManual }}</textarea>
                        <span class="mt-1 block text-xs text-gray-500">Satu nomor per baris. Nomor Staff dan PIC terpisah; isi keduanya jika satu nomor perlu menerima kedua jenis alert.</span>
                    @endif
                </div>
            </details>
        @empty
            <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Belum ada lokasi aktif untuk diatur.</p>
        @endforelse
        @if($locations->isNotEmpty())
            <p data-location-recipient-empty class="hidden rounded-lg bg-gray-50 p-3 text-center text-xs text-gray-500">Lokasi tidak ditemukan.</p>
        @endif
    </div>
</div>
