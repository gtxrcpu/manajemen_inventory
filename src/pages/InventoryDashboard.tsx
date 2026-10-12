import React, { useState, useMemo } from 'react';

// ==========================================
// TYPES & INTERFACES
// ==========================================
export type ItemCategory = 'All Items' | 'Fresh Produce' | 'Frozen Meat' | 'Dry Pantry' | 'Packaging';

export interface InventoryItem {
  id: string;
  sku: string;
  name: string;
  batch: string;
  category: 'Fresh Produce' | 'Frozen Meat' | 'Dry Pantry' | 'Packaging' | 'Dairy / Fresh';
  imageUrl: string;
  stockPercentage: number;
  stockCurrent: string;
  stockMax: string;
  stockBarColor: 'optimal' | 'warning' | 'danger';
  expiryDate: string;
  daysLeft: number | null;
  status: 'Optimal' | 'Low Stock' | 'Expiring Soon';
}

export interface FEFOQueueItem {
  id: string;
  name: string;
  batch: string;
  daysLeft: number;
  quantity: string;
  actionTag: 'Use today' | 'Use this week';
  imageUrl: string;
}

export interface DailyConsumption {
  date: string;
  usageKg: number;
  wasteKg: number;
}

// ==========================================
// MOCK DATA (1:1 FROM MOCKUP)
// ==========================================
const INVENTORY_DATA: InventoryItem[] = [
  {
    id: '1',
    sku: 'SKU-BFF-042',
    name: 'Daging Sapi Wagyu MB5',
    batch: '#B92',
    category: 'Frozen Meat',
    imageUrl: 'https://images.unsplash.com/photo-1544025162-d76694265947?w=120&auto=format&fit=crop&q=80',
    stockPercentage: 85,
    stockCurrent: '120 kg',
    stockMax: '140 kg',
    stockBarColor: 'optimal',
    expiryDate: '12 Nov 2026',
    daysLeft: 128,
    status: 'Optimal',
  },
  {
    id: '2',
    sku: 'SKU-DRY-108',
    name: 'Minyak Goreng Sawit 18L',
    batch: '#MGS-230',
    category: 'Dry Pantry',
    imageUrl: 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=120&auto=format&fit=crop&q=80',
    stockPercentage: 12,
    stockCurrent: '3 Can',
    stockMax: '24 Can',
    stockBarColor: 'danger',
    expiryDate: '04 Mar 2027',
    daysLeft: 210,
    status: 'Low Stock',
  },
  {
    id: '3',
    sku: 'SKU-FSH-019',
    name: 'Susu UHT Full Cream 1L',
    batch: '#SFC-771',
    category: 'Dairy / Fresh',
    imageUrl: 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=120&auto=format&fit=crop&q=80',
    stockPercentage: 40,
    stockCurrent: '24 Pcs',
    stockMax: '60 Pcs',
    stockBarColor: 'warning',
    expiryDate: '09 Oct 2026',
    daysLeft: 25,
    status: 'Expiring Soon',
  },
  {
    id: '4',
    sku: 'SKU-FSH-027',
    name: 'Tomat Merah Segar',
    batch: '#TMR-021',
    category: 'Fresh Produce',
    imageUrl: 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=120&auto=format&fit=crop&q=80',
    stockPercentage: 78,
    stockCurrent: '39 kg',
    stockMax: '50 kg',
    stockBarColor: 'optimal',
    expiryDate: '11 Oct 2026',
    daysLeft: 27,
    status: 'Optimal',
  },
  {
    id: '5',
    sku: 'SKU-FSH-031',
    name: 'Selada Romaine',
    batch: '#SLD-118',
    category: 'Fresh Produce',
    imageUrl: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=120&auto=format&fit=crop&q=80',
    stockPercentage: 65,
    stockCurrent: '13 kg',
    stockMax: '20 kg',
    stockBarColor: 'warning',
    expiryDate: '08 Oct 2026',
    daysLeft: 24,
    status: 'Expiring Soon',
  },
  {
    id: '6',
    sku: 'SKU-BFF-067',
    name: 'Dada Ayam Fillet',
    batch: '#AYM-556',
    category: 'Frozen Meat',
    imageUrl: 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=120&auto=format&fit=crop&q=80',
    stockPercentage: 33,
    stockCurrent: '10 kg',
    stockMax: '30 kg',
    stockBarColor: 'danger',
    expiryDate: '15 Oct 2026',
    daysLeft: 31,
    status: 'Low Stock',
  },
  {
    id: '7',
    sku: 'SKU-DRY-220',
    name: 'Beras Premium 5kg',
    batch: '#BR-900',
    category: 'Dry Pantry',
    imageUrl: 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=120&auto=format&fit=crop&q=80',
    stockPercentage: 92,
    stockCurrent: '46 kg',
    stockMax: '50 kg',
    stockBarColor: 'optimal',
    expiryDate: '01 May 2027',
    daysLeft: 268,
    status: 'Optimal',
  },
  {
    id: '8',
    sku: 'SKU-PKG-014',
    name: 'Plastic Container 750ml',
    batch: '#PC-310',
    category: 'Packaging',
    imageUrl: 'https://images.unsplash.com/photo-1584727638096-042c45049ebe?w=120&auto=format&fit=crop&q=80',
    stockPercentage: 70,
    stockCurrent: '350 Pcs',
    stockMax: '500 Pcs',
    stockBarColor: 'optimal',
    expiryDate: '-',
    daysLeft: null,
    status: 'Optimal',
  },
  {
    id: '9',
    sku: 'SKU-DRY-145',
    name: 'Gula Pasir 1kg',
    batch: '#GP-782',
    category: 'Dry Pantry',
    imageUrl: 'https://images.unsplash.com/photo-1581441363689-1f3c3c414635?w=120&auto=format&fit=crop&q=80',
    stockPercentage: 18,
    stockCurrent: '9 kg',
    stockMax: '50 kg',
    stockBarColor: 'danger',
    expiryDate: '20 Dec 2026',
    daysLeft: 66,
    status: 'Low Stock',
  },
  {
    id: '10',
    sku: 'SKU-FSH-055',
    name: 'Telur Ayam Omega',
    batch: '#TR-450',
    category: 'Dairy / Fresh',
    imageUrl: 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?w=120&auto=format&fit=crop&q=80',
    stockPercentage: 88,
    stockCurrent: '880 Butir',
    stockMax: '1.000',
    stockBarColor: 'optimal',
    expiryDate: '18 Oct 2026',
    daysLeft: 37,
    status: 'Optimal',
  },
];

