@extends('layouts.app')

@section('title', 'Technolife - Inventory Management Dashboard')

@section('content')
<div class="min-h-screen flex flex-col bg-[#FBF9F8]">
    <!-- ========================================== -->
    <!-- 1. TOP HEADER NAVBAR (<header>)            -->
    <!-- ========================================== -->
    <header class="sticky top-0 z-40 w-full bg-white border-b border-[#EAEAEA] shadow-[0_1px_3px_rgba(0,0,0,0.03)] h-14">
        <div class="w-full h-full px-4 xl:px-6 flex items-center justify-between gap-3">
            
            <!-- Left: Logo & Warehouse Selector & Nav Tabs -->
            <div class="flex items-center gap-3.5 shrink-0">
                <a href="{{ url('/') }}" class="flex items-center text-lg font-bold font-poppins text-[#1B1C1C] tracking-tight hover:opacity-95 transition-opacity">
                    Technolife<span class="inline-block w-2 h-2 rounded-full bg-[#DC143C] ml-0.5"></span>
                </a>

                <div class="h-4 w-px bg-[#EAEAEA]"></div>

                <div class="relative">
                    <button type="button" id="warehouse-dropdown-btn" class="flex items-center gap-1.5 px-2.5 py-1 bg-[#FBF9F8] hover:bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg text-xs font-medium text-gray-700 transition-colors cursor-pointer shrink-0">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="truncate max-w-[120px]">Central Warehouse</span>
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>

                <div class="h-4 w-px bg-[#EAEAEA] hidden md:block"></div>

                <!-- Navigation Tabs (Matching Mobile: Dashboard, Data Stock, Riwayat, Laporan) -->
                <nav class="hidden md:flex items-center gap-1 shrink-0">
                    <a href="{{ url('/') }}" class="px-2.5 py-1 rounded-lg text-xs font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        Dashboard
                    </a>
                    <a href="{{ route('inventory.stock.index') }}" class="px-2.5 py-1 rounded-lg text-xs font-semibold text-[#DC143C] bg-[#FFF1F0] border border-[#DC143C]/10 transition-colors">
                        Data Stock
                    </a>
                    <a href="#riwayat" onclick="alert('Menu Riwayat Operasional'); return false;" class="px-2.5 py-1 rounded-lg text-xs font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        Riwayat
                    </a>
                    <a href="#laporan" onclick="alert('Menu Laporan Stok & Pergerakan'); return false;" class="px-2.5 py-1 rounded-lg text-xs font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                        Laporan
                    </a>
                </nav>
            </div>

            <!-- Center: Global Search Input -->
            <div class="flex-1 max-w-xs xl:max-w-sm mx-2 min-w-0">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        id="table-search-input" 
                        placeholder="Cari SKU, batch, supplier..." 
                        class="w-full pl-8 pr-10 py-1 h-8 bg-[#F5F5FA] border border-[#EAEAEA] rounded-lg text-xs text-gray-900 placeholder:text-gray-400 focus:bg-white focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all"
                    >
                    <span class="absolute inset-y-0 right-0 pr-2 flex items-center pointer-events-none text-[10px] text-gray-400 font-mono">⌘K</span>
                </div>
            </div>

            <!-- Right Actions: CTAs, Notifications, User Profile -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- Primary CTA -->
                <button type="button" onclick="openStockInModal()" class="inline-flex items-center gap-1.5 h-8 px-3 bg-[#DC143C] hover:bg-[#B1002C] text-white text-xs font-semibold rounded-lg shadow-xs transition-all active:scale-95 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Stock In</span>
                </button>

                <!-- Secondary CTA -->
                <button type="button" class="hidden sm:inline-flex items-center gap-1 h-8 px-2.5 bg-white hover:bg-[#FFF1F0] text-[#DC143C] border border-[#DC143C] text-xs font-semibold rounded-lg transition-all active:scale-95 cursor-pointer">
                    <span>Audit</span>
                </button>

                <!-- Notification Bell Icon -->
                <button type="button" class="relative w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors" title="3 Notifications">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-[#DC143C] rounded-full"></span>
                </button>

                <!-- Settings Button -->
                <button type="button" onclick="alert('Menu Pengaturan Sistem'); return false;" class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors" title="Settings">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>

                <div class="h-4 w-px bg-[#EAEAEA]"></div>

                <!-- User Profile -->
                <a href="{{ route('account') }}" class="flex items-center gap-1.5 pl-1 cursor-pointer text-inherit no-underline">
                    <span class="px-1 py-0.2 bg-[#DCFCE7] text-[#16A34A] text-[10px] font-bold rounded tracking-wider">ON</span>
                    <img 
                        src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80" 
                        alt="Operations Manager" 
                        class="w-7 h-7 rounded-full object-cover border border-gray-200"
                    >
                    <span class="text-xs font-semibold text-gray-800 hidden 2xl:inline">Operations Manager</span>
                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>
            </div>

        </div>
    </header>

    <!-- ========================================== -->
    <!-- MAIN WORKSPACE CONTENT (<main>)            -->
    <!-- ========================================== -->
    <main class="flex-1 w-full max-w-[1440px] mx-auto p-4 sm:p-6 xl:p-8 space-y-6 overflow-x-hidden min-w-0">
            
            <!-- ========================================== -->
            <!-- 3.1 TOP 4 METRIC KPI CARDS (<section>)     -->
            <!-- ========================================== -->
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 gap-4 xl:gap-5 min-w-0">
                
                <!-- Card 1: Total Active SKUs -->
                <div class="bg-white border border-[#EAEAEA] rounded-xl p-4 xl:p-4.5 2xl:p-5 shadow-[0_4px_12px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)] transition-shadow flex items-center justify-between gap-3 min-w-0">
                    <div class="flex items-center gap-3 xl:gap-3.5 min-w-0 flex-1">
                        <div class="w-10 h-10 xl:w-11 xl:h-11 rounded-xl bg-[#F5F5FA] flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 xl:w-5.5 xl:h-5.5 text-gray-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="block text-[10px] xl:text-[11px] font-semibold tracking-wider text-gray-500 uppercase truncate">TOTAL ACTIVE SKUs</span>
                            <div class="text-lg xl:text-xl 2xl:text-[22px] font-semibold font-poppins text-gray-900 leading-tight mt-0.5 whitespace-nowrap">1,248 Items</div>
                            <span class="text-xs text-gray-400 mt-0.5 block truncate">4 Storage Units</span>
                        </div>
                    </div>
                    <button type="button" class="w-6.5 h-6.5 xl:w-7 xl:h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:border-gray-300 transition-colors shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                <!-- Card 2: Low Stock Warning -->
                <div class="bg-white border border-[#EAEAEA] rounded-xl p-4 xl:p-4.5 2xl:p-5 shadow-[0_4px_12px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)] transition-shadow flex items-center justify-between gap-3 min-w-0">
                    <div class="flex items-center gap-3 xl:gap-3.5 min-w-0 flex-1">
                        <div class="w-10 h-10 xl:w-11 xl:h-11 rounded-xl bg-[#FFF1F0] flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 xl:w-5.5 xl:h-5.5 text-[#DC143C]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="block text-[10px] xl:text-[11px] font-semibold tracking-wider text-[#DC143C] uppercase truncate">LOW STOCK WARNING</span>
                            <div class="text-lg xl:text-xl 2xl:text-[22px] font-semibold font-poppins text-[#DC143C] leading-tight mt-0.5 whitespace-nowrap">12 SKUs</div>
                            <span class="text-xs text-gray-400 mt-0.5 block truncate">Needs restock soon</span>
                        </div>
                    </div>
                    <button type="button" class="w-6.5 h-6.5 xl:w-7 xl:h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:border-gray-300 transition-colors shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                <!-- Card 3: Expiring Soon (FEFO) -->
                <div class="bg-white border border-[#EAEAEA] rounded-xl p-4 xl:p-4.5 2xl:p-5 shadow-[0_4px_12px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)] transition-shadow flex items-center justify-between gap-3 min-w-0">
                    <div class="flex items-center gap-3 xl:gap-3.5 min-w-0 flex-1">
                        <div class="w-10 h-10 xl:w-11 xl:h-11 rounded-xl bg-[#FEF3C7] flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 xl:w-5.5 xl:h-5.5 text-[#F59E0B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="block text-[10px] xl:text-[11px] font-semibold tracking-wider text-[#F59E0B] uppercase truncate">EXPIRING SOON (FEFO)</span>
                            <div class="text-base sm:text-lg xl:text-lg 2xl:text-xl font-semibold font-poppins text-gray-900 leading-tight mt-0.5 whitespace-nowrap">8 Batches &lt; 3 Days</div>
                            <span class="text-xs text-gray-400 mt-0.5 block truncate">Prioritize usage</span>
                        </div>
                    </div>
                    <button type="button" class="w-6.5 h-6.5 xl:w-7 xl:h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:border-gray-300 transition-colors shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                <!-- Card 4: Total Inventory Value -->
                <div class="bg-white border border-[#EAEAEA] rounded-xl p-4 xl:p-4.5 2xl:p-5 shadow-[0_4px_12px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)] transition-shadow flex items-center justify-between gap-3 min-w-0">
                    <div class="flex items-center gap-3 xl:gap-3.5 min-w-0 flex-1">
                        <div class="w-10 h-10 xl:w-11 xl:h-11 rounded-xl bg-[#DCFCE7] flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 xl:w-5.5 xl:h-5.5 text-[#16A34A]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <span class="block text-[10px] xl:text-[11px] font-semibold tracking-wider text-gray-500 uppercase truncate">TOTAL INVENTORY VALUE</span>
                            <div class="text-base sm:text-lg xl:text-lg 2xl:text-xl font-semibold font-poppins text-gray-900 leading-tight mt-0.5 whitespace-nowrap truncate">Rp 342.800.000</div>
                            <div class="flex items-center gap-1.5 mt-0.5 text-xs text-gray-400">
                                <span class="px-1.5 py-0.5 bg-[#DCFCE7] text-[#16A34A] text-[10px] font-bold rounded-full shrink-0">+8.4%</span>
                                <span class="truncate">vs last month</span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="w-6.5 h-6.5 xl:w-7 xl:h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:border-gray-300 transition-colors shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

            </section>

            <!-- ======================================================== -->
            <!-- 4 & 5. MAIN WORKSPACE (LEFT 65% TABLE, RIGHT 35% WIDGETS) -->
            <!-- ======================================================== -->
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                
                <!-- ========================================== -->
                <!-- 4. LEFT SIDE: INVENTORY ITEMS TABLE (65%)  -->
                <!-- ========================================== -->
                <section class="xl:col-span-8 bg-white border border-[#EAEAEA] rounded-xl shadow-level-1 overflow-hidden">
                    
                    <!-- Table Header & Filter Buttons -->
                    <div class="p-6 pb-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h1 class="text-2xl font-semibold font-poppins text-gray-900 tracking-tight">Inventory Items</h1>
                                <p class="text-xs text-gray-500 mt-0.5">Manage all ingredients, materials, and supplies across your storage units.</p>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-[#EAEAEA] rounded-xl text-xs font-semibold text-gray-700 shadow-sm hover:border-gray-300 transition-colors">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                    </svg>
                                    <span>Filter</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-[#EAEAEA] rounded-xl text-xs font-semibold text-gray-700 shadow-sm hover:border-gray-300 transition-colors">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <span>Export</span>
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Category Filter Tabs -->
                        <div class="flex items-center gap-6 mt-6 border-b border-[#EAEAEA] text-xs font-medium overflow-x-auto">
                            <button type="button" onclick="setCategoryFilter('all', this)" class="category-tab pb-3 font-semibold text-[#DC143C] border-b-2 border-[#DC143C] -mb-[1px] whitespace-nowrap">
                                All Items
                            </button>
                            <button type="button" onclick="setCategoryFilter('Fresh Produce', this)" class="category-tab pb-3 text-gray-500 hover:text-gray-900 border-b-2 border-transparent transition-colors whitespace-nowrap">
                                Fresh Produce
                            </button>
                            <button type="button" onclick="setCategoryFilter('Frozen Meat', this)" class="category-tab pb-3 text-gray-500 hover:text-gray-900 border-b-2 border-transparent transition-colors whitespace-nowrap">
                                Frozen Meat
                            </button>
                            <button type="button" onclick="setCategoryFilter('Dry Pantry', this)" class="category-tab pb-3 text-gray-500 hover:text-gray-900 border-b-2 border-transparent transition-colors whitespace-nowrap">
                                Dry Pantry
                            </button>
                            <button type="button" onclick="setCategoryFilter('Packaging', this)" class="category-tab pb-3 text-gray-500 hover:text-gray-900 border-b-2 border-transparent transition-colors whitespace-nowrap">
                                Packaging
                            </button>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div class="overflow-x-auto w-full">
                        <table class="w-full text-left border-collapse min-w-[580px]" id="inventory-table">
                            <thead>
                                <tr class="bg-[#FBF9F8] border-y border-[#EAEAEA] text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                                    <th class="py-3 px-4 w-10 text-center">
                                        <input type="checkbox" id="select-all-checkbox" class="w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    </th>
                                    <th class="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                                        <div class="flex items-center gap-1">
                                            <span>SKU Code</span>
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                        </div>
                                    </th>
                                    <th class="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                                        <div class="flex items-center gap-1">
                                            <span>Item & Batch</span>
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                        </div>
                                    </th>
                                    <th class="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                                        <div class="flex items-center gap-1">
                                            <span>Category</span>
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                        </div>
                                    </th>
                                    <th class="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                                        <div class="flex items-center gap-1">
                                            <span>Stock Bar</span>
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                        </div>
                                    </th>
                                    <th class="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                                        <div class="flex items-center gap-1">
                                            <span>Expiry (FEFO)</span>
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                        </div>
                                    </th>
                                    <th class="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                                        <div class="flex items-center gap-1">
                                            <span>Status</span>
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                        </div>
                                    </th>
                                    <th class="py-3 px-3 text-center w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#EAEAEA] text-xs font-inter" id="table-body">
                                
                                <!-- ROW 1 -->
                                <tr class="inventory-row hover:bg-gray-50/70 transition-colors" data-category="Frozen Meat">
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" class="row-checkbox w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    </td>
                                    <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-BFF-042</td>
                                    <td class="py-3.5 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <img src="https://images.unsplash.com/photo-1544025162-d76694265947?w=100&auto=format&fit=crop&q=80" alt="Wagyu MB5" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                            <div>
                                                <div class="font-semibold text-gray-900 leading-tight">Daging Sapi Wagyu MB5</div>
                                                <span class="text-[11px] text-gray-400">Batch #B92</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-gray-600 font-medium">Frozen Meat</td>
                                    <td class="py-3.5 px-3 min-w-[105px]">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1">
                                            <div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2">
                                                <div class="bg-[#28A745] h-full rounded-full" style="width: 85%;"></div>
                                            </div>
                                            <span>85%</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-plex-mono">120 kg / 140 kg</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-[#DC143C]">12 Nov 2026</div>
                                        <div class="text-[10px] text-gray-400">128 days left</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8ED] text-[#16A34A]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#28A745]"></span>
                                            Optimal
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <button type="button" class="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1.5"/><circle cx="6" cy="12" r="1.5"/><circle cx="18" cy="12" r="1.5"/></svg>
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 2 -->
                                <tr class="inventory-row hover:bg-gray-50/70 transition-colors" data-category="Dry Pantry">
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" class="row-checkbox w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    </td>
                                    <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-DRY-108</td>
                                    <td class="py-3.5 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <img src="https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=100&auto=format&fit=crop&q=80" alt="Minyak Goreng" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                            <div>
                                                <div class="font-semibold text-gray-900 leading-tight">Minyak Goreng Sawit 18L</div>
                                                <span class="text-[11px] text-gray-400">Batch #MGS-230</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-gray-600 font-medium">Dry Pantry</td>
                                    <td class="py-3.5 px-3 min-w-[105px]">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1">
                                            <div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2">
                                                <div class="bg-[#DC143C] h-full rounded-full" style="width: 12%;"></div>
                                            </div>
                                            <span>12%</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-plex-mono">3 Can / 24 Can</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-[#DC143C]">04 Mar 2027</div>
                                        <div class="text-[10px] text-gray-400">210 days left</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FFF1F0] text-[#DC143C]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#DC143C]"></span>
                                            Low Stock
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <button type="button" class="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1.5"/><circle cx="6" cy="12" r="1.5"/><circle cx="18" cy="12" r="1.5"/></svg>
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 3 -->
                                <tr class="inventory-row hover:bg-gray-50/70 transition-colors" data-category="Fresh Produce">
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" class="row-checkbox w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    </td>
                                    <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-FSH-019</td>
                                    <td class="py-3.5 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <img src="https://images.unsplash.com/photo-1550583724-b2692b85b150?w=100&auto=format&fit=crop&q=80" alt="Susu UHT" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                            <div>
                                                <div class="font-semibold text-gray-900 leading-tight">Susu UHT Full Cream 1L</div>
                                                <span class="text-[11px] text-gray-400">Batch #SFC-771</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-gray-600 font-medium">Dairy / Fresh</td>
                                    <td class="py-3.5 px-3 min-w-[105px]">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1">
                                            <div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2">
                                                <div class="bg-[#F59E0B] h-full rounded-full" style="width: 40%;"></div>
                                            </div>
                                            <span>40%</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-plex-mono">24 Pcs / 60 Pcs</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-[#DC143C]">09 Oct 2026</div>
                                        <div class="text-[10px] text-gray-400">25 days left</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] text-[#D97706]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span>
                                            Expiring Soon
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <button type="button" class="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1.5"/><circle cx="6" cy="12" r="1.5"/><circle cx="18" cy="12" r="1.5"/></svg>
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 4 -->
                                <tr class="inventory-row hover:bg-gray-50/70 transition-colors" data-category="Fresh Produce">
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" class="row-checkbox w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    </td>
                                    <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-FSH-027</td>
                                    <td class="py-3.5 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <img src="https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=100&auto=format&fit=crop&q=80" alt="Tomat Merah" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                            <div>
                                                <div class="font-semibold text-gray-900 leading-tight">Tomat Merah Segar</div>
                                                <span class="text-[11px] text-gray-400">Batch #TMR-021</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-gray-600 font-medium">Fresh Produce</td>
                                    <td class="py-3.5 px-3 min-w-[105px]">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1">
                                            <div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2">
                                                <div class="bg-[#28A745] h-full rounded-full" style="width: 78%;"></div>
                                            </div>
                                            <span>78%</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-plex-mono">39 kg / 50 kg</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-[#DC143C]">11 Oct 2026</div>
                                        <div class="text-[10px] text-gray-400">27 days left</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8ED] text-[#16A34A]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#28A745]"></span>
                                            Optimal
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <button type="button" class="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1.5"/><circle cx="6" cy="12" r="1.5"/><circle cx="18" cy="12" r="1.5"/></svg>
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 5 -->
                                <tr class="inventory-row hover:bg-gray-50/70 transition-colors" data-category="Fresh Produce">
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" class="row-checkbox w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    </td>
                                    <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-FSH-031</td>
                                    <td class="py-3.5 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <img src="https://images.unsplash.com/photo-1540420773420-3366772f4999?w=100&auto=format&fit=crop&q=80" alt="Selada Romaine" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                            <div>
                                                <div class="font-semibold text-gray-900 leading-tight">Selada Romaine</div>
                                                <span class="text-[11px] text-gray-400">Batch #SLD-118</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-gray-600 font-medium">Fresh Produce</td>
                                    <td class="py-3.5 px-3 min-w-[105px]">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1">
                                            <div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2">
                                                <div class="bg-[#F59E0B] h-full rounded-full" style="width: 65%;"></div>
                                            </div>
                                            <span>65%</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-plex-mono">13 kg / 20 kg</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-[#DC143C]">08 Oct 2026</div>
                                        <div class="text-[10px] text-gray-400">24 days left</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] text-[#D97706]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#F59E0B]"></span>
                                            Expiring Soon
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <button type="button" class="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1.5"/><circle cx="6" cy="12" r="1.5"/><circle cx="18" cy="12" r="1.5"/></svg>
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 6 -->
                                <tr class="inventory-row hover:bg-gray-50/70 transition-colors" data-category="Frozen Meat">
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" class="row-checkbox w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    </td>
                                    <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-BFF-067</td>
                                    <td class="py-3.5 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <img src="https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=100&auto=format&fit=crop&q=80" alt="Dada Ayam Fillet" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                            <div>
                                                <div class="font-semibold text-gray-900 leading-tight">Dada Ayam Fillet</div>
                                                <span class="text-[11px] text-gray-400">Batch #AYM-556</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-gray-600 font-medium">Frozen Meat</td>
                                    <td class="py-3.5 px-3 min-w-[105px]">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1">
                                            <div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2">
                                                <div class="bg-[#DC143C] h-full rounded-full" style="width: 33%;"></div>
                                            </div>
                                            <span>33%</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-plex-mono">10 kg / 30 kg</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-[#DC143C]">15 Oct 2026</div>
                                        <div class="text-[10px] text-gray-400">31 days left</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FFF1F0] text-[#DC143C]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#DC143C]"></span>
                                            Low Stock
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <button type="button" class="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1.5"/><circle cx="6" cy="12" r="1.5"/><circle cx="18" cy="12" r="1.5"/></svg>
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 7 -->
                                <tr class="inventory-row hover:bg-gray-50/70 transition-colors" data-category="Dry Pantry">
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" class="row-checkbox w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    </td>
                                    <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-DRY-220</td>
                                    <td class="py-3.5 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <img src="https://images.unsplash.com/photo-1586201375761-83865001e31c?w=100&auto=format&fit=crop&q=80" alt="Beras Premium" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                            <div>
                                                <div class="font-semibold text-gray-900 leading-tight">Beras Premium 5kg</div>
                                                <span class="text-[11px] text-gray-400">Batch #BR-900</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-gray-600 font-medium">Dry Pantry</td>
                                    <td class="py-3.5 px-3 min-w-[105px]">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1">
                                            <div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2">
                                                <div class="bg-[#28A745] h-full rounded-full" style="width: 92%;"></div>
                                            </div>
                                            <span>92%</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-plex-mono">46 kg / 50 kg</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-[#DC143C]">01 May 2027</div>
                                        <div class="text-[10px] text-gray-400">268 days left</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8ED] text-[#16A34A]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#28A745]"></span>
                                            Optimal
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <button type="button" class="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1.5"/><circle cx="6" cy="12" r="1.5"/><circle cx="18" cy="12" r="1.5"/></svg>
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 8 -->
                                <tr class="inventory-row hover:bg-gray-50/70 transition-colors" data-category="Packaging">
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" class="row-checkbox w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    </td>
                                    <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-PKG-014</td>
                                    <td class="py-3.5 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <img src="https://images.unsplash.com/photo-1584727638096-042c45049ebe?w=100&auto=format&fit=crop&q=80" alt="Plastic Container" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                            <div>
                                                <div class="font-semibold text-gray-900 leading-tight">Plastic Container 750ml</div>
                                                <span class="text-[11px] text-gray-400">Batch #PC-310</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-gray-600 font-medium">Packaging</td>
                                    <td class="py-3.5 px-3 min-w-[105px]">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1">
                                            <div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2">
                                                <div class="bg-[#28A745] h-full rounded-full" style="width: 70%;"></div>
                                            </div>
                                            <span>70%</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-plex-mono">350 Pcs / 500 Pcs</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-gray-400">-</div>
                                        <div class="text-[10px] text-gray-400">Non-perishable</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8ED] text-[#16A34A]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#28A745]"></span>
                                            Optimal
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <button type="button" class="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1.5"/><circle cx="6" cy="12" r="1.5"/><circle cx="18" cy="12" r="1.5"/></svg>
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 9 -->
                                <tr class="inventory-row hover:bg-gray-50/70 transition-colors" data-category="Dry Pantry">
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" class="row-checkbox w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    </td>
                                    <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-DRY-145</td>
                                    <td class="py-3.5 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <img src="https://images.unsplash.com/photo-1581441363689-1f3c3c414635?w=100&auto=format&fit=crop&q=80" alt="Gula Pasir" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                            <div>
                                                <div class="font-semibold text-gray-900 leading-tight">Gula Pasir 1kg</div>
                                                <span class="text-[11px] text-gray-400">Batch #GP-782</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-gray-600 font-medium">Dry Pantry</td>
                                    <td class="py-3.5 px-3 min-w-[105px]">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1">
                                            <div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2">
                                                <div class="bg-[#DC143C] h-full rounded-full" style="width: 18%;"></div>
                                            </div>
                                            <span>18%</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-plex-mono">9 kg / 50 kg</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-[#DC143C]">20 Dec 2026</div>
                                        <div class="text-[10px] text-gray-400">66 days left</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FFF1F0] text-[#DC143C]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#DC143C]"></span>
                                            Low Stock
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <button type="button" class="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1.5"/><circle cx="6" cy="12" r="1.5"/><circle cx="18" cy="12" r="1.5"/></svg>
                                        </button>
                                    </td>
                                </tr>

                                <!-- ROW 10 -->
                                <tr class="inventory-row hover:bg-gray-50/70 transition-colors" data-category="Fresh Produce">
                                    <td class="py-3.5 px-4 text-center">
                                        <input type="checkbox" class="row-checkbox w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    </td>
                                    <td class="py-3.5 px-3 font-plex-mono text-xs font-medium text-gray-700">SKU-FSH-055</td>
                                    <td class="py-3.5 px-3">
                                        <div class="flex items-center gap-2.5">
                                            <img src="https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?w=100&auto=format&fit=crop&q=80" alt="Telur Ayam" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                            <div>
                                                <div class="font-semibold text-gray-900 leading-tight">Telur Ayam Omega</div>
                                                <span class="text-[11px] text-gray-400">Batch #TR-450</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-3 text-gray-600 font-medium">Dairy / Fresh</td>
                                    <td class="py-3.5 px-3 min-w-[105px]">
                                        <div class="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1">
                                            <div class="w-16 xl:w-20 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2">
                                                <div class="bg-[#28A745] h-full rounded-full" style="width: 88%;"></div>
                                            </div>
                                            <span>88%</span>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-plex-mono">880 Butir / 1.000</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <div class="font-semibold text-[#DC143C]">18 Oct 2026</div>
                                        <div class="text-[10px] text-gray-400">37 days left</div>
                                    </td>
                                    <td class="py-3.5 px-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8ED] text-[#16A34A]">
                                            <span class="w-1.5 h-1.5 rounded-full bg-[#28A745]"></span>
                                            Optimal
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-3 text-center">
                                        <button type="button" class="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="1.5"/><circle cx="6" cy="12" r="1.5"/><circle cx="18" cy="12" r="1.5"/></svg>
                                        </button>
                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Footer -->
                    <div class="px-6 py-4 border-t border-[#EAEAEA] flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500">
                        <div>
                            Showing <span class="font-semibold text-gray-800">1–10</span> of <span class="font-semibold text-gray-800">1,248</span> items
                        </div>

                        <!-- Pagination Buttons -->
                        <div class="flex items-center gap-1">
                            <button type="button" class="w-7 h-7 rounded border border-gray-200 flex items-center justify-center text-gray-400 hover:bg-gray-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button type="button" class="w-7 h-7 rounded bg-[#DC143C] text-white font-semibold flex items-center justify-center shadow-xs">1</button>
                            <button type="button" class="w-7 h-7 rounded text-gray-700 hover:bg-gray-100 flex items-center justify-center">2</button>
                            <button type="button" class="w-7 h-7 rounded text-gray-700 hover:bg-gray-100 flex items-center justify-center">3</button>
                            <button type="button" class="w-7 h-7 rounded text-gray-700 hover:bg-gray-100 flex items-center justify-center">4</button>
                            <button type="button" class="w-7 h-7 rounded text-gray-700 hover:bg-gray-100 flex items-center justify-center">5</button>
                            <span class="px-1 text-gray-400">...</span>
                            <button type="button" class="w-7 h-7 rounded text-gray-700 hover:bg-gray-100 flex items-center justify-center">125</button>
                            <button type="button" class="w-7 h-7 rounded border border-gray-200 flex items-center justify-center text-gray-700 hover:bg-gray-50">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>

                        <!-- Rows per page -->
                        <div class="flex items-center gap-2">
                            <span>Rows per page</span>
                            <div class="relative">
                                <select class="appearance-none bg-white border border-[#EAEAEA] rounded-lg px-2.5 py-1 pr-6 text-xs font-semibold text-gray-800 outline-none cursor-pointer focus:border-[#DC143C]">
                                    <option>10</option>
                                    <option>25</option>
                                    <option>50</option>
                                </select>
                                <svg class="w-3 h-3 text-gray-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                </section>

                <!-- ======================================================== -->
                <!-- 5. RIGHT SIDE: ANALYTICS & FEFO QUEUE (35%)             -->
                <!-- ======================================================== -->
                <aside class="xl:col-span-4 space-y-6">
                    
                    <!-- 5.1 Stock Consumption Analytics Widget -->
                    <div class="bg-white border border-[#EAEAEA] rounded-xl p-5 shadow-level-1">
                        <div class="flex items-center justify-between pb-4 border-b border-[#EAEAEA]">
                            <h2 class="text-sm font-semibold font-poppins text-gray-900">Stock Consumption Analytics</h2>
                            <button type="button" class="flex items-center gap-1 px-2.5 py-1 bg-white border border-[#EAEAEA] rounded-lg text-xs font-medium text-gray-600 hover:border-gray-300">
                                <span>Last 7 Days</span>
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>

                        <!-- Chart Legends -->
                        <div class="flex items-center gap-5 my-4 text-xs font-medium text-gray-600">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#DC143C]"></span>
                                <span>Ingredient Usage (kg)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-[#708090]"></span>
                                <span>Waste / Discard (kg)</span>
                            </div>
                        </div>

                        <!-- Responsive SVG Bar Chart Component -->
                        <div class="relative w-full overflow-hidden pt-2">
                            <!-- Tooltip container -->
                            <div id="chart-tooltip" class="absolute hidden bg-gray-900 text-white text-[11px] py-1 px-2.5 rounded shadow pointer-events-none z-10 font-plex-mono transform -translate-x-1/2 -translate-y-full mb-1"></div>

                            <svg class="w-full h-48 xl:h-52 overflow-hidden" viewBox="0 0 420 200" preserveAspectRatio="xMidYMid meet">
                                <!-- Horizontal Grid Lines & Y-Labels -->
                                <g class="text-[10px] fill-gray-400" font-family="Inter, sans-serif">
                                    <!-- 800 -->
                                    <text x="0" y="24">800</text>
                                    <line x1="28" y1="20" x2="415" y2="20" stroke="#F1F1F5" stroke-dasharray="3,3" stroke-width="1"/>

                                    <!-- 600 -->
                                    <text x="0" y="64">600</text>
                                    <line x1="28" y1="60" x2="415" y2="60" stroke="#F1F1F5" stroke-dasharray="3,3" stroke-width="1"/>

                                    <!-- 400 -->
                                    <text x="0" y="104">400</text>
                                    <line x1="28" y1="100" x2="415" y2="100" stroke="#F1F1F5" stroke-dasharray="3,3" stroke-width="1"/>

                                    <!-- 200 -->
                                    <text x="0" y="144">200</text>
                                    <line x1="28" y1="140" x2="415" y2="140" stroke="#F1F1F5" stroke-dasharray="3,3" stroke-width="1"/>

                                    <!-- 0 (Baseline) -->
                                    <text x="12" y="180">0</text>
                                    <line x1="28" y1="176" x2="415" y2="176" stroke="#EAEAEA" stroke-width="1"/>
                                </g>

                                <!-- Bar Pairs: (Usage max 800 corresponds to height 156px: ratio = 156/800 = 0.195) -->
                                <!-- 1. 29 Sep (Usage: 610kg -> h:119, y:57 | Waste: 190kg -> h:37, y:139) -->
                                <g class="chart-group cursor-pointer" onmouseenter="showTooltip(event, '29 Sep: Usage 610kg, Waste 190kg')" onmouseleave="hideTooltip()">
                                    <rect x="42" y="57" width="13" height="119" rx="3" ry="3" fill="#DC143C" class="hover:opacity-90 transition-opacity"/>
                                    <rect x="58" y="139" width="13" height="37" rx="3" ry="3" fill="#708090" class="hover:opacity-90 transition-opacity"/>
                                    <text x="56" y="193" text-anchor="middle" font-size="10" fill="#6B7280" font-family="Inter, sans-serif">29 Sep</text>
                                </g>

                                <!-- 2. 30 Sep (Usage: 670kg -> h:131, y:45 | Waste: 260kg -> h:51, y:125) -->
                                <g class="chart-group cursor-pointer" onmouseenter="showTooltip(event, '30 Sep: Usage 670kg, Waste 260kg')" onmouseleave="hideTooltip()">
                                    <rect x="96" y="45" width="13" height="131" rx="3" ry="3" fill="#DC143C" class="hover:opacity-90 transition-opacity"/>
                                    <rect x="112" y="125" width="13" height="51" rx="3" ry="3" fill="#708090" class="hover:opacity-90 transition-opacity"/>
                                    <text x="110" y="193" text-anchor="middle" font-size="10" fill="#6B7280" font-family="Inter, sans-serif">30 Sep</text>
                                </g>

                                <!-- 3. 1 Oct (Usage: 485kg -> h:95, y:81 | Waste: 220kg -> h:43, y:133) -->
                                <g class="chart-group cursor-pointer" onmouseenter="showTooltip(event, '1 Oct: Usage 485kg, Waste 220kg')" onmouseleave="hideTooltip()">
                                    <rect x="150" y="81" width="13" height="95" rx="3" ry="3" fill="#DC143C" class="hover:opacity-90 transition-opacity"/>
                                    <rect x="166" y="133" width="13" height="43" rx="3" ry="3" fill="#708090" class="hover:opacity-90 transition-opacity"/>
                                    <text x="164" y="193" text-anchor="middle" font-size="10" fill="#6B7280" font-family="Inter, sans-serif">1 Oct</text>
                                </g>

                                <!-- 4. 2 Oct (Usage: 690kg -> h:135, y:41 | Waste: 140kg -> h:27, y:149) -->
                                <g class="chart-group cursor-pointer" onmouseenter="showTooltip(event, '2 Oct: Usage 690kg, Waste 140kg')" onmouseleave="hideTooltip()">
                                    <rect x="204" y="41" width="13" height="135" rx="3" ry="3" fill="#DC143C" class="hover:opacity-90 transition-opacity"/>
                                    <rect x="220" y="149" width="13" height="27" rx="3" ry="3" fill="#708090" class="hover:opacity-90 transition-opacity"/>
                                    <text x="218" y="193" text-anchor="middle" font-size="10" fill="#6B7280" font-family="Inter, sans-serif">2 Oct</text>
                                </g>

                                <!-- 5. 3 Oct (Usage: 620kg -> h:121, y:55 | Waste: 180kg -> h:35, y:141) -->
                                <g class="chart-group cursor-pointer" onmouseenter="showTooltip(event, '3 Oct: Usage 620kg, Waste 180kg')" onmouseleave="hideTooltip()">
                                    <rect x="258" y="55" width="13" height="121" rx="3" ry="3" fill="#DC143C" class="hover:opacity-90 transition-opacity"/>
                                    <rect x="274" y="141" width="13" height="35" rx="3" ry="3" fill="#708090" class="hover:opacity-90 transition-opacity"/>
                                    <text x="272" y="193" text-anchor="middle" font-size="10" fill="#6B7280" font-family="Inter, sans-serif">3 Oct</text>
                                </g>

                                <!-- 6. 4 Oct (Usage: 680kg -> h:133, y:43 | Waste: 190kg -> h:37, y:139) -->
                                <g class="chart-group cursor-pointer" onmouseenter="showTooltip(event, '4 Oct: Usage 680kg, Waste 190kg')" onmouseleave="hideTooltip()">
                                    <rect x="312" y="43" width="13" height="133" rx="3" ry="3" fill="#DC143C" class="hover:opacity-90 transition-opacity"/>
                                    <rect x="328" y="139" width="13" height="37" rx="3" ry="3" fill="#708090" class="hover:opacity-90 transition-opacity"/>
                                    <text x="326" y="193" text-anchor="middle" font-size="10" fill="#6B7280" font-family="Inter, sans-serif">4 Oct</text>
                                </g>

                                <!-- 7. 5 Oct (Usage: 530kg -> h:103, y:73 | Waste: 140kg -> h:27, y:149) -->
                                <g class="chart-group cursor-pointer" onmouseenter="showTooltip(event, '5 Oct: Usage 530kg, Waste 140kg')" onmouseleave="hideTooltip()">
                                    <rect x="366" y="73" width="13" height="103" rx="3" ry="3" fill="#DC143C" class="hover:opacity-90 transition-opacity"/>
                                    <rect x="382" y="149" width="13" height="27" rx="3" ry="3" fill="#708090" class="hover:opacity-90 transition-opacity"/>
                                    <text x="380" y="193" text-anchor="middle" font-size="10" fill="#6B7280" font-family="Inter, sans-serif">5 Oct</text>
                                </g>
                            </svg>
                        </div>
                    </div>

                    <!-- 5.2 FEFO First-to-Expire Dispatch Queue Widget -->
                    <div class="bg-white border border-[#EAEAEA] rounded-xl p-5 shadow-level-1">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-semibold font-poppins text-gray-900">FEFO First-to-Expire Dispatch Queue</h2>
                            <button type="button" class="flex items-center gap-1 px-2.5 py-1 bg-white border border-[#EAEAEA] rounded-lg text-xs font-medium text-gray-600 hover:border-gray-300">
                                <span>Today</span>
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1 mb-4">Ingredients that should be used first based on expiry date.</p>

                        <!-- FEFO Items List -->
                        <div class="divide-y divide-[#EAEAEA]">
                            
                            <!-- Item 1: Selada Romaine -->
                            <div class="py-3 flex items-center justify-between gap-3 group hover:bg-gray-50/60 px-1 rounded-lg transition-colors">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    <img src="https://images.unsplash.com/photo-1540420773420-3366772f4999?w=100&auto=format&fit=crop&q=80" alt="Selada Romaine" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                    <div class="truncate">
                                        <div class="font-semibold text-xs text-gray-900 leading-tight">Selada Romaine</div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">
                                            Batch #SLD-118 · <span class="text-[#DC143C] font-semibold">24 days left</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="text-right">
                                        <div class="text-xs font-semibold text-gray-900 font-plex-mono">13 kg</div>
                                        <span class="text-[10px] font-semibold text-[#DC143C]">Use today</span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Item 2: Susu UHT Full Cream 1L -->
                            <div class="py-3 flex items-center justify-between gap-3 group hover:bg-gray-50/60 px-1 rounded-lg transition-colors">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    <img src="https://images.unsplash.com/photo-1550583724-b2692b85b150?w=100&auto=format&fit=crop&q=80" alt="Susu UHT" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                    <div class="truncate">
                                        <div class="font-semibold text-xs text-gray-900 leading-tight">Susu UHT Full Cream 1L</div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">
                                            Batch #SFC-771 · <span class="text-[#DC143C] font-semibold">25 days left</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="text-right">
                                        <div class="text-xs font-semibold text-gray-900 font-plex-mono">24 Pcs</div>
                                        <span class="text-[10px] font-semibold text-[#DC143C]">Use today</span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Item 3: Tomat Merah Segar -->
                            <div class="py-3 flex items-center justify-between gap-3 group hover:bg-gray-50/60 px-1 rounded-lg transition-colors">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    <img src="https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=100&auto=format&fit=crop&q=80" alt="Tomat Merah" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                    <div class="truncate">
                                        <div class="font-semibold text-xs text-gray-900 leading-tight">Tomat Merah Segar</div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">
                                            Batch #TMR-021 · <span class="text-[#DC143C] font-semibold">27 days left</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="text-right">
                                        <div class="text-xs font-semibold text-gray-900 font-plex-mono">18 kg</div>
                                        <span class="text-[10px] font-semibold text-[#DC143C]">Use today</span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Item 4: Dada Ayam Fillet -->
                            <div class="py-3 flex items-center justify-between gap-3 group hover:bg-gray-50/60 px-1 rounded-lg transition-colors">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    <img src="https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=100&auto=format&fit=crop&q=80" alt="Dada Ayam Fillet" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                    <div class="truncate">
                                        <div class="font-semibold text-xs text-gray-900 leading-tight">Dada Ayam Fillet</div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">
                                            Batch #AYM-556 · <span class="text-[#DC143C] font-semibold">31 days left</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="text-right">
                                        <div class="text-xs font-semibold text-gray-900 font-plex-mono">10 kg</div>
                                        <span class="text-[10px] font-semibold text-[#DC143C]">Use this week</span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Item 5: Telur Ayam Omega -->
                            <div class="py-3 flex items-center justify-between gap-3 group hover:bg-gray-50/60 px-1 rounded-lg transition-colors">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer">
                                    <img src="https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?w=100&auto=format&fit=crop&q=80" alt="Telur Ayam Omega" class="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0">
                                    <div class="truncate">
                                        <div class="font-semibold text-xs text-gray-900 leading-tight">Telur Ayam Omega</div>
                                        <div class="text-[11px] text-gray-400 mt-0.5">
                                            Batch #TR-450 · <span class="text-[#DC143C] font-semibold">37 days left</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <div class="text-right">
                                        <div class="text-xs font-semibold text-gray-900 font-plex-mono">3 Tray</div>
                                        <span class="text-[10px] font-semibold text-[#DC143C]">Use this week</span>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>

                        </div>

                        <!-- Footer Action Link -->
                        <div class="pt-4 border-t border-[#EAEAEA] mt-2">
                            <a href="#" class="w-full inline-flex items-center justify-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-[#DC143C] transition-colors py-1">
                                <span>View Full FEFO List</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>

                </aside>

            </div>

        </main>
    </div>

    <!-- Quick Stock In Modal -->
    <div id="quick-stock-modal" class="fixed inset-0 z-50 hidden bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-xl border border-[#EAEAEA] shadow-level-2 w-full max-w-lg p-6 animate-in fade-in zoom-in-95 duration-150">
            <div class="flex items-center justify-between pb-3 border-b border-[#EAEAEA]">
                <h3 class="text-base font-semibold font-poppins text-gray-900 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#DC143C]"></span>
                    Quick Stock In
                </h3>
                <button type="button" onclick="closeStockInModal()" class="text-gray-400 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form onsubmit="handleStockInSubmit(event)" class="mt-4 space-y-4 text-xs font-inter">
                <div>
                    <label class="block font-semibold text-gray-700 mb-1">Select Item / SKU</label>
                    <select class="w-full px-3 py-2 bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl outline-none focus:bg-white focus:border-[#DC143C]">
                        <option>SKU-BFF-042 — Daging Sapi Wagyu MB5</option>
                        <option>SKU-DRY-108 — Minyak Goreng Sawit 18L</option>
                        <option>SKU-FSH-019 — Susu UHT Full Cream 1L</option>
                        <option>SKU-FSH-027 — Tomat Merah Segar</option>
                        <option>SKU-FSH-031 — Selada Romaine</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Quantity</label>
                        <input type="number" placeholder="e.g. 50" required class="w-full px-3 py-2 bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl outline-none focus:bg-white focus:border-[#DC143C]">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Batch Number</label>
                        <input type="text" placeholder="e.g. #B93" required class="w-full px-3 py-2 bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl outline-none focus:bg-white focus:border-[#DC143C]">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Expiry Date (FEFO)</label>
                        <input type="date" required class="w-full px-3 py-2 bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl outline-none focus:bg-white focus:border-[#DC143C]">
                    </div>
                    <div>
                        <label class="block font-semibold text-gray-700 mb-1">Supplier</label>
                        <input type="text" value="WRT / Main Kitchen" class="w-full px-3 py-2 bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl outline-none focus:bg-white focus:border-[#DC143C]">
                    </div>
                </div>

                <div class="pt-4 border-t border-[#EAEAEA] flex items-center justify-end gap-2">
                    <button type="button" onclick="closeStockInModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-[#DC143C] hover:bg-[#B1002C] text-white font-semibold rounded-xl shadow-sm transition-colors">
                        Confirm Stock In
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Interactive Client-side Script -->
<script>
    // Tab category filter
    let activeCategory = 'all';
    function setCategoryFilter(category, buttonEl) {
        activeCategory = category;
        
        // Update tab styles
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.classList.remove('font-semibold', 'text-[#DC143C]', 'border-[#DC143C]');
            tab.classList.add('text-gray-500', 'border-transparent');
        });
        buttonEl.classList.remove('text-gray-500', 'border-transparent');
        buttonEl.classList.add('font-semibold', 'text-[#DC143C]', 'border-[#DC143C]');

        filterTable();
    }

    // Search filter
    const searchInput = document.getElementById('table-search-input');
    searchInput.addEventListener('input', () => {
        filterTable();
    });

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#table-body .inventory-row');

        rows.forEach(row => {
            const cat = row.getAttribute('data-category');
            const rowText = row.innerText.toLowerCase();

            const matchesCategory = (activeCategory === 'all' || cat === activeCategory);
            const matchesQuery = query === '' || rowText.includes(query);

            if (matchesCategory && matchesQuery) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Select all checkboxes
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');

    selectAllCheckbox.addEventListener('change', () => {
        rowCheckboxes.forEach(cb => {
            cb.checked = selectAllCheckbox.checked;
        });
    });

    // Chart tooltip
    const tooltip = document.getElementById('chart-tooltip');
    function showTooltip(e, text) {
        tooltip.innerText = text;
        tooltip.classList.remove('hidden');
        const container = tooltip.parentElement.getBoundingClientRect();
        const x = e.clientX - container.left;
        const y = e.clientY - container.top;
        tooltip.style.left = x + 'px';
        tooltip.style.top = y + 'px';
    }

    function hideTooltip() {
        tooltip.classList.add('hidden');
    }

    // Modal controls
    const modal = document.getElementById('quick-stock-modal');
    function openStockInModal() {
        modal.classList.remove('hidden');
    }

    function closeStockInModal() {
        modal.classList.add('hidden');
    }

    function handleStockInSubmit(e) {
        e.preventDefault();
        alert('Stock In recorded successfully! Inventory levels and movements updated.');
        closeStockInModal();
    }
</script>
@endsection
