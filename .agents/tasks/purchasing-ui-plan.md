# Implementation Plan: Purchasing Module UI/UX Upgrade

## Design System Context

**Existing Design Patterns Found:**
- TailwindCSS with colors: primary=#DC143C, secondary=gray-500, borders #EAEAEA
- Card styles: `rounded-2xl`, `rounded-xl`, `border border-gray-200`, `shadow-sm`
- Typography: `font-poppins` for headings, `font-inter` base
- Icons: `material-symbols-outlined` 
- KPI cards: Icon in colored rounded container (bg-blue-100, bg-orange-50, etc.) + uppercase label + large bold value + subtitle
- Mobile: px-4 py-3 or p-4, gap-3, grid-cols-2 for 2-col layouts
- Desktop: max-w-5xl container, grid layouts (grid-cols-2, xl:grid-cols-4)
- Back links: `text-sm font-semibold text-[#DC143C]` 
- Primary buttons: `bg-[#DC143C] px-5 py-3 text-sm font-semibold text-white rounded-xl`
- Status badges: colored rounded-full pills with colored backgrounds
- Info boxes: bg-gray-50 p-4 or p-3 rounded-xl with border-top/border

**Reference Files Patterns:**
- purchasing/index.blade.php: KPI cards (4-column desktop, 2-column mobile), two tables with section headers, filter toolbar
- dashboard.blade.php: Stat cards with icon-in-container on right, uppercase label, bold value, subtitle

---

## File Changes

### 1. request-show.blade.php — Desktop Section (MAIN TARGET)

**WHAT:** Replace the flat 2-column `dl` grid with 4 stat cards matching the KPI card style from purchasing/index.blade.php. Convert the divide-y item list into a proper card grid layout.

**WHERE:** Lines in `@section('desktop-content')` block

**CHANGES:**

#### A. Replace lines containing `<dl class="mt-5 grid grid-cols-2 gap-4 text-sm md:grid-cols-4">` to the end of `</dl>` (the entire definition list block):

OLD (existing definition list):
```blade
        <dl class="mt-5 grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
            <div><dt class="text-gray-500">Pemohon</dt><dd class="mt-1 font-semibold">{{ $purchaseRequest->requester->name }}</dd></div>
            <div><dt class="text-gray-500">Tanggal dibutuhkan</dt><dd class="mt-1 font-semibold">{{ $purchaseRequest->required_date?->format('d M Y') ?? 'Belum ditentukan' }}</dd></div>
            @if ($purchaseRequest->reviewer)
                <div><dt class="text-gray-500">Ditinjau oleh</dt><dd class="mt-1 font-semibold">{{ $purchaseRequest->reviewer->name }}</dd></div>
                <div><dt class="text-gray-500">Waktu tinjauan</dt><dd class="mt-1 font-semibold">{{ $purchaseRequest->reviewed_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
            @endif
        </dl>
```

NEW (4 stat cards):
```blade
        {{-- KPI Stat Cards (4 columns) --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            {{-- Card 1: Status --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</p>
                        <p class="mt-2 text-xl font-bold text-gray-800">{{ $statusLabels[$purchaseRequest->status] ?? ucfirst($purchaseRequest->status) }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $purchaseRequest->created_at->format('d M Y') }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $purchaseRequest->status === 'approved' ? 'bg-green-100' : ($purchaseRequest->status === 'pending_approval' ? 'bg-amber-100' : 'bg-gray-100') }}">
                        <span class="material-symbols-outlined text-[22px] {{ $purchaseRequest->status === 'approved' ? 'text-green-600' : ($purchaseRequest->status === 'pending_approval' ? 'text-amber-600' : 'text-gray-600') }}">{{ $purchaseRequest->status === 'approved' ? 'check_circle' : 'schedule' }}</span>
                    </div>
                </div>
            </div>

            {{-- Card 2: Pemohon --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pemohon</p>
                        <p class="mt-2 text-lg font-bold text-gray-800">{{ $purchaseRequest->requester->name }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $purchaseRequest->requester->email ?? '—' }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100">
                        <span class="material-symbols-outlined text-[22px] text-blue-600">person</span>
                    </div>
                </div>
            </div>

            {{-- Card 3: Tanggal Dibutuhkan --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Dibutuhkan</p>
                        <p class="mt-2 text-xl font-bold text-gray-800">{{ $purchaseRequest->required_date?->format('d M Y') ?? '—' }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $purchaseRequest->required_date ? $purchaseRequest->required_date->diffForHumans() : 'Belum ditentukan' }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-100">
                        <span class="material-symbols-outlined text-[22px] text-orange-600">calendar_month</span>
                    </div>
                </div>
            </div>

            {{-- Card 4: Jumlah Produk --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Jumlah Produk</p>
                        <p class="mt-2 text-3xl font-bold text-gray-800">{{ $purchaseRequest->items->count() }}</p>
                        <p class="mt-1 text-xs text-gray-500">jenis produk</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100">
                        <span class="material-symbols-outlined text-[22px] text-indigo-600">package_2</span>
                    </div>
                </div>
            </div>
        </div>
```