const FEFO_QUEUE: FEFOQueueItem[] = [
  {
    id: 'fefo-1',
    name: 'Selada Romaine',
    batch: '#SLD-118',
    daysLeft: 24,
    quantity: '13 kg',
    actionTag: 'Use today',
    imageUrl: 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=100&auto=format&fit=crop&q=80',
  },
  {
    id: 'fefo-2',
    name: 'Susu UHT Full Cream 1L',
    batch: '#SFC-771',
    daysLeft: 25,
    quantity: '24 Pcs',
    actionTag: 'Use today',
    imageUrl: 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=100&auto=format&fit=crop&q=80',
  },
  {
    id: 'fefo-3',
    name: 'Tomat Merah Segar',
    batch: '#TMR-021',
    daysLeft: 27,
    quantity: '18 kg',
    actionTag: 'Use today',
    imageUrl: 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=100&auto=format&fit=crop&q=80',
  },
  {
    id: 'fefo-4',
    name: 'Dada Ayam Fillet',
    batch: '#AYM-556',
    daysLeft: 31,
    quantity: '10 kg',
    actionTag: 'Use this week',
    imageUrl: 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=100&auto=format&fit=crop&q=80',
  },
  {
    id: 'fefo-5',
    name: 'Telur Ayam Omega',
    batch: '#TR-450',
    daysLeft: 37,
    quantity: '3 Tray',
    actionTag: 'Use this week',
    imageUrl: 'https://images.unsplash.com/photo-1582722872445-44dc5f7e3c8f?w=100&auto=format&fit=crop&q=80',
  },
];

const CONSUMPTION_DATA: DailyConsumption[] = [
  { date: '29 Sep', usageKg: 610, wasteKg: 190 },
  { date: '30 Sep', usageKg: 670, wasteKg: 260 },
  { date: '1 Oct', usageKg: 485, wasteKg: 220 },
  { date: '2 Oct', usageKg: 690, wasteKg: 140 },
  { date: '3 Oct', usageKg: 620, wasteKg: 180 },
  { date: '4 Oct', usageKg: 680, wasteKg: 190 },
  { date: '5 Oct', usageKg: 530, wasteKg: 140 },
];

