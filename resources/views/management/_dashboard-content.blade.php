@php
    $reportLink = fn (string $type) => route('reports.index', array_merge(['report' => $type], $reportFilters));
    $currency = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $number = fn ($value) => number_format((int) $value, 0, ',', '.');
    $coveragePct = $stockPairCount > 0 ? round(($stockedPairs / $stockPairCount) * 100) : 0;
    $totalTrendIn = (int) $trend->sum('stock_in');
    $totalTrendOut = (int) $trend->sum('stock_out');
    $avgDailyMovements = round(($totalTrendIn + $totalTrendOut) / max(1, $periodDays), 1);

    // Coordinate re-scaling for high-fidelity, non-squished rendering
    $baselineY = 240;
    $topY = 45;
    $chartHeight = $baselineY - $topY; // 195px vertical range
    $chartWidth = 670; // from x=45 to x=715 in a 760-wide viewBox
    $pointCount = max(1, $trend->count() - 1);

    $chartPoints = [];
    foreach ($trend as $idx => $p) {
        $x = round(45 + (($idx / $pointCount) * $chartWidth), 1);
        $inY = round($baselineY - (($p['stock_in'] / max(1, $trendMax)) * $chartHeight), 1);
        $outY = round($baselineY - (($p['stock_out'] / max(1, $trendMax)) * $chartHeight), 1);
        $fullDate = \Illuminate\Support\Carbon::parse($p['date'])->locale('id')->translatedFormat('l, d M Y');

        $chartPoints[] = [
            'x' => $x,
            'in_x' => $x - (($p['stock_in'] > 0 && $p['stock_out'] > 0 && abs($inY - $outY) < 0.1) ? 3 : 0),
            'out_x' => $x + (($p['stock_in'] > 0 && $p['stock_out'] > 0 && abs($inY - $outY) < 0.1) ? 3 : 0),
            'in_y' => $inY,
            'out_y' => $outY,
            'date' => $p['date'],
            'full_date' => $fullDate,
            'label' => $p['label'],
            'stock_in' => (int) $p['stock_in'],
            'stock_out' => (int) $p['stock_out'],
        ];
    }

    // Smooth Bezier Curve generator (Catmull-Rom spline)
    $buildSmoothPath = function (array $points, string $yKey, float $baseline = 240) {
        $n = count($points);
        if ($n === 0) return '';
        if ($n === 1) return "M {$points[0]['x']},{$points[0][$yKey]}";

        $d = "M {$points[0]['x']},{$points[0][$yKey]}";
        for ($i = 0; $i < $n - 1; $i++) {
            $p0 = $points[max(0, $i - 1)];
            $p1 = $points[$i];
            $p2 = $points[$i + 1];
            $p3 = $points[min($n - 1, $i + 2)];

            $cp1x = round($p1['x'] + ($p2['x'] - $p0['x']) / 6, 1);
            $cp1y = round($p1[$yKey] + ($p2[$yKey] - $p0[$yKey]) / 6, 1);
            $cp2x = round($p2['x'] - ($p3['x'] - $p1['x']) / 6, 1);
            $cp2y = round($p2[$yKey] - ($p3[$yKey] - $p1[$yKey]) / 6, 1);

            // Clamp control points to baseline so curves never dip under 0
            $cp1y = min($baseline, $cp1y);
            $cp2y = min($baseline, $cp2y);

            $d .= " C {$cp1x},{$cp1y} {$cp2x},{$cp2y} {$p2['x']},{$p2[$yKey]}";
        }
        return $d;
    };

    $smoothInPath = $buildSmoothPath($chartPoints, 'in_y', $baselineY);
    $smoothOutPath = $buildSmoothPath($chartPoints, 'out_y', $baselineY);

    $firstX = $chartPoints[0]['x'] ?? 45;
    $lastX = $chartPoints[count($chartPoints) - 1]['x'] ?? 715;

    $areaInPath = $smoothInPath ? "{$smoothInPath} L {$lastX},{$baselineY} L {$firstX},{$baselineY} Z" : '';
    $areaOutPath = $smoothOutPath ? "{$smoothOutPath} L {$lastX},{$baselineY} L {$firstX},{$baselineY} Z" : '';

    // Generate balanced Y-axis ticks
    if ($trendMax <= 4) {
        $yTicks = [
            [45, 4],
            [94, 3],
            [143, 2],
            [191, 1],
            [240, 0],
        ];
    } else {
        $yTicks = [
            [45, $trendMax],
            [94, (int) round($trendMax * 0.75)],
            [143, (int) round($trendMax * 0.50)],
            [191, (int) round($trendMax * 0.25)],
            [240, 0],
        ];
    }

    $labelStep = max(1, (int) ceil(count($chartPoints) / 7));
@endphp