#### B. Replace the items section: lines containing `<div class="overflow-hidden rounded-2xl border border-[#EAEAEA] bg-white">` through `</div>` (entire items table):

OLD (divide-y table):
```blade
    <div class="overflow-hidden rounded-2xl border border-[#EAEAEA] bg-white">
        <div class="border-b p-5"><h2 class="font-semibold">Barang yang diminta</h2><p class="mt-1 text-sm text-gray-500">{{ $purchaseRequest->items->count() }} jenis produk</p></div>
        <div class="divide-y">
            @foreach ($purchaseRequest->items as $item)
                <div class="flex items-center justify-between gap-4 px-5 py-4"><div><p class="font-medium">{{ $item->product->name }}</p><p class="text-sm text-gray-500">{{ $item->product->sku }}</p></div><p class="shrink-0 font-semibold">{{ number_format($item->quantity, 3, ',', '.') }} {{ $item->product->unit->symbol }}</p></div>
            @endforeach
        </div>
    </div>
```

NEW (card grid):
```blade
    <section>
        <h2 class="text-lg font-semibold mb-4">Barang yang diminta</h2>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($purchaseRequest->items as $item)
                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3 mb-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 shrink-0">
                            <span class="material-symbols-outlined text-[18px] text-gray-600">package_2</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-800 truncate">{{ $item->product->name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $item->product->sku }}</p>
                        </div>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3">
                        <p class="text-xs text-gray-500 mb-1">Jumlah diminta</p>
                        <p class="text-lg font-bold text-gray-800">{{ number_format($item->quantity, 3, ',', '.') }} <span class="text-sm text-gray-500">{{ $item->product->unit->symbol }}</span></p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
```

#### C. Update action buttons: Replace the final button/link section (after `@endif` for approved status):

OLD (basic buttons):
```blade
    @if ($relatedOrder)
        <a href="{{ route('purchasing.orders.show', $relatedOrder) }}" class="inline-flex rounded-xl border border-[#EAEAEA] bg-white px-5 py-3 text-sm font-semibold">Lihat Purchase Order {{ $relatedOrder->reference }}</a>
    @elseif ($purchaseRequest->status === 'approved' && auth()->user()->hasPermissionTo('purchasing.manage'))
        <a href="{{ route('purchasing.orders.create', $purchaseRequest) }}" class="inline-flex rounded-xl bg-[#DC143C] px-5 py-3 text-sm font-semibold text-white">Buat Purchase Order</a>
    @endif
```

NEW (styled buttons with icons):
```blade
    <div class="flex flex-col gap-3 sm:flex-row">
        @if ($relatedOrder)
            <a href="{{ route('purchasing.orders.show', $relatedOrder) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold hover:bg-gray-50 transition-colors">
                <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                Lihat Purchase Order {{ $relatedOrder->reference }}
            </a>
        @elseif ($purchaseRequest->status === 'approved' && auth()->user()->hasPermissionTo('purchasing.manage'))
            <a href="{{ route('purchasing.orders.create', $purchaseRequest) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#DC143C] px-5 py-3 text-sm font-semibold text-white hover:bg-[#b01030] transition-colors shadow-sm">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Buat Purchase Order
            </a>
        @endif
    </div>
```

---

### 2. request-show.blade.php — Mobile Section

**WHAT:** Update mobile stat display and items list to match mobile index.blade.php patterns with mini stat cards and better spacing.

**CHANGES:**

#### A. Replace mobile stat section (lines in `@section('mobile-content')` containing `<dl class="mt-4 grid grid-cols-2 gap-3 text-xs">` to `</dl>`):