export const InventoryDashboard: React.FC = () => {
  const [activeTab, setActiveTab] = useState<ItemCategory>('All Items');
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
  const [isStockInModalOpen, setIsStockInModalOpen] = useState(false);
  const [chartTooltip, setChartTooltip] = useState<{ text: string; x: number; y: number } | null>(null);

  // Filtered rows based on category tab & search
  const filteredItems = useMemo(() => {
    return INVENTORY_DATA.filter((item) => {
      const matchesCategory =
        activeTab === 'All Items' ||
        (activeTab === 'Fresh Produce' && (item.category === 'Fresh Produce' || item.category === 'Dairy / Fresh')) ||
        item.category === activeTab;

      const q = searchQuery.toLowerCase().trim();
      const matchesSearch =
        !q ||
        item.name.toLowerCase().includes(q) ||
        item.sku.toLowerCase().includes(q) ||
        item.batch.toLowerCase().includes(q) ||
        item.category.toLowerCase().includes(q);

      return matchesCategory && matchesSearch;
    });
  }, [activeTab, searchQuery]);

  // Checkbox toggle handlers
  const handleSelectAll = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.checked) {
      setSelectedIds(new Set(filteredItems.map((i) => i.id)));
    } else {
      setSelectedIds(new Set());
    }
  };

  const handleSelectItem = (id: string) => {
    const updated = new Set(selectedIds);
    if (updated.has(id)) {
      updated.delete(id);
    } else {
      updated.add(id);
    }
    setSelectedIds(updated);
  };

  return (
    <div className="min-h-screen flex flex-col bg-[#FBF9F8] font-inter text-[#1B1C1C]">
      {/* ======================================================== */}
      {/* 1. TOP HEADER NAVBAR (<header>)                          */}
      {/* ======================================================== */}
      <header className="sticky top-0 z-40 w-full bg-white border-b border-[#EAEAEA] shadow-[0_2px_8px_rgba(0,0,0,0.02)]">
        <div className="w-full px-6 py-3.5 flex items-center justify-between gap-4">
          
          {/* Left: Brand Logo & Central Warehouse Selector */}
          <div className="flex items-center gap-6 shrink-0">
            <a href="/" className="flex items-center text-[22px] font-semibold font-poppins text-[#1B1C1C] tracking-tight hover:opacity-95 transition-opacity">
              Technolife<span className="inline-block w-2.5 h-2.5 rounded-full bg-[#DC143C] ml-0.5" />
            </a>

            {/* Warehouse Dropdown Button */}
            <button
              type="button"
              className="flex items-center gap-2 px-3.5 py-1.5 bg-white border border-[#EAEAEA] rounded-xl text-sm font-medium text-gray-700 shadow-sm hover:border-gray-300 transition-colors"
            >
              <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>Central Warehouse</span>
              <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
          </div>

          {/* Center: Global Search Input */}
          <div className="flex-1 max-w-xl mx-4">
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search SKU, ingredient, batch no, or supplier..."
                className="w-full pl-10 pr-4 py-2 bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl text-sm text-gray-900 placeholder:text-gray-400 focus:bg-white focus:border-[#DC143C] focus:ring-2 focus:ring-[#DC143C]/10 outline-none transition-all"
              />
            </div>
          </div>

          {/* Right Actions: Quick Stock In, Audit Stock, Notification, Profile */}
          <div className="flex items-center gap-3 shrink-0">
            {/* Primary Action Button */}
            <button
              type="button"
              onClick={() => setIsStockInModalOpen(true)}
              className="inline-flex items-center gap-1.5 px-4 py-2 bg-[#DC143C] hover:bg-[#B1002C] text-white text-sm font-semibold rounded-xl shadow-sm transition-all duration-150 active:scale-95"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
              </svg>
              <span>Quick Stock In</span>
            </button>

            {/* Secondary Action Button */}
            <button
              type="button"
              className="inline-flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-[#FFF1F0] text-[#DC143C] border-[1.5px] border-[#DC143C] text-sm font-semibold rounded-xl transition-all duration-150 active:scale-95"
            >
              <span>Audit Stock</span>
            </button>

            {/* Notification Bell with Badge 3 */}
            <button type="button" className="relative p-2 text-gray-600 hover:text-gray-900 transition-colors ml-1" title="3 Notifications">
              <svg className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth={1.8} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span className="absolute top-1 right-1 w-4 h-4 bg-[#DC143C] text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                3
              </span>
            </button>

            {/* User Profile Info */}
            <div className="flex items-center gap-2 pl-2 border-l border-[#EAEAEA]">
              <span className="px-1.5 py-0.5 bg-[#DCFCE7] text-[#16A34A] text-[11px] font-bold rounded tracking-wider">
                ON
              </span>
              <img
                src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80"
                alt="Operations Manager Avatar"
                className="w-8 h-8 rounded-full object-cover border border-gray-200"
              />
              <div className="flex items-center gap-1 cursor-pointer">
                <span className="text-xs font-semibold text-gray-800">Operations Manager</span>
                <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>

          </div>

        </div>
      </header>

      {/* Main Body Grid */}
      <div className="flex-1 flex w-full">
        {/* ======================================================== */}
        {/* 2. LEFT SIDEBAR NAVIGATION (<aside>)                     */}
        {/* ======================================================== */}
        <aside className="w-56 shrink-0 bg-[#FBF9F8] border-r border-[#EAEAEA] flex flex-col justify-between py-5 px-3">
          <nav className="space-y-1">
            <a href="#dashboard" className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100/70 transition-colors">
              <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Dashboard</span>
            </a>

            {/* Active Inventory Tab */}
            <a href="#inventory" className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold bg-[#FFF1F0] text-[#DC143C] shadow-xs">
              <svg className="w-4 h-4 text-[#DC143C]" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
              <span>Inventory</span>
            </a>

            <a href="#stock-in" className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100/70 transition-colors">
              <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Stock In</span>
            </a>

            <a href="#stock-out" className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100/70 transition-colors">
              <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Stock Out</span>
            </a>

            <a href="#transfers" className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100/70 transition-colors">
              <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
              </svg>
              <span>Transfers</span>
            </a>

            <a href="#purchasing" className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100/70 transition-colors">
              <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span>Purchasing</span>
            </a>

            <a href="#suppliers" className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100/70 transition-colors">
              <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              <span>Suppliers</span>
            </a>

            <a href="#reports" className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100/70 transition-colors">
              <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span>Reports</span>
            </a>

            <a href="#adjustments" className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100/70 transition-colors">
              <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
              </svg>
              <span>Adjustments</span>
            </a>

            <a href="#master-data" className="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100/70 transition-colors">
              <div className="flex items-center gap-3">
                <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
                <span>Master Data</span>
              </div>
              <svg className="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
              </svg>
            </a>
          </nav>

          {/* Bottom Settings Link */}
          <div className="pt-4 border-t border-[#EAEAEA]">
            <a href="#settings" className="flex items-center gap-3 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100/70 transition-colors">
              <svg className="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <span>Settings</span>
            </a>
          </div>
        </aside>

        {/* ======================================================== */}
        {/* 3. MAIN WORKSPACE CONTENT (<main>)                       */}
        {/* ======================================================== */}
        <main className="flex-1 p-6 lg:p-8 space-y-6 max-w-[1400px]">
          
          {/* ======================================================== */}
          {/* 3.1 TOP 4 METRIC KPI CARDS (<section>)                   */}
          {/* ======================================================== */}
          <section className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            
            {/* Card 1: Total Active SKUs */}
            <div className="bg-white border border-[#EAEAEA] rounded-xl p-5 shadow-[0_4px_12px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)] transition-shadow flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 rounded-xl bg-[#F5F5FA] flex items-center justify-center shrink-0">
                  <svg className="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" strokeWidth={1.8} viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                </div>
                <div>
                  <span className="block text-[11px] font-semibold tracking-wider text-gray-500 uppercase">TOTAL ACTIVE SKUs</span>
                  <div className="text-[22px] font-semibold font-poppins text-gray-900 leading-tight mt-0.5">1,248 Items</div>
                  <span className="text-xs text-gray-400 mt-0.5 block">4 Storage Units</span>
                </div>
              </div>
              <button type="button" className="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:border-gray-300 transition-colors">
                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>

            {/* Card 2: Low Stock Warning */}
            <div className="bg-white border border-[#EAEAEA] rounded-xl p-5 shadow-[0_4px_12px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)] transition-shadow flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 rounded-xl bg-[#FFF1F0] flex items-center justify-center shrink-0">
                  <svg className="w-6 h-6 text-[#DC143C]" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9" />
                    <line x1="12" y1="8" x2="12" y2="12" />
                    <line x1="12" y1="16" x2="12.01" y2="16" />
                  </svg>
                </div>
                <div>
                  <span className="block text-[11px] font-semibold tracking-wider text-[#DC143C] uppercase">LOW STOCK WARNING</span>
                  <div className="text-[22px] font-semibold font-poppins text-[#DC143C] leading-tight mt-0.5">12 SKUs</div>
                  <span className="text-xs text-gray-400 mt-0.5 block">Needs restock soon</span>
                </div>
              </div>
              <button type="button" className="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:border-gray-300 transition-colors">
                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>

            {/* Card 3: Expiring Soon (FEFO) */}
            <div className="bg-white border border-[#EAEAEA] rounded-xl p-5 shadow-[0_4px_12px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)] transition-shadow flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 rounded-xl bg-[#FEF3C7] flex items-center justify-center shrink-0">
                  <svg className="w-6 h-6 text-[#F59E0B]" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9" />
                    <polyline points="12 6 12 12 16 14" />
                  </svg>
                </div>
                <div>
                  <span className="block text-[11px] font-semibold tracking-wider text-[#F59E0B] uppercase">EXPIRING SOON (FEFO)</span>
                  <div className="text-[22px] font-semibold font-poppins text-gray-900 leading-tight mt-0.5">8 Batches &lt; 3 Days</div>
                  <span className="text-xs text-gray-400 mt-0.5 block">Prioritize usage</span>
                </div>
              </div>
              <button type="button" className="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:border-gray-300 transition-colors">
                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>

            {/* Card 4: Total Inventory Value */}
            <div className="bg-white border border-[#EAEAEA] rounded-xl p-5 shadow-[0_4px_12px_rgba(0,0,0,0.05)] hover:shadow-[0_8px_24px_rgba(0,0,0,0.08)] transition-shadow flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="w-12 h-12 rounded-xl bg-[#DCFCE7] flex items-center justify-center shrink-0">
                  <svg className="w-6 h-6 text-[#16A34A]" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                  </svg>
                </div>
                <div>
                  <span className="block text-[11px] font-semibold tracking-wider text-gray-500 uppercase">TOTAL INVENTORY VALUE</span>
                  <div className="flex items-center gap-1.5 mt-0.5">
                    <span className="text-[20px] font-semibold font-poppins text-gray-900 leading-tight">Rp 342.800.000</span>
                    <span className="px-1.5 py-0.5 bg-[#DCFCE7] text-[#16A34A] text-[10px] font-bold rounded-full">+8.4%</span>
                  </div>
                  <span className="text-xs text-gray-400 mt-0.5 block">vs last month</span>
                </div>
              </div>
              <button type="button" className="w-7 h-7 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-700 hover:border-gray-300 transition-colors">
                <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>

          </section>

          {/* ======================================================== */}
          {/* 4 & 5. WORKSPACE (LEFT 65% TABLE, RIGHT 35% WIDGETS)    */}
          {/* ======================================================== */}
          <div className="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
            
            {/* ======================================================== */}
            {/* 4. LEFT SIDE: INVENTORY ITEMS TABLE (65% WIDTH)          */}
            {/* ======================================================== */}
            <section className="xl:col-span-8 bg-white border border-[#EAEAEA] rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.05)] overflow-hidden">
              
              {/* Table Card Header & Filter Buttons */}
              <div className="p-6 pb-4">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div>
                    <h1 className="text-2xl font-semibold font-poppins text-gray-900 tracking-tight">Inventory Items</h1>
                    <p className="text-xs text-gray-500 mt-0.5">Manage all ingredients, materials, and supplies across your storage units.</p>
                  </div>
                  <div className="flex items-center gap-2.5">
                    <button
                      type="button"
                      className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-[#EAEAEA] rounded-xl text-xs font-semibold text-gray-700 shadow-sm hover:border-gray-300 transition-colors"
                    >
                      <svg className="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                      </svg>
                      <span>Filter</span>
                      <svg className="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>
                    <button
                      type="button"
                      className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-[#EAEAEA] rounded-xl text-xs font-semibold text-gray-700 shadow-sm hover:border-gray-300 transition-colors"
                    >
                      <svg className="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                      </svg>
                      <span>Export</span>
                      <svg className="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>
                  </div>
                </div>

                {/* Category Filter Tabs */}
                <div className="flex items-center gap-6 mt-6 border-b border-[#EAEAEA] text-xs font-medium overflow-x-auto">
                  {(['All Items', 'Fresh Produce', 'Frozen Meat', 'Dry Pantry', 'Packaging'] as ItemCategory[]).map((tab) => (
                    <button
                      key={tab}
                      type="button"
                      onClick={() => setActiveTab(tab)}
                      className={`pb-3 transition-colors whitespace-nowrap ${
                        activeTab === tab
                          ? 'font-semibold text-[#DC143C] border-b-2 border-[#DC143C] -mb-[1px]'
                          : 'text-gray-500 hover:text-gray-900 border-b-2 border-transparent'
                      }`}
                    >
                      {tab}
                    </button>
                  ))}
                </div>
              </div>

              {/* Data Table */}
              <div className="overflow-x-auto">
                <table className="w-full text-left border-collapse">
                  <thead>
                    <tr className="bg-[#FBF9F8] border-y border-[#EAEAEA] text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
                      <th className="py-3 px-4 w-10 text-center">
                        <input
                          type="checkbox"
                          onChange={handleSelectAll}
                          checked={filteredItems.length > 0 && selectedIds.size === filteredItems.length}
                          className="w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer"
                        />
                      </th>
                      <th className="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                        <div className="flex items-center gap-1">
                          <span>SKU Code</span>
                          <svg className="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                        </div>
                      </th>
                      <th className="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                        <div className="flex items-center gap-1">
                          <span>Item & Batch</span>
                          <svg className="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                        </div>
                      </th>
                      <th className="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                        <div className="flex items-center gap-1">
                          <span>Category</span>
                          <svg className="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                        </div>
                      </th>
                      <th className="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                        <div className="flex items-center gap-1">
                          <span>Stock Bar</span>
                          <svg className="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                        </div>
                      </th>
                      <th className="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                        <div className="flex items-center gap-1">
                          <span>Expiry (FEFO)</span>
                          <svg className="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                        </div>
                      </th>
                      <th className="py-3 px-3 cursor-pointer hover:text-gray-800 transition-colors">
                        <div className="flex items-center gap-1">
                          <span>Status</span>
                          <svg className="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" /></svg>
                        </div>
                      </th>
                      <th className="py-3 px-3 text-center w-12" />
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#EAEAEA] text-xs">
                    {filteredItems.map((item) => {
                      const isChecked = selectedIds.has(item.id);
                      return (
                        <tr key={item.id} className="hover:bg-gray-50/70 transition-colors">
                          <td className="py-3.5 px-4 text-center">
                            <input
                              type="checkbox"
                              checked={isChecked}
                              onChange={() => handleSelectItem(item.id)}
                              className="w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer"
                            />
                          </td>
                          <td className="py-3.5 px-3 font-mono text-xs font-medium text-gray-700">{item.sku}</td>
                          <td className="py-3.5 px-3">
                            <div className="flex items-center gap-2.5">
                              <img
                                src={item.imageUrl}
                                alt={item.name}
                                className="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0"
                              />
                              <div>
                                <div className="font-semibold text-gray-900 leading-tight">{item.name}</div>
                                <span className="text-[11px] text-gray-400">Batch {item.batch}</span>
                              </div>
                            </div>
                          </td>
                          <td className="py-3.5 px-3 text-gray-600 font-medium">{item.category}</td>
                          <td className="py-3.5 px-3 min-w-[140px]">
                            <div className="flex items-center justify-between text-[11px] font-semibold text-gray-700 mb-1">
                              <div className="w-24 bg-gray-200 h-1.5 rounded-full overflow-hidden mr-2">
                                <div
                                  className={`h-full rounded-full ${
                                    item.stockBarColor === 'optimal'
                                      ? 'bg-[#28A745]'
                                      : item.stockBarColor === 'warning'
                                      ? 'bg-[#F59E0B]'
                                      : 'bg-[#DC143C]'
                                  }`}
                                  style={{ width: `${item.stockPercentage}%` }}
                                />
                              </div>
                              <span>{item.stockPercentage}%</span>
                            </div>
                            <div className="text-[10px] text-gray-400 font-mono">
                              {item.stockCurrent} / {item.stockMax}
                            </div>
                          </td>
                          <td className="py-3.5 px-3">
                            {item.expiryDate !== '-' ? (
                              <>
                                <div className="font-semibold text-[#DC143C]">{item.expiryDate}</div>
                                <div className="text-[10px] text-gray-400">{item.daysLeft} days left</div>
                              </>
                            ) : (
                              <>
                                <div className="font-semibold text-gray-400">-</div>
                                <div className="text-[10px] text-gray-400">Non-perishable</div>
                              </>
                            )}
                          </td>
                          <td className="py-3.5 px-3">
                            {item.status === 'Optimal' && (
                              <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#E8F8ED] text-[#16A34A]">
                                <span className="w-1.5 h-1.5 rounded-full bg-[#28A745]" />
                                Optimal
                              </span>
                            )}
                            {item.status === 'Low Stock' && (
                              <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FFF1F0] text-[#DC143C]">
                                <span className="w-1.5 h-1.5 rounded-full bg-[#DC143C]" />
                                Low Stock
                              </span>
                            )}
                            {item.status === 'Expiring Soon' && (
                              <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-[#FEF3C7] text-[#D97706]">
                                <span className="w-1.5 h-1.5 rounded-full bg-[#F59E0B]" />
                                Expiring Soon
                              </span>
                            )}
                          </td>
                          <td className="py-3.5 px-3 text-center">
                            <button type="button" className="text-gray-400 hover:text-gray-700 p-1 rounded hover:bg-gray-100">
                              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="1.5" />
                                <circle cx="6" cy="12" r="1.5" />
                                <circle cx="18" cy="12" r="1.5" />
                              </svg>
                            </button>
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>

              {/* Pagination Footer */}
              <div className="px-6 py-4 border-t border-[#EAEAEA] flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500">
                <div>
                  Showing <span className="font-semibold text-gray-800">1–10</span> of <span className="font-semibold text-gray-800">1,248</span> items
                </div>

                <div className="flex items-center gap-1">
                  <button type="button" className="w-7 h-7 rounded border border-gray-200 flex items-center justify-center text-gray-400 hover:bg-gray-50">
                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" /></svg>
                  </button>
                  <button type="button" className="w-7 h-7 rounded bg-[#DC143C] text-white font-semibold flex items-center justify-center shadow-xs">1</button>
                  <button type="button" className="w-7 h-7 rounded text-gray-700 hover:bg-gray-100 flex items-center justify-center">2</button>
                  <button type="button" className="w-7 h-7 rounded text-gray-700 hover:bg-gray-100 flex items-center justify-center">3</button>
                  <button type="button" className="w-7 h-7 rounded text-gray-700 hover:bg-gray-100 flex items-center justify-center">4</button>
                  <button type="button" className="w-7 h-7 rounded text-gray-700 hover:bg-gray-100 flex items-center justify-center">5</button>
                  <span className="px-1 text-gray-400">...</span>
                  <button type="button" className="w-7 h-7 rounded text-gray-700 hover:bg-gray-100 flex items-center justify-center">125</button>
                  <button type="button" className="w-7 h-7 rounded border border-gray-200 flex items-center justify-center text-gray-700 hover:bg-gray-50">
                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" /></svg>
                  </button>
                </div>

                <div className="flex items-center gap-2">
                  <span>Rows per page</span>
                  <div className="relative">
                    <select className="appearance-none bg-white border border-[#EAEAEA] rounded-lg px-2.5 py-1 pr-6 text-xs font-semibold text-gray-800 outline-none cursor-pointer focus:border-[#DC143C]">
                      <option>10</option>
                      <option>25</option>
                      <option>50</option>
                    </select>
                    <svg className="w-3 h-3 text-gray-400 absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                  </div>
                </div>
              </div>

            </section>

            {/* ======================================================== */}
            {/* 5. RIGHT SIDE: ANALYTICS & FEFO QUEUE (35% WIDTH)        */}
            {/* ======================================================== */}
            <aside className="xl:col-span-4 space-y-6">
              
              {/* 5.1 Stock Consumption Analytics Card */}
              <div className="bg-white border border-[#EAEAEA] rounded-xl p-5 shadow-[0_4px_12px_rgba(0,0,0,0.05)]">
                <div className="flex items-center justify-between pb-4 border-b border-[#EAEAEA]">
                  <h2 className="text-sm font-semibold font-poppins text-gray-900">Stock Consumption Analytics</h2>
                  <button type="button" className="flex items-center gap-1 px-2.5 py-1 bg-white border border-[#EAEAEA] rounded-lg text-xs font-medium text-gray-600 hover:border-gray-300">
                    <span>Last 7 Days</span>
                    <svg className="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" /></svg>
                  </button>
                </div>

                {/* Legends */}
                <div className="flex items-center gap-5 my-4 text-xs font-medium text-gray-600">
                  <div className="flex items-center gap-1.5">
                    <span className="w-2.5 h-2.5 rounded-full bg-[#DC143C]" />
                    <span>Ingredient Usage (kg)</span>
                  </div>
                  <div className="flex items-center gap-1.5">
                    <span className="w-2.5 h-2.5 rounded-full bg-[#708090]" />
                    <span>Waste / Discard (kg)</span>
                  </div>
                </div>

                {/* SVG Paired Bar Chart */}
                <div className="relative w-full pt-2">
                  {chartTooltip && (
                    <div
                      className="absolute bg-gray-900 text-white text-[11px] py-1 px-2.5 rounded shadow pointer-events-none z-10 font-mono transform -translate-x-1/2 -translate-y-full mb-1"
                      style={{ left: chartTooltip.x, top: chartTooltip.y }}
                    >
                      {chartTooltip.text}
                    </div>
                  )}

                  <svg className="w-full h-52 overflow-visible" viewBox="0 0 420 200">
                    {/* Y-Axis Gridlines */}
                    <g className="text-[10px] fill-gray-400" fontFamily="Inter, sans-serif">
                      <text x="0" y="24">800</text>
                      <line x1="28" y1="20" x2="415" y2="20" stroke="#F1F1F5" strokeDasharray="3,3" strokeWidth="1" />

                      <text x="0" y="64">600</text>
                      <line x1="28" y1="60" x2="415" y2="60" stroke="#F1F1F5" strokeDasharray="3,3" strokeWidth="1" />

                      <text x="0" y="104">400</text>
                      <line x1="28" y1="100" x2="415" y2="100" stroke="#F1F1F5" strokeDasharray="3,3" strokeWidth="1" />

                      <text x="0" y="144">200</text>
                      <line x1="28" y1="140" x2="415" y2="140" stroke="#F1F1F5" strokeDasharray="3,3" strokeWidth="1" />

                      <text x="12" y="180">0</text>
                      <line x1="28" y1="176" x2="415" y2="176" stroke="#EAEAEA" strokeWidth="1" />
                    </g>

                    {/* Bars Mapping */}
                    {CONSUMPTION_DATA.map((item, idx) => {
                      const xOffset = 42 + idx * 54;
                      // 800 corresponds to height 156
                      const usageH = Math.round((item.usageKg / 800) * 156);
                      const usageY = 176 - usageH;
                      const wasteH = Math.round((item.wasteKg / 800) * 156);
                      const wasteY = 176 - wasteH;

                      return (
                        <g
                          key={item.date}
                          className="cursor-pointer group"
                          onMouseEnter={(e) => {
                            const rect = e.currentTarget.getBoundingClientRect();
                            setChartTooltip({
                              text: `${item.date}: Usage ${item.usageKg}kg, Waste ${item.wasteKg}kg`,
                              x: xOffset + 14,
                              y: usageY,
                            });
                          }}
                          onMouseLeave={() => setChartTooltip(null)}
                        >
                          <rect
                            x={xOffset}
                            y={usageY}
                            width="13"
                            height={usageH}
                            rx="3"
                            ry="3"
                            fill="#DC143C"
                            className="group-hover:opacity-90 transition-opacity"
                          />
                          <rect
                            x={xOffset + 16}
                            y={wasteY}
                            width="13"
                            height={wasteH}
                            rx="3"
                            ry="3"
                            fill="#708090"
                            className="group-hover:opacity-90 transition-opacity"
                          />
                          <text
                            x={xOffset + 14}
                            y="193"
                            textAnchor="middle"
                            fontSize="10"
                            fill="#6B7280"
                            fontFamily="Inter, sans-serif"
                          >
                            {item.date}
                          </text>
                        </g>
                      );
                    })}
                  </svg>
                </div>
              </div>

              {/* 5.2 FEFO First-to-Expire Dispatch Queue Card */}
              <div className="bg-white border border-[#EAEAEA] rounded-xl p-5 shadow-[0_4px_12px_rgba(0,0,0,0.05)]">
                <div className="flex items-center justify-between">
                  <h2 className="text-sm font-semibold font-poppins text-gray-900">FEFO First-to-Expire Dispatch Queue</h2>
                  <button type="button" className="flex items-center gap-1 px-2.5 py-1 bg-white border border-[#EAEAEA] rounded-lg text-xs font-medium text-gray-600 hover:border-gray-300">
                    <span>Today</span>
                    <svg className="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" /></svg>
                  </button>
                </div>
                <p className="text-[11px] text-gray-400 mt-1 mb-4">Ingredients that should be used first based on expiry date.</p>

                {/* Queue List Items */}
                <div className="divide-y divide-[#EAEAEA]">
                  {FEFO_QUEUE.map((item) => (
                    <div
                      key={item.id}
                      className="py-3 flex items-center justify-between gap-3 group hover:bg-gray-50/60 px-1 rounded-lg transition-colors cursor-pointer"
                    >
                      <div className="flex items-center gap-2.5 min-w-0">
                        <input
                          type="checkbox"
                          className="w-4 h-4 rounded border-gray-300 text-[#DC143C] focus:ring-[#DC143C] cursor-pointer"
                        />
                        <img
                          src={item.imageUrl}
                          alt={item.name}
                          className="w-9 h-9 rounded-lg object-cover border border-gray-100 shrink-0"
                        />
                        <div className="truncate">
                          <div className="font-semibold text-xs text-gray-900 leading-tight">{item.name}</div>
                          <div className="text-[11px] text-gray-400 mt-0.5">
                            Batch {item.batch} · <span className="text-[#DC143C] font-semibold">{item.daysLeft} days left</span>
                          </div>
                        </div>
                      </div>
                      <div className="flex items-center gap-3 shrink-0">
                        <div className="text-right">
                          <div className="text-xs font-semibold text-gray-900 font-mono">{item.quantity}</div>
                          <span className="text-[10px] font-semibold text-[#DC143C]">{item.actionTag}</span>
                        </div>
                        <svg className="w-4 h-4 text-gray-300 group-hover:text-gray-500 transition-colors" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                      </div>
                    </div>
                  ))}
                </div>

                {/* Footer Action Link */}
                <div className="pt-4 border-t border-[#EAEAEA] mt-2">
                  <a href="#fefo-full" className="w-full inline-flex items-center justify-center gap-1.5 text-xs font-semibold text-gray-600 hover:text-[#DC143C] transition-colors py-1">
                    <span>View Full FEFO List</span>
                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" /></svg>
                  </a>
                </div>
              </div>

            </aside>

          </div>

        </main>
      </div>

      {/* Quick Stock In Modal */}
      {isStockInModalOpen && (
        <div className="fixed inset-0 z-50 bg-black/40 backdrop-blur-xs flex items-center justify-center p-4">
          <div className="bg-white rounded-xl border border-[#EAEAEA] shadow-[0_8px_24px_rgba(0,0,0,0.08)] w-full max-w-lg p-6 animate-in fade-in zoom-in-95 duration-150">
            <div className="flex items-center justify-between pb-3 border-b border-[#EAEAEA]">
              <h3 className="text-base font-semibold font-poppins text-gray-900 flex items-center gap-2">
                <span className="w-2 h-2 rounded-full bg-[#DC143C]" />
                Quick Stock In
              </h3>
              <button
                type="button"
                onClick={() => setIsStockInModalOpen(false)}
                className="text-gray-400 hover:text-gray-700"
              >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <form
              onSubmit={(e) => {
                e.preventDefault();
                alert('Stock In recorded successfully! Inventory levels and movements updated.');
                setIsStockInModalOpen(false);
              }}
              className="mt-4 space-y-4 text-xs"
            >
              <div>
                <label className="block font-semibold text-gray-700 mb-1">Select Item / SKU</label>
                <select className="w-full px-3 py-2 bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl outline-none focus:bg-white focus:border-[#DC143C]">
                  <option>SKU-BFF-042 — Daging Sapi Wagyu MB5</option>
                  <option>SKU-DRY-108 — Minyak Goreng Sawit 18L</option>
                  <option>SKU-FSH-019 — Susu UHT Full Cream 1L</option>
                  <option>SKU-FSH-027 — Tomat Merah Segar</option>
                  <option>SKU-FSH-031 — Selada Romaine</option>
                </select>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-gray-700 mb-1">Quantity</label>
                  <input
                    type="number"
                    placeholder="e.g. 50"
                    required
                    className="w-full px-3 py-2 bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl outline-none focus:bg-white focus:border-[#DC143C]"
                  />
                </div>
                <div>
                  <label className="block font-semibold text-gray-700 mb-1">Batch Number</label>
                  <input
                    type="text"
                    placeholder="e.g. #B93"
                    required
                    className="w-full px-3 py-2 bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl outline-none focus:bg-white focus:border-[#DC143C]"
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-gray-700 mb-1">Expiry Date (FEFO)</label>
                  <input
                    type="date"
                    required
                    className="w-full px-3 py-2 bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl outline-none focus:bg-white focus:border-[#DC143C]"
                  />
                </div>
                <div>
                  <label className="block font-semibold text-gray-700 mb-1">Supplier</label>
                  <input
                    type="text"
                    defaultValue="WRT / Main Kitchen"
                    className="w-full px-3 py-2 bg-[#F5F5FA] border border-[#EAEAEA] rounded-xl outline-none focus:bg-white focus:border-[#DC143C]"
                  />
                </div>
              </div>

              <div className="pt-4 border-t border-[#EAEAEA] flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setIsStockInModalOpen(false)}
                  className="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-[#DC143C] hover:bg-[#B1002C] text-white font-semibold rounded-xl shadow-sm transition-colors"
                >
                  Confirm Stock In
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default InventoryDashboard;