@once
<style>
    .mgmt-card {
        background-color: #ffffff;
        border: 1px solid rgba(226, 232, 240, 0.9);
        border-radius: 1.25rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04), 0 1px 2px -1px rgba(0, 0, 0, 0.02);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .mgmt-card:hover {
        border-color: rgba(203, 213, 225, 1);
        box-shadow: 0 4px 16px -2px rgba(0, 0, 0, 0.06), 0 2px 6px -2px rgba(0, 0, 0, 0.03);
    }
    .mgmt-ring-circle {
        transition: opacity 0.2s ease, filter 0.2s ease;
        outline: none;
    }
    .mgmt-ring-circle:hover,
    .mgmt-ring-circle:focus {
        opacity: 0.85;
        filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.15));
    }
    .mgmt-scrubber { transition: opacity 100ms ease; }
    .mgmt-scrubber-dot-in, .mgmt-scrubber-dot-out { transition: cx 70ms linear, cy 70ms linear, opacity 100ms ease; }
    .mgmt-tooltip-series { transition: opacity 120ms ease; }
    .mgmt-tooltip-series:not(.is-active) { opacity: 0.5; }
    @media (prefers-reduced-motion: reduce) {
        .mgmt-card, .mgmt-ring-circle, .mgmt-scrubber, .mgmt-scrubber-dot-in, .mgmt-scrubber-dot-out, .mgmt-tooltip-series { transition: none !important; }
    }
</style>
@endonce