OLD:
```blade
        <dl class="mt-4 grid grid-cols-2 gap-3 text-xs">
            <div><dt class="text-secondary">Pemohon</dt><dd class="mt-1 font-semibold">{{ $purchaseRequest->requester->name }}</dd></div>
            <div><dt class="text-secondary">Tanggal dibutuhkan</dt><dd class="mt-1 font-semibold">{{ $purchaseRequest->required_date?->format('d M Y') ?? 'Belum ditentukan' }}</dd></div>
            @if ($purchaseRequest->reviewer)
                <div><dt class="text-secondary">Ditinjau oleh</dt><dd class="mt-1 font-semibold">{{ $purchaseRequest->reviewer->name }}</dd></div>
                <div><dt class="text-secondary">Waktu tinjauan</dt><dd class="mt-1 font-semibold">{{ $purchaseRequest->reviewed_at?->format('d M Y, H:i') ?? '—' }}</dd></div>
            @endif
        </dl>
```

NEW (2-column mini stat cards):
```blade
        <div class="grid grid-cols-2 gap-3">
            {{-- Mini Stat Card 1: Pemohon --}}
            <div class="rounded-2xl bg-white border border-outline p-3.5">
                <p class="text-xs text-secondary">Pemohon</p>
                <p class="mt-1.5 font-poppins font-bold text-gray-800 text-sm">{{ $purchaseRequest->requester->name }}</p>
                <p class="text-[10px] text-secondary mt-1">{{ $purchaseRequest->created_at->format('d M Y') }}</p>
            </div>

            {{-- Mini Stat Card 2: Tanggal Dibutuhkan --}}
            <div class="rounded-2xl bg-white border border-outline p-3.5">
                <p class="text-xs text-secondary">Dibutuhkan</p>
                <p class="mt-1.5 font-poppins font-bold text-gray-800 text-sm">{{ $purchaseRequest->required_date?->format('d M') ?? '—' }}</p>
                <p class="text-[10px] text-secondary mt-1">{{ $purchaseRequest->required_date?->format('Y') ?? 'TBD' }}</p>
            </div>
        </div>
```

#### B. Replace items list section (lines containing `<div class="mt-2 divide-y divide-outline">` to `</div>`):

OLD:
```blade
        <div class="mt-2 divide-y divide-outline">
            @foreach ($purchaseRequest->items as $item)
                <div class="flex items-center justify-between gap-3 py-3">
                    <div><p class="text-sm font-medium">{{ $item->product->name }}</p><p class="text-xs text-secondary">{{ $item->product->sku }}</p></div>
                    <p class="shrink-0 text-sm font-semibold">{{ number_format($item->quantity, 3, ',', '.') }} {{ $item->product->unit->symbol }}</p>
                </div>
            @endforeach
        </div>
```

NEW (item cards):
```blade
        <div class="space-y-2">
            @foreach ($purchaseRequest->items as $item)
                <div class="rounded-2xl bg-white border border-outline p-3.5 flex items-start gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 shrink-0">
                        <span class="material-symbols-outlined text-[16px] text-gray-600">package_2</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $item->product->name }}</p>
                        <p class="text-xs text-secondary truncate">{{ $item->product->sku }}</p>
                        <div class="mt-2 rounded-lg bg-gray-50 px-2 py-1.5">
                            <p class="text-xs font-semibold text-gray-700">{{ number_format($item->quantity, 3, ',', '.') }} {{ $item->product->unit->symbol }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
```

#### C. Update mobile buttons section (after mobile items):

OLD:
```blade
    @if ($relatedOrder)
        <a href="{{ route('purchasing.orders.show', $relatedOrder) }}" class="block rounded-xl border border-outline bg-white p-4 text-center text-sm font-semibold">Lihat Purchase Order {{ $relatedOrder->reference }}</a>
    @elseif ($purchaseRequest->status === 'approved' && auth()->user()->hasPermissionTo('purchasing.manage'))
        <a href="{{ route('purchasing.orders.create', $purchaseRequest) }}" class="block rounded-xl bg-primary p-3 text-center text-sm font-semibold text-white">Buat Purchase Order</a>
    @endif
```

NEW:
```blade
    @if ($relatedOrder)
        <a href="{{ route('purchasing.orders.show', $relatedOrder) }}" class="flex items-center justify-center gap-2 rounded-xl border border-outline bg-white p-3 text-center text-sm font-semibold">
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
            Lihat PO {{ $relatedOrder->reference }}
        </a>
    @elseif ($purchaseRequest->status === 'approved' && auth()->user()->hasPermissionTo('purchasing.manage'))
        <a href="{{ route('purchasing.orders.create', $purchaseRequest) }}" class="flex items-center justify-center gap-2 rounded-xl bg-primary p-3 text-center text-sm font-semibold text-white">
            <span class="material-symbols-outlined text-[18px]">add</span>
            Buat Purchase Order
        </a>
    @endif
```

---

### 3. request-create.blade.php — Minor Consistency Updates

**WHAT:** Ensure page header, back link, and error banner match desktop pattern from index.blade.php exactly.

**CHANGES:**

The page is currently minified. Need to make these conceptual updates (the minified code is equivalent):

#### A. Desktop section header must include page description like index.blade.php:

The existing structure `<h1>` + `<p>` is already correct. Verify heading uses `font-poppins text-2xl font-semibold` (already present).

#### B. Error banner styling — ensure it matches index.blade.php:

Current: `rounded-xl bg-red-50 p-4 text-sm text-red-700`
This matches index.blade.php green banner: `rounded-xl bg-green-50 p-4 text-sm text-green-800`
Status: ✓ Already correct

#### C. Back link color:

Current: `text-sm font-semibold text-[#DC143C]`
Status: ✓ Already correct

---

### 4. purchasing/index.blade.php — Verification Only

**WHAT:** No changes needed. This file is already the design reference and fully consistent.

**STATUS:** ✓ Reference complete, already correct

---

## Verification Steps

After each file change:

1. **request-show.blade.php desktop section:**
   - View http://127.0.0.1:8000/purchasing/requests/1 in desktop browser (1440px+)
   - Verify: 4 stat cards appear in first row (Status, Pemohon, Tanggal Dibutuhkan, Jumlah Produk)
   - Verify: Each card has colored icon in top-right container, uppercase label, bold value, subtitle
   - Verify: Items appear in 3-column grid below (or 2-column on mid-width)
   - Verify: Each item card has package icon, product name, SKU, and quantity badge
   - Verify: Action buttons (if present) have icon + text, proper spacing
   - Check page responsiveness: shrink to tablet (768px), verify grid collapses to 2 columns for stats, 1 column for items

2. **request-show.blade.php mobile section:**
   - View http://127.0.0.1:8000/purchasing/requests/1 on mobile (375px) or mobile emulator
   - Verify: 2-column mini stat grid appears (Pemohon + Tanggal Dibutuhkan)
   - Verify: Items appear as list cards with icon, name, SKU, quantity badge
   - Verify: Buttons have icons and proper touch-friendly sizing

3. **request-create.blade.php:**
   - View http://127.0.0.1:8000/purchasing/requests/create in desktop
   - Verify: Page header, description, back link match index.blade.php style
   - Verify: Error banner (if triggered by form errors) has correct styling
   - Check mobile view (375px): back link, heading, error banner all readable

4. **Full page consistency check:**
   - Compare request-show.blade.php with purchasing/index.blade.php side-by-side
   - Verify: Colors, typography, card styles, button styles, icon usage all match
   - Verify: Mobile and desktop breakpoints are consistent

---

## Design Decisions

**Why 4 stat cards instead of flat definition list:**
- Matches KPI card pattern already established in purchasing/index.blade.php and dashboard.blade.php
- Provides visual hierarchy and quicker scanning of key information
- Icons add visual context (status icon, person icon, calendar icon, package icon)
- Responsive: reduces to 2 columns on tablet, maintaining readability

**Why grid layout for items instead of divide-y table:**
- Consistent with dashboard product cards and mobile list patterns
- Better for responsive design (reflows naturally on all screen sizes)
- Each item is a complete visual unit, reducing cognitive load
- Icon + SKU + quantity together in card reduces vertical scanning

**Why 2-column stat cards on mobile:**
- Matches mobile grid pattern from index.blade.php (2-column KPI grid)
- Fits 375px screen width without truncation
- Shows essentials (requester, date) in compact form

**Button styling with icons:**
- Matches primary button style from index.blade.php (`bg-[#DC143C]` + icon)
- Provides visual affordance (user sees action icon + label)
- Consistent icon use across purchasing module