<main class="{{ $mobile ? 'w-full space-y-5 p-4' : 'mx-auto w-full max-w-7xl space-y-6 px-4 sm:px-6 xl:px-8 pb-12' }}">

    {{-- ================================================================ --}}
    {{-- 1. EXECUTIVE HEADER                                              --}}
    {{-- ================================================================ --}}
    <header class="flex flex-col gap-4 border-b border-slate-200/80 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-slate-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Portal Eksekutif
                </span>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-xs font-medium text-slate-500">Ringkasan Persediaan</span>
            </div>
            <h1 class="mt-2 font-poppins text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                Dashboard Manajemen
            </h1>
            <p class="mt-1.5 text-sm text-slate-600 leading-relaxed">
                Posisi stok, valuasi aset, arus logistik, dan ringkasan persediaan eksekutif.
                <span class="inline-block font-medium text-slate-500">
                    ({{ $number($stockPairCount) }} produk aktif &middot; {{ $number($locations->count()) }} lokasi
                    @if($stockedPairs === 0)
                        &middot; Saldo stok belum dicatat
                    @endif)
                </span>
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 sm:justify-end">
            <div class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-medium text-slate-600 shadow-2xs">
                <span class="material-symbols-outlined text-[16px] text-slate-400">calendar_today</span>
                <span>{{ \Illuminate\Support\Carbon::parse($dateFrom)->locale('id')->translatedFormat('d M Y') }} &ndash; {{ \Illuminate\Support\Carbon::parse($dateTo)->locale('id')->translatedFormat('d M Y') }}</span>
            </div>
            <a href="{{ $reportLink('stock') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined text-[17px]">analytics</span>
                Semua Laporan
            </a>
        </div>
    </header>

    {{-- ================================================================ --}}
    {{-- 3. EXECUTIVE KPI CARDS                                           --}}
    {{-- ================================================================ --}}
    <section aria-label="Indikator utama" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Card 1: Inventory Valuation --}}
        <a href="{{ route('finance.valuation', array_filter(['location_id' => $selectedLocationId])) }}" class="mgmt-card p-5 group flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Nilai Persediaan</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-[#DC143C] group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[20px]">account_balance_wallet</span>
                    </div>
                </div>
                <p class="mt-2 font-poppins text-xl sm:text-2xl font-bold tracking-tight text-slate-900 truncate">
                    {{ $currency($valueTotal) }}
                </p>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-[11px]">
                <span class="text-slate-500">Metode WAC Operasional</span>
                <span class="inline-flex items-center gap-0.5 font-semibold text-[#DC143C] group-hover:translate-x-0.5 transition-transform">
                    Valuasi &rarr;
                </span>
            </div>
        </a>

        {{-- Card 2: Stock Availability --}}
        <a href="{{ $reportLink('stock') }}" class="mgmt-card p-5 group flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Produk Berstok Aktif</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[20px]">inventory_2</span>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="font-poppins text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                        {{ $number($stockedPairs) }}
                    </span>
                    <span class="text-xs text-slate-400 font-medium">/ {{ $number($stockPairCount) }} SKU-Lokasi</span>
                </div>
            </div>
            <div class="mt-4 border-t border-slate-100 pt-3">
                <div class="flex items-center justify-between text-[11px] text-slate-500 mb-1.5">
                    <span>Cakupan ketersediaan</span>
                    <span class="font-semibold text-slate-700">{{ $coveragePct }}%</span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-500" style="width: {{ $coveragePct }}%"></div>
                </div>
            </div>
        </a>

        {{-- Card 3: Minimum Stock Alerts --}}
        <a href="{{ $reportLink('stock') }}" class="mgmt-card p-5 group flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Alert Stok Minimum</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl {{ $lowStockCount ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-500' }} group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[20px]">notifications_active</span>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="font-poppins text-xl sm:text-2xl font-bold tracking-tight {{ $lowStockCount ? 'text-amber-700' : 'text-slate-900' }}">
                        {{ $number($lowStockCount) }}
                    </span>
                    <span class="text-xs text-slate-400 font-medium">peringatan aktif</span>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-[11px]">
                @if($lowStockCount > 0)
                    <span class="inline-flex items-center gap-1 font-semibold text-amber-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Perlu restock pengadaan
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 font-medium text-emerald-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        Semua stok di atas batas minimum
                    </span>
                @endif
                <span class="font-semibold text-slate-400 group-hover:text-slate-700 group-hover:translate-x-0.5 transition-all">&rarr;</span>
            </div>
        </a>

        {{-- Card 4: Logistics & Open POs --}}
        <a href="{{ $reportLink('purchasing') }}" class="mgmt-card p-5 group flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">PO & Distribusi Berjalan</span>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 group-hover:scale-105 transition-transform">
                        <span class="material-symbols-outlined text-[20px]">local_shipping</span>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="font-poppins text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                        {{ $number($openPurchaseOrders) }}
                    </span>
                    <span class="text-xs text-slate-400 font-medium">PO open</span>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                <span>{{ $number($inTransitTransfers) }} transfer &middot; {{ $number($openDiscrepancies) }} selisih</span>
                <span class="font-semibold text-blue-600 group-hover:translate-x-0.5 transition-transform">&rarr;</span>
            </div>
        </a>
    </section>

    {{-- ================================================================ --}}
    {{-- 4. CHART & VALUE QUALITY SECTION                                 --}}
    {{-- ================================================================ --}}
    <section class="space-y-4">
        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-800">Periode aktivitas</p>
                <p class="mt-0.5 text-[11px] text-slate-500">Pilih rentang data pada grafik</p>
            </div>
            <div class="flex w-fit items-center gap-1 rounded-xl bg-slate-100 p-1">
                @foreach([7 => '7 Hari', 30 => '30 Hari', 90 => '90 Hari'] as $days => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="period" value="{{ $days }}" onchange="this.form.submit()" class="sr-only" @checked($periodDays === $days)>
                        <span class="inline-block rounded-lg px-3 py-1.5 text-xs font-semibold transition-all {{ $periodDays === $days ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-600 hover:text-slate-900' }}">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </form>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.75fr)_minmax(310px,0.85fr)]">

        {{-- ==================== REDESIGNED PROPORTIONAL ACTIVITY CHART ==================== --}}
        <article class="mgmt-card p-5 sm:p-6 flex flex-col justify-between">
            <div>
                {{-- Header & Dynamic Legend Pills --}}
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                <span class="material-symbols-outlined text-[18px]">monitoring</span>
                            </span>
                            <h2 class="font-poppins text-base font-semibold text-slate-900">Arus Pergerakan Barang</h2>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Volume baris mutasi stock in vs stock out per hari &middot; {{ $periodDays }} hari terakhir</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-emerald-800 font-semibold shadow-2xs">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Masuk: {{ $number($totalTrendIn) }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-rose-800 font-semibold shadow-2xs">
                            <span class="h-2 w-2 rounded-full bg-[#DC143C]"></span>
                            Keluar: {{ $number($totalTrendOut) }}
                        </span>
                    </div>
                </div>

                {{-- Insight chips strip --}}
                <div class="mt-4 flex flex-wrap items-center gap-2 border-y border-slate-100 py-2.5 text-[11px] text-slate-600">
                    <span class="inline-flex items-center gap-1 text-slate-500">
                        <span class="material-symbols-outlined text-[15px] text-slate-400">speed</span>
                        Rata-rata: <strong class="font-semibold text-slate-800">{{ $avgDailyMovements }} baris/hari</strong>
                    </span>
                    <span class="text-slate-300">&bull;</span>
                    <span class="inline-flex items-center gap-1 text-slate-500">
                        <span class="material-symbols-outlined text-[15px] text-slate-400">insights</span>
                        Puncak Volume: <strong class="font-semibold text-slate-800">{{ $trendMax }} baris/hari</strong>
                    </span>
                    <span class="text-slate-300">&bull;</span>
                    <span class="text-slate-400">Arahkan kursor ke grafik untuk rincian tanggal realtime</span>
                </div>

                {{-- Interactive Chart Container --}}
                <div class="management-activity-chart relative mt-4 w-full select-none" data-points='@json($chartPoints)'>

                    {{-- Floating Glassmorphic Tooltip --}}
                    <div class="mgmt-chart-tooltip pointer-events-none absolute z-30 hidden -translate-x-1/2 rounded-xl border border-slate-700/90 bg-slate-900/95 px-3.5 py-2.5 text-xs text-white shadow-2xl backdrop-blur-md transition-[opacity,transform] duration-75 ease-out" style="top: 8px; left: 50%; opacity: 0;">
                        <div class="mb-1.5 flex items-center justify-between gap-4 border-b border-slate-700/60 pb-1.5 text-[11px] font-medium text-slate-300">
                            <span class="mgmt-tooltip-date font-semibold text-white">—</span>
                            <span class="mgmt-tooltip-badge rounded-md bg-slate-800 px-1.5 py-0.5 text-[10px] text-slate-300 font-mono">Movement</span>
                        </div>
                        <div class="space-y-1 text-[11px]">
                            <div class="mgmt-tooltip-series mgmt-tooltip-series-in flex items-center justify-between gap-6">
                                <span class="inline-flex items-center gap-1.5 text-emerald-400 font-medium">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                    Barang Masuk:
                                </span>
                                <strong class="mgmt-tooltip-in font-bold text-white">0</strong>
                            </div>
                            <div class="mgmt-tooltip-series mgmt-tooltip-series-out flex items-center justify-between gap-6">
                                <span class="inline-flex items-center gap-1.5 text-rose-400 font-medium">
                                    <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                                    Barang Keluar:
                                </span>
                                <strong class="mgmt-tooltip-out font-bold text-white">0</strong>
                            </div>
                            <div class="flex items-center justify-between gap-6 border-t border-slate-800/80 pt-1 text-[10px] text-slate-400">
                                <span>Total Aktivitas:</span>
                                <span class="mgmt-tooltip-total font-semibold text-slate-200">0 baris</span>
                            </div>
                        </div>
                    </div>

                    {{-- Beautiful Tall SVG (viewBox 0 0 760 280) --}}
                    <svg viewBox="0 0 760 280" class="h-72 sm:h-80 md:h-88 w-full overflow-visible cursor-crosshair" preserveAspectRatio="none">
                        <defs>
                            {{-- Emerald Area Gradient --}}
                            <linearGradient id="mgmtAreaIn" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#059669" stop-opacity="0.32" />
                                <stop offset="60%" stop-color="#059669" stop-opacity="0.08" />
                                <stop offset="100%" stop-color="#059669" stop-opacity="0.00" />
                            </linearGradient>

                            {{-- Crimson Area Gradient --}}
                            <linearGradient id="mgmtAreaOut" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#DC143C" stop-opacity="0.25" />
                                <stop offset="60%" stop-color="#DC143C" stop-opacity="0.06" />
                                <stop offset="100%" stop-color="#DC143C" stop-opacity="0.00" />
                            </linearGradient>

                            {{-- Ambient Floor Glow --}}
                            <linearGradient id="mgmtFloorGlow" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#F8FAFC" stop-opacity="0.5" />
                                <stop offset="100%" stop-color="#F1F5F9" stop-opacity="1" />
                            </linearGradient>
                        </defs>

                        {{-- Subtle background grid backdrop --}}
                        <rect x="45" y="45" width="670" height="195" fill="url(#mgmtFloorGlow)" rx="6" opacity="0.4" />

                        {{-- Horizontal Gridlines & Y-axis labels --}}
                        @foreach($yTicks as [$y, $val])
                            <line x1="45" y1="{{ $y }}" x2="715" y2="{{ $y }}" stroke="#E2E8F0" stroke-width="1.2" stroke-dasharray="3 4" />
                            <text x="36" y="{{ $y + 3 }}" text-anchor="end" fill="#94A3B8" font-size="10" font-family="Inter, sans-serif" font-weight="600">{{ $val }}</text>
                        @endforeach

                        {{-- Smooth Area Fills under curves --}}
                        @if($areaInPath)
                            <path d="{{ $areaInPath }}" fill="url(#mgmtAreaIn)" pointer-events="none" />
                        @endif
                        @if($areaOutPath)
                            <path d="{{ $areaOutPath }}" fill="url(#mgmtAreaOut)" pointer-events="none" />
                        @endif

                        {{-- Baseline Solid Axis --}}
                        <line x1="45" y1="{{ $baselineY }}" x2="715" y2="{{ $baselineY }}" stroke="#CBD5E1" stroke-width="1.5" />

                        {{-- Smooth Spline Curves (No sharp needle corners!) --}}
                        @if($smoothInPath)
                            <path d="{{ $smoothInPath }}" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                        @endif
                        @if($smoothOutPath)
                            <path d="{{ $smoothOutPath }}" fill="none" stroke="#DC143C" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                        @endif

                        {{-- Active Day Glow Nodes on curves --}}
                        @foreach($chartPoints as $pt)
                            @if($pt['stock_in'] > 0)
                                <circle cx="{{ $pt['in_x'] }}" cy="{{ $pt['in_y'] }}" r="4.5" fill="#059669" stroke="#ffffff" stroke-width="2.5" />
                            @endif
                            @if($pt['stock_out'] > 0)
                                <circle cx="{{ $pt['out_x'] }}" cy="{{ $pt['out_y'] }}" r="4.5" fill="#DC143C" stroke="#ffffff" stroke-width="2.5" />
                            @endif
                        @endforeach

                        {{-- Interactive Scrubber Line (Tracks cursor smoothly) --}}
                        <line class="mgmt-scrubber" x1="0" y1="45" x2="0" y2="{{ $baselineY }}" stroke="#64748B" stroke-width="1.5" stroke-dasharray="3 3" opacity="0" pointer-events="none" />
                        <circle class="mgmt-scrubber-dot-in" r="5.5" fill="#059669" stroke="#ffffff" stroke-width="2.5" opacity="0" pointer-events="none" />
                        <circle class="mgmt-scrubber-dot-out" r="5.5" fill="#DC143C" stroke="#ffffff" stroke-width="2.5" opacity="0" pointer-events="none" />

                        {{-- X-Axis Date Labels --}}
                        @foreach($chartPoints as $idx => $pt)
                            @if($idx % $labelStep === 0 || $idx === count($chartPoints) - 1)
                                <line x1="{{ $pt['x'] }}" y1="{{ $baselineY }}" x2="{{ $pt['x'] }}" y2="{{ $baselineY + 5 }}" stroke="#94A3B8" stroke-width="1.5" />
                                <text x="{{ $pt['x'] }}" y="{{ $baselineY + 22 }}" text-anchor="middle" fill="#64748B" font-size="10" font-family="Inter, sans-serif" font-weight="600">{{ $pt['label'] }}</text>
                            @endif
                        @endforeach
                    </svg>
                </div>
            </div>

            {{-- Footer Note --}}
            <div class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                <span>Grafik mengukur baris mutasi untuk mencegah distorsi konversi satuan yang berbeda.</span>
                <a class="inline-flex items-center gap-1 font-semibold text-[#DC143C] hover:underline" href="{{ $reportLink('movements') }}">
                    Rincian Mutasi Lengkap <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
            </div>
        </article>

        {{-- ==================== DOUGHNUT / VALUE QUALITY ==================== --}}
        <article class="mgmt-card p-5 sm:p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                <span class="material-symbols-outlined text-[18px]">pie_chart</span>
                            </span>
                            <h2 class="font-poppins text-base font-semibold text-slate-900">Kelengkapan Nilai</h2>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Status penetapan harga dan biaya per SKU</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                        {{ $number($valueQualityTotal) }} SKU
                    </span>
                </div>

                {{-- Ring & Breakdown Layout --}}
                <div class="mt-6 flex flex-col sm:flex-row items-center gap-6">
                    {{-- Clean SVG Donut Chart --}}
                    <div class="relative h-32 w-32 shrink-0">
                        <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" role="img" aria-label="Diagram kelengkapan status valuasi persediaan">
                            <circle cx="60" cy="60" r="43" fill="none" stroke="#F1F5F9" stroke-width="14" />
                            @foreach($valueQuality as $segment)
                                <circle class="mgmt-ring-circle"
                                        tabindex="0"
                                        cx="60"
                                        cy="60"
                                        r="43"
                                        fill="none"
                                        stroke="{{ $segment['color'] }}"
                                        stroke-width="14"
                                        stroke-dasharray="{{ $segment['dash'] }}"
                                        stroke-dashoffset="{{ $segment['offset'] }}"
                                        aria-label="{{ $segment['label'] }}: {{ $segment['count'] }} SKU">
                                    <title>{{ $segment['label'] }}: {{ $segment['count'] }} SKU ({{ round(($segment['count'] / max(1, $valueQualityTotal)) * 100) }}%)</title>
                                </circle>
                            @endforeach
                        </svg>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span class="font-poppins text-xl font-bold tracking-tight text-slate-900">{{ $number($valueQualityTotal) }}</span>
                            <span class="text-[9px] font-semibold uppercase tracking-wider text-slate-400">SKU-Lokasi</span>
                        </div>
                    </div>

                    {{-- Legend & Detailed Breakdown --}}
                    <div class="min-w-0 flex-1 space-y-2.5 w-full">
                        @foreach($valueQuality as $segment)
                            @php($pct = round(($segment['count'] / max(1, $valueQualityTotal)) * 100))
                            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-2.5 flex items-center justify-between gap-3">
                                <div class="min-w-0 flex items-center gap-2">
                                    <i class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $segment['color'] }};"></i>
                                    <div>
                                        <p class="text-xs font-semibold text-slate-800 leading-tight">{{ $segment['label'] }}</p>
                                        <p class="text-[10px] text-slate-400">
                                            @if($segment['key'] === 'confirmed')
                                                Harga terverifikasi faktur & PO
                                            @elseif($segment['key'] === 'provisional')
                                                Estimasi sebelum approval final
                                            @else
                                                Belum tertaut dokumen biaya
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <strong class="text-xs font-bold text-slate-900">{{ $number($segment['count']) }}</strong>
                                    <span class="block text-[10px] font-medium text-slate-500">{{ $pct }}%</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Quality Context & Action --}}
            <div class="mt-5 border-t border-slate-100 pt-3">
                <p class="text-[11px] leading-relaxed text-slate-500">
                    Nilai sementara atau belum lengkap perlu diverifikasi dengan dokumen faktur pembelian sebelum dicatat sebagai aset buku final.
                </p>
                <a href="{{ route('finance.valuation', array_filter(['location_id' => $selectedLocationId])) }}" class="mt-2.5 inline-flex items-center gap-1 text-xs font-semibold text-[#DC143C] hover:underline">
                    Buka Valuasi Persediaan WAC <span class="material-symbols-outlined text-[15px]">arrow_forward</span>
                </a>
            </div>
        </article>
        </div>
    </section>

    {{-- ================================================================ --}}
    {{-- 5. BOTTOM GRID: TOP VALUED INVENTORY & RISK RADAR                --}}
    {{-- ================================================================ --}}
    <section class="grid gap-6 xl:grid-cols-2">

        {{-- ==================== TOP VALUED INVENTORY ==================== --}}
        <article class="mgmt-card p-5 sm:p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                <span class="material-symbols-outlined text-[18px]">star</span>
                            </span>
                            <h2 class="font-poppins text-base font-semibold text-slate-900">Nilai Persediaan Terbesar</h2>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Lima produk-lokasi dengan nilai terhitung tertinggi</p>
                    </div>
                    <a href="{{ route('finance.valuation', array_filter(['location_id' => $selectedLocationId])) }}" class="text-xs font-semibold text-[#DC143C] hover:underline">
                        Lihat Semua &rarr;
                    </a>
                </div>

                <div class="mt-5 space-y-3.5">
                    @forelse($topValueRows as $index => $row)
                        <div class="group rounded-xl border border-slate-100 bg-slate-50/50 p-3 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-white border border-slate-200 text-[10px] font-bold text-slate-600 mt-0.5">
                                        0{{ $index + 1 }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-xs text-slate-900">{{ $row['product_name'] }}</p>
                                        <p class="mt-0.5 truncate text-[11px] text-slate-500">
                                            {{ $row['location_name'] }} &middot;
                                            <span class="font-medium text-slate-700">{{ \App\Support\QuantityFormatter::format($row['quantity']) }} {{ $row['unit_symbol'] }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-poppins text-xs font-bold text-slate-900">{{ $currency($row['value']) }}</p>
                                    <span class="inline-block mt-0.5 rounded-full px-2 py-0.5 text-[9px] font-semibold {{ $row['valuation_status'] === 'confirmed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200/60' : ($row['valuation_status'] === 'provisional' ? 'bg-amber-50 text-amber-700 border border-amber-200/60' : 'bg-rose-50 text-rose-700 border border-rose-200/60') }}">
                                        {{ ucfirst($row['valuation_status']) }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-200/80">
                                <div class="h-full rounded-full bg-gradient-to-r from-[#DC143C] to-rose-500 transition-all duration-300" style="width: {{ $row['bar_percent'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-200 py-10 text-center text-xs text-slate-500">
                            <span class="material-symbols-outlined text-[32px] text-slate-300 mb-1">inventory</span>
                            <p>Belum ada nilai biaya terhitung yang dapat ditampilkan.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="mt-4 border-t border-slate-100 pt-3 text-[11px] text-slate-400">
                Diurutkan berdasarkan total nominal valuasi WAC pada snapshot aktif.
            </div>
        </article>

        {{-- ==================== RADAR RISIKO & PERHATIAN ==================== --}}
        <article class="mgmt-card p-5 sm:p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                                <span class="material-symbols-outlined text-[18px]">crisis_alert</span>
                            </span>
                            <h2 class="font-poppins text-base font-semibold text-slate-900">Perlu Perhatian</h2>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Alert aktif, masa simpan/kedaluwarsa, dan discrepancy transfer</p>
                    </div>
                    <a href="{{ $reportLink('expiry') }}" class="text-xs font-semibold text-[#DC143C] hover:underline">
                        Laporan Risiko &rarr;
                    </a>
                </div>

                {{-- 3 Key Risk Metric Strips --}}
                <div class="mt-4 grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-2 border border-slate-100">
                    <div class="rounded-lg bg-white p-2.5 text-center shadow-2xs border border-slate-100">
                        <span class="block text-[10px] font-medium text-slate-500 uppercase tracking-wide">Stok Kritis</span>
                        <strong class="mt-0.5 block font-poppins text-base font-bold {{ $lowStockCount ? 'text-amber-600' : 'text-slate-800' }}">
                            {{ $number($lowStockCount) }}
                        </strong>
                    </div>
                    <div class="rounded-lg bg-white p-2.5 text-center shadow-2xs border border-slate-100">
                        <span class="block text-[10px] font-medium text-slate-500 uppercase tracking-wide">Expired / Dekat</span>
                        <strong class="mt-0.5 block font-poppins text-base font-bold {{ ($expiredBatchCount + $nearExpiryBatchCount) ? 'text-rose-600' : 'text-slate-800' }}">
                            {{ $number($expiredBatchCount) }} / {{ $number($nearExpiryBatchCount) }}
                        </strong>
                    </div>
                    <div class="rounded-lg bg-white p-2.5 text-center shadow-2xs border border-slate-100">
                        <span class="block text-[10px] font-medium text-slate-500 uppercase tracking-wide">Discrepancy</span>
                        <strong class="mt-0.5 block font-poppins text-base font-bold {{ $openDiscrepancies ? 'text-rose-700' : 'text-slate-800' }}">
                            {{ $number($openDiscrepancies) }}
                        </strong>
                    </div>
                </div>

                {{-- Feed of Risk Items --}}
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach($lowStockAlerts->take(3) as $alert)
                        <a href="{{ $reportLink('stock') }}" class="flex items-center gap-3 py-3 group hover:bg-slate-50/60 -mx-2 px-2 rounded-lg transition-colors">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 border border-amber-200/60">
                                <span class="material-symbols-outlined text-[15px]">warning</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <strong class="block truncate text-xs font-semibold text-slate-800 group-hover:text-[#DC143C] transition-colors">
                                    {{ $alert->product?->name ?? 'Produk' }}
                                </strong>
                                <span class="block truncate text-[11px] text-slate-500">
                                    {{ $alert->location?->name ?? 'Lokasi' }} &middot; Sisa stok: <strong class="text-amber-700 font-medium">{{ \App\Support\QuantityFormatter::format($alert->stock_quantity) }} {{ $alert->product?->unit?->symbol }}</strong>
                                </span>
                            </span>
                            <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-slate-700 group-hover:translate-x-0.5 transition-all">chevron_right</span>
                        </a>
                    @endforeach

                    @foreach($expiryRisks->take(3) as $risk)
                        <a href="{{ $reportLink('expiry') }}" class="flex items-center gap-3 py-3 group hover:bg-slate-50/60 -mx-2 px-2 rounded-lg transition-colors">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $risk->status === 'Kedaluwarsa' ? 'bg-rose-50 text-rose-600 border border-rose-200/60' : 'bg-orange-50 text-orange-600 border border-orange-200/60' }}">
                                <span class="material-symbols-outlined text-[15px]">timer</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <strong class="block truncate text-xs font-semibold text-slate-800 group-hover:text-[#DC143C] transition-colors">
                                    {{ $risk->product }}
                                </strong>
                                <span class="block truncate text-[11px] text-slate-500">
                                    {{ $risk->location }} &middot; <span class="{{ $risk->status === 'Kedaluwarsa' ? 'text-rose-600 font-semibold' : 'text-orange-600 font-medium' }}">{{ $risk->status }}</span> &middot; {{ $risk->expiry ? \Illuminate\Support\Carbon::parse($risk->expiry)->format('d M Y') : '' }}
                                </span>
                            </span>
                            <span class="material-symbols-outlined text-[16px] text-slate-400 group-hover:text-slate-700 group-hover:translate-x-0.5 transition-all">chevron_right</span>
                        </a>
                    @endforeach

                    @if($lowStockCount === 0 && $expiryRisks->isEmpty() && $openDiscrepancies === 0)
                        <div class="py-8 text-center text-xs text-slate-500">
                            <span class="material-symbols-outlined text-[32px] text-emerald-500 mb-1">verified</span>
                            <p class="font-semibold text-slate-700">Semua Operasional Terkendali</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">Tidak ada alert stok, risiko kedaluwarsa, atau selisih distribusi pada periode ini.</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 border-t border-slate-100 pt-3 text-[11px] text-slate-400">
                Tindak lanjuti peringatan kritis melalui koordinasi dengan tim gudang & pengadaan.
            </div>
        </article>
    </section>

    {{-- ================================================================ --}}
    {{-- 6. EXECUTIVE FOOTER & SHORTCUTS                                  --}}
    {{-- ================================================================ --}}
    <footer class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-slate-200/80 pt-4 text-xs text-slate-500">
        <p>Snapshot stok per produk dan lokasi aktif. Nilai aset diperbarui realtime saat transaksi diposting.</p>
        <div class="flex flex-wrap items-center gap-4">
            <a href="{{ $reportLink('opname') }}" class="font-medium text-slate-600 hover:text-slate-900 transition-colors">Hasil Opname</a>
            <a href="{{ $reportLink('purchasing') }}" class="font-medium text-slate-600 hover:text-slate-900 transition-colors">Pengadaan</a>
            <a href="{{ $reportLink('movements') }}" class="font-medium text-slate-600 hover:text-slate-900 transition-colors">Riwayat Mutasi</a>
            <a href="{{ $reportLink('expiry') }}" class="font-medium text-slate-600 hover:text-slate-900 transition-colors">Laporan Kedaluwarsa</a>
        </div>
    </footer>
</main>

{{-- ================================================================ --}}
{{-- 7. ULTRA-RELIABLE MOUSE / TOUCH CHART INTERACTION SCRIPT         --}}
{{-- ================================================================ --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Handle both mobile and desktop instances independently
    document.querySelectorAll('.management-activity-chart').forEach(function (container) {
        const svg = container.querySelector('svg');
        const tooltip = container.querySelector('.mgmt-chart-tooltip');
        const scrubber = container.querySelector('.mgmt-scrubber');
        const dotIn = container.querySelector('.mgmt-scrubber-dot-in');
        const dotOut = container.querySelector('.mgmt-scrubber-dot-out');

        const dateEl = container.querySelector('.mgmt-tooltip-date');
        const inEl = container.querySelector('.mgmt-tooltip-in');
        const outEl = container.querySelector('.mgmt-tooltip-out');
        const totalEl = container.querySelector('.mgmt-tooltip-total');
        const badgeEl = container.querySelector('.mgmt-tooltip-badge');
        const inRow = container.querySelector('.mgmt-tooltip-series-in');
        const outRow = container.querySelector('.mgmt-tooltip-series-out');

        let points = [];
        try {
            points = JSON.parse(container.getAttribute('data-points') || '[]');
        } catch (e) {
            return;
        }

        if (!points || !points.length || !svg || !tooltip) return;

        function handlePointer(clientX, clientY) {
            const rect = svg.getBoundingClientRect();
            if (rect.width <= 0) return;

            // Convert pointer position to the SVG viewBox coordinate system.
            const offsetX = clientX - rect.left;
            const offsetY = clientY - rect.top;
            const svgX = (offsetX / rect.width) * 760;
            const svgY = (offsetY / rect.height) * 280;

            // Pick the nearest visible marker, so red and green hovers report the matching series.
            let closest = null;
            let activeType = null;
            let minDist = Infinity;
            points.forEach(function (point) {
                ['in', 'out'].forEach(function (type) {
                    const count = type === 'in' ? point.stock_in : point.stock_out;
                    if (count <= 0) return;

                    const markerX = point[type + '_x'] ?? point.x;
                    const dx = (svgX - markerX) * (rect.width / 760);
                    const dy = (svgY - point[type + '_y']) * (rect.height / 280);
                    const distance = (dx * dx) + (dy * dy);
                    if (distance < minDist) {
                        minDist = distance;
                        closest = point;
                        activeType = type;
                    }
                });
            });

            // Empty days have no visible marker; still let the chart show their zero values.
            if (!closest) {
                closest = points.reduce(function (nearest, point) {
                    return Math.abs(svgX - point.x) < Math.abs(svgX - nearest.x) ? point : nearest;
                }, points[0]);
                activeType = Math.abs(svgY - closest.in_y) <= Math.abs(svgY - closest.out_y) ? 'in' : 'out';
            }

            if (scrubber) {
                scrubber.setAttribute('x1', closest.x);
                scrubber.setAttribute('x2', closest.x);
                scrubber.style.opacity = '1';
            }
            if (dotIn) {
                dotIn.setAttribute('cx', closest.in_x ?? closest.x);
                dotIn.setAttribute('cy', closest.in_y);
                dotIn.style.opacity = activeType === 'in' && closest.stock_in > 0 ? '1' : '0';
            }
            if (dotOut) {
                dotOut.setAttribute('cx', closest.out_x ?? closest.x);
                dotOut.setAttribute('cy', closest.out_y);
                dotOut.style.opacity = activeType === 'out' && closest.stock_out > 0 ? '1' : '0';
            }

            if (dateEl) dateEl.textContent = closest.full_date || closest.date;
            if (inEl) inEl.textContent = closest.stock_in + ' baris';
            if (outEl) outEl.textContent = closest.stock_out + ' baris';

            if (inRow) inRow.classList.toggle('is-active', activeType === 'in');
            if (outRow) outRow.classList.toggle('is-active', activeType === 'out');

            const total = (closest.stock_in || 0) + (closest.stock_out || 0);
            if (totalEl) totalEl.textContent = total + ' baris pergerakan';

            if (badgeEl) {
                badgeEl.textContent = activeType === 'in' ? 'BARANG MASUK' : 'BARANG KELUAR';
                badgeEl.className = 'mgmt-tooltip-badge rounded-md px-1.5 py-0.5 text-[10px] font-mono ' +
                    (activeType === 'in' ? 'bg-emerald-900/60 text-emerald-200' : 'bg-rose-900/60 text-rose-200');
            }

            // Keep tooltip inside the chart edges.
            const pct = Math.max(14, Math.min(86, (closest.x / 760) * 100));
            tooltip.style.left = pct + '%';
            tooltip.classList.remove('hidden');
            tooltip.style.opacity = '1';
        }

        function resetPointer() {
            if (scrubber) scrubber.style.opacity = '0';
            if (dotIn) dotIn.style.opacity = '0';
            if (dotOut) dotOut.style.opacity = '0';
            if (inRow) inRow.classList.remove('is-active');
            if (outRow) outRow.classList.remove('is-active');
            if (tooltip) {
                tooltip.style.opacity = '0';
                setTimeout(function () {
                    if (tooltip.style.opacity === '0') {
                        tooltip.classList.add('hidden');
                    }
                }, 100);
            }
        }

        // Read the nearest red/green marker on both pointer and touch devices.
        svg.addEventListener('mousemove', function (e) {
            handlePointer(e.clientX, e.clientY);
        });

        svg.addEventListener('mouseleave', resetPointer);

        svg.addEventListener('touchmove', function (e) {
            if (e.touches && e.touches[0]) {
                handlePointer(e.touches[0].clientX, e.touches[0].clientY);
            }
        }, { passive: true });

        svg.addEventListener('touchend', resetPointer);
    });
});
</script>
