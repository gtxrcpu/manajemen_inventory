# Implementation Plan: Purchase Invoice Form UI/UX Upgrade

## Task Summary

Upgrade `resources/views/purchase-invoices/form.blade.php` to match the design consistency of the purchasing module (request-show.blade.php pattern) and add Indonesian Rupiah currency formatting for numeric inputs.

**Objective:** Make the purchase invoice form visually consistent with the stat-card design pattern from purchasing/request-show.blade.php, add a document-info summary section above the form, format currency inputs with IDR notation, and improve overall visual hierarchy.

---

## Current Form Structure

The form is organized into two responsive sections (`mobile-content` and `desktop-content`):

### Mobile Section (< lg breakpoint)
- Simple header with back link and minimal styling
- Form fields in vertical stack (supplier, PO, invoice number, date, document total, file upload)
- Items section with basic grid layout (product select, qty, price in 2-column grid)
- Add-item button at section top
- Template-based dynamic item rows using IIFE

### Desktop Section (>= lg breakpoint)
- Header with back link, title, description
- Form fields in 2-column grid (supplier, PO, invoice, date in row 1; total, file in row 2)
- Items section with 4-column grid (product, quantity, unit_price, remove button)
- Add-item button styled as border pill
- Same template-based item cloning

### Current Issues
1. **No stat-card summary panel** — missing document info preview (supplier, PO, invoice #, date) above form
2. **Inconsistent styling** — borders (border-lg vs border-gray-100), shadows (none vs shadow-sm), paddings differ from request-show pattern
3. **No currency formatting** — document_total and unit_price fields are bare number inputs with no visual Rp notation
4. **Typography mismatch** — not using font-poppins for headings consistently
5. **Icon/button gaps** — save button lacks material-symbols-outlined icon and proper flex layout

---

## Design Requirements (from request-show.blade.php pattern)

### Colors & Styling
- Primary color: `#DC143C` (crimson, used in buttons)
- Borders: `border-gray-100` (not border-outline)
- Cards: `rounded-2xl border border-gray-100 bg-white p-5 shadow-sm` (not p-6 on form itself)
- Form card: `rounded-2xl border border-gray-100 bg-white p-6 shadow-sm`
- Input fields: `rounded-xl border p-3` with `text-sm font-medium` labels
- Mobile: consistent `px-4` padding, `space-y-4`

### Header Section
- Back link: `text-sm text-[#DC143C]` with arrow symbol
- Title: `font-poppins text-2xl font-semibold` (desktop) or `text-xl` (mobile)
- Description: `text-sm text-gray-500` (not secondary class; use gray-500)
- Status messages: `bg-green-50 text-green-800` border `border-green-200` for success; `bg-red-50` for errors

### Document-Info Summary Panel (NEW)
- **Desktop:** 4-column stat card grid (like KPI cards in request-show)
  - Each card: `rounded-2xl border border-gray-100 bg-white p-5 shadow-sm`
  - Each card displays: label (xs uppercase text-gray-500), value (text-lg font-bold text-gray-800), icon right-aligned
  - Cards: Supplier, PO (or "Tidak ditautkan"), Invoice Number, Invoice Date
  - Icons: supplier = "business", PO = "description", invoice = "receipt", date = "calendar_month" (material-symbols-outlined)

- **Mobile:** 2-column grid
  - Each card: `rounded-2xl bg-white border border-outline p-3.5`
  - Simplified: Supplier, Invoice Number (row 1); PO, Date (row 2)
  - Labels: `text-xs text-secondary`, values: `text-sm font-poppins font-bold`

### Form Card
- Desktop: `rounded-2xl border border-gray-100 bg-white p-6 shadow-sm` (existing, stays)
- Mobile: `rounded-2xl border bg-white p-4` (needs border-gray-100 if using color variables)

### Input Fields
- Label: `text-sm font-medium` (desktop) or `text-sm` (mobile)
- Input: `rounded-xl border p-3` (desktop) or `rounded-lg border p-2` (mobile)
- Border color: gray-100

### Buttons
- Primary save button: `flex items-center gap-2 rounded-xl bg-[#DC143C] px-5 py-3 font-semibold text-white`
- Icon: material-symbols-outlined "save_as_outlined" or "save"
- Desktop: positioned in `flex justify-end` wrapper
- Mobile: full width `w-full`

### Items Section
- Heading: `font-semibold` (desktop) or same
- Add button: desktop = `rounded-lg border px-3 py-2 text-sm font-semibold`, mobile = `text-sm font-semibold text-primary`
- Item row: 
  - Desktop: `grid grid-cols-[1fr_180px_220px_auto]` with `rounded-xl bg-slate-50 p-3`
  - Mobile: `grid grid-cols-1 gap-2 rounded-xl bg-slate-50 p-3`
- Remove button: `text-sm text-red-600`

### Subtotal Display (NEW)
- Below each item row, show formatted subtotal: "qty × Rp unit_price = Rp subtotal"
- Text: `text-xs text-gray-500`
- Format: Indonesian (thousands = '.', no decimals for whole rupiah)
- Display element, not form input; placed in label or div below row

---

## Currency Formatting Architecture

### JavaScript Pattern
- **Self-contained IIFE** (immediately invoked function expression) to avoid global scope pollution
- **Formatter function:** converts number to Indonesian Rupiah format (e.g., 1234567 → "Rp 1.234.567")
- **Initialization:** on DOM load, find all `[data-format="currency"]` elements and init display
- **Event delegation:** listen to `input` events on parent container; re-format display when number fields change
- **Template handling:** when `.clone()` or template row is inserted, formatter re-runs on new fields

### Input Field Pattern
```html
<label>Harga satuan
  <input type="number" name="items[0][unit_price]" data-currency-display="items[0][unit_price]_display" ...>
  <div data-currency-display="items[0][unit_price]_display" class="text-xs text-gray-500">Rp 0</div>
</label>
```

### Indonesian Number Format
- Thousands separator: `.` (titik)
- Decimal separator: `,` (koma) — but **whole rupiah has NO decimals** in display
- Example: 1234567.89 → display "Rp 1.234.568" (rounded, no decimals for currency display)
- All numeric inputs stay as `type="number"` (no decimals specified) for form submission; formatter is display-only

### Edge Cases Handled
1. **Edit mode:** form loads with pre-filled values (old('document_total', ...)); formatter runs on init to display existing values
2. **Dynamic rows:** when "Add Item" is clicked, template is cloned; new row's input gets unique name (items[next][...]) and formatter attaches to new display element
3. **Remove item:** formatter cleanup happens automatically since removed row's DOM is deleted; no orphaned listeners
4. **Empty/null values:** display "Rp 0" if field is empty
5. **Precision:** preserve submitted value precision for DB, only display is rounded

---

## Section-by-Section Changes

### 1. Mobile Header (mobile-content)
**Current:**
```
<a class="text-sm text-primary" href="{{ route(...) }}">← Faktur Pembelian</a>
<h1 class="mt-3 font-poppins text-xl font-semibold">...</h1>
<p class="mt-1 text-sm text-secondary">...</p>
```

**Changes:**
- Back link color: `text-sm text-[#DC143C]` (replace `text-primary`)
- Error message: `bg-red-50 text-red-700` (stays), add `border border-red-200`
- Add document-info stat cards (2-column grid) BEFORE form
- Form styling: ensure `border border-gray-100` (if using color var, check CSS)

### 2. Mobile Document-Info Stat Cards (NEW)
**Insert BEFORE form:**
```blade
<div class="grid grid-cols-2 gap-3">
  {{-- Supplier Card --}}
  <div class="rounded-2xl bg-white border border-outline p-3.5">
    <p class="text-xs text-secondary">Supplier</p>
    <p class="mt-1.5 font-poppins font-bold text-gray-800 text-sm">{{ $invoice?->supplier->name ?? '-' }}</p>
  </div>
  
  {{-- Invoice Number Card --}}
  <div class="rounded-2xl bg-white border border-outline p-3.5">
    <p class="text-xs text-secondary">Nomor Faktur</p>
    <p class="mt-1.5 font-poppins font-bold text-gray-800 text-sm">{{ old('invoice_number', $invoice?->invoice_number ?? '-') }}</p>
  </div>
  
  {{-- PO Card --}}
  <div class="rounded-2xl bg-white border border-outline p-3.5">
    <p class="text-xs text-secondary">PO</p>
    <p class="mt-1.5 font-poppins font-bold text-gray-800 text-sm">{{ $invoice?->order?->reference ?? 'Tidak ditautkan' }}</p>
  </div>
  
  {{-- Date Card --}}
  <div class="rounded-2xl bg-white border border-outline p-3.5">
    <p class="text-xs text-secondary">Tanggal Faktur</p>
    <p class="mt-1.5 font-poppins font-bold text-gray-800 text-sm">{{ old('invoice_date', $invoice?->invoice_date?->format('d M Y') ?? '-') }}</p>
  </div>
</div>
```

**Files:** `resources/views/purchase-invoices/form.blade.php` (mobile-content section)

### 3. Desktop Header (desktop-content)
**Current:**
```
<a class="text-sm text-[#DC143C]" href="...">← Faktur Pembelian</a>
<h1 class="font-poppins text-2xl font-semibold">...</h1>
<p class="mt-1 text-sm text-gray-500">...</p>
```

**Changes:**
- Header stays mostly the same (already uses #DC143C)
- Ensure description uses `text-gray-500` (check: is it using secondary class?)
- Add document-info stat cards (4-column grid) AFTER title, BEFORE form

### 4. Desktop Document-Info Stat Cards (NEW)
**Insert AFTER heading, BEFORE form card:**
```blade
<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
  {{-- Supplier Card --}}
  <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Supplier</p>
        <p class="mt-2 text-lg font-bold text-gray-800">{{ $invoice?->supplier->name ?? '-' }}</p>
      </div>
      <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 shrink-0">
        <span class="material-symbols-outlined text-[22px] text-blue-600">business</span>
      </div>
    </div>
  </div>
  
  {{-- PO Card --}}
  <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">PO</p>
        <p class="mt-2 text-lg font-bold text-gray-800">{{ $invoice?->order?->reference ?? 'Tidak ditautkan' }}</p>
      </div>
      <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 shrink-0">
        <span class="material-symbols-outlined text-[22px] text-indigo-600">description</span>
      </div>
    </div>
  </div>
  
  {{-- Invoice Number Card --}}
  <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nomor Faktur</p>
        <p class="mt-2 text-lg font-bold text-gray-800">{{ old('invoice_number', $invoice?->invoice_number ?? '-') }}</p>
      </div>
      <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 shrink-0">
        <span class="material-symbols-outlined text-[22px] text-amber-600">receipt</span>
      </div>
    </div>
  </div>
  
  {{-- Date Card --}}
  <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal Faktur</p>
        <p class="mt-2 text-lg font-bold text-gray-800">{{ old('invoice_date', $invoice?->invoice_date?->format('d M Y') ?? '-') }}</p>
      </div>
      <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-100 shrink-0">
        <span class="material-symbols-outlined text-[22px] text-green-600">calendar_month</span>
      </div>
    </div>
  </div>
</div>
```

**Files:** `resources/views/purchase-invoices/form.blade.php` (desktop-content section)

### 5. Mobile Form Card & Fields
**Current:**
- Form has `rounded-2xl border bg-white p-4`
- Fields have `mt-1 w-full rounded-lg border p-2`

**Changes:**
- Form card: add `border-gray-100` and `space-y-4` (already has)
- All input fields: change `rounded-lg p-2` → `rounded-xl p-3`
- Label text: ensure `text-sm` (already is)
- Error messages: add `border border-red-200` styling

**Files:** `resources/views/purchase-invoices/form.blade.php` (mobile-content section)

### 6. Mobile Document Total & Unit Price (Currency Format)
**Current:**
```blade
<label class="block text-sm">Total sesuai dokumen (Rp)
  <input type="number" name="document_total" ... class="mt-1 w-full rounded-lg border p-2">
</label>
```

**Changes:**
```blade
<label class="block text-sm">Total sesuai dokumen (Rp)
  <div class="mt-1 relative">
    <input type="number" name="document_total" 
           data-currency-input="document_total" 
           class="w-full rounded-xl border p-3" ...>
    <div data-currency-output="document_total" class="absolute top-3 left-3 pointer-events-none text-sm font-medium text-gray-700">Rp 0</div>
  </div>
</label>
```

- Layer formatted display on top of input using absolute positioning
- Display updates via JavaScript listener on number input

**Files:** `resources/views/purchase-invoices/form.blade.php` (mobile-content section, in form)

### 7. Mobile Items Section & Currency
**Current:**
```blade
<input type="number" name="items[{{ $i }}][unit_price]" min="0" step="0.01" 
       placeholder="Harga satuan" ... class="min-w-0 rounded-lg border p-2">
```

**Changes:**
- Add currency display below unit_price input
- Use label structure with formatted output div:
```blade
<div class="grid grid-cols-2 gap-2">
  <label class="block">
    <span class="text-xs text-gray-600">Kuantitas</span>
    <input type="number" data-field="quantity" data-currency-qty="items[{{ $i }}][quantity]" 
           class="mt-1 min-w-0 rounded-xl border p-3" ...>
  </label>
  <label class="block">
    <span class="text-xs text-gray-600">Harga satuan</span>
    <input type="number" data-field="unit_price" data-currency-price="items[{{ $i }}][unit_price]" 
           class="mt-1 min-w-0 rounded-xl border p-3" ...>
    <div data-currency-output="items[{{ $i }}][unit_price]" class="mt-1 text-xs text-gray-500">Rp 0</div>
  </label>
</div>
{{-- Subtotal Preview --}}
<div data-subtotal-row="items[{{ $i }}]" class="text-xs text-gray-500 mt-2">
  — × Rp 0 = Rp 0
</div>
```

**Files:** `resources/views/purchase-invoices/form.blade.php` (mobile item template & loop)

### 8. Desktop Form Card & Fields
**Current:**
- Form: `rounded-2xl border border-gray-100 bg-white p-6 shadow-sm` (already correct)
- Labels: `text-sm font-medium`
- Inputs: `rounded-xl border p-3` (already correct)

**Changes:**
- No styling changes needed (already matches); just verify border colors
- Add currency formatting overlays to document_total

**Files:** `resources/views/purchase-invoices/form.blade.php` (desktop-content section)

### 9. Desktop Document Total (Currency Format)
**Current:**
```blade
<label class="text-sm font-medium">Total sesuai dokumen (Rp)
  <input type="number" name="document_total" ... class="mt-1.5 w-full rounded-xl border p-3">
</label>
```

**Changes:**
```blade
<div class="text-sm font-medium">
  <label>Total sesuai dokumen (Rp)</label>
  <div class="mt-1.5 relative">
    <input type="number" name="document_total" 
           data-currency-input="document_total" 
           class="w-full rounded-xl border p-3" ...>
    <div data-currency-output="document_total" class="absolute top-3 left-3 pointer-events-none text-sm font-medium text-gray-700">Rp 0</div>
  </div>
</div>
```

**Files:** `resources/views/purchase-invoices/form.blade.php` (desktop-content section)

### 10. Desktop Items & Unit Price (Currency Format)
**Current:**
```blade
<label class="text-xs text-gray-600">Harga satuan
  <input type="number" name="items[{{ $i }}][unit_price]" ... class="mt-1 w-full rounded-lg border p-2.5">
</label>
```

**Changes:**
```blade
<label class="text-xs text-gray-600">
  Harga satuan
  <div class="mt-1 relative">
    <input type="number" data-field="unit_price" data-currency-price="items[{{ $i }}][unit_price]" 
           class="w-full rounded-xl border p-2.5" ...>
    <div data-currency-output="items[{{ $i }}][unit_price]" class="absolute top-2.5 left-2.5 pointer-events-none text-xs font-medium text-gray-700">Rp 0</div>
  </div>
</label>
```

- Add subtotal preview row below item (similar to mobile)

**Files:** `resources/views/purchase-invoices/form.blade.php` (desktop item template & loop)

### 11. Save Button (Mobile & Desktop)
**Current:**
```
<button class="w-full rounded-xl bg-[#DC143C] px-4 py-3 font-semibold text-white">Simpan sebagai draft</button>
```

**Changes (Mobile):**
```
<button class="w-full flex items-center justify-center gap-2 rounded-xl bg-[#DC143C] px-4 py-3 font-semibold text-white">
  <span class="material-symbols-outlined text-[20px]">save</span>
  Simpan sebagai draft
</button>
```

**Changes (Desktop):**
```
<div class="flex justify-end">
  <button class="flex items-center gap-2 rounded-xl bg-[#DC143C] px-5 py-3 font-semibold text-white">
    <span class="material-symbols-outlined text-[20px]">save</span>
    Simpan sebagai draft
  </button>
</div>
```

**Files:** `resources/views/purchase-invoices/form.blade.php` (both sections)

### 12. JavaScript Currency Formatter (NEW)
**Location:** `<script>` tag at end of desktop-content section (replaces existing IIFE, add currency logic)

**Functions:**
```javascript
(()=>{
  // ========== CURRENCY FORMATTER ==========
  
  const formatCurrency = (num) => {
    if (!num || isNaN(num)) return 'Rp 0';
    const n = Math.round(parseFloat(num)); // Round to whole rupiah
    const formatted = n.toLocaleString('id-ID'); // Indonesian formatting: . for thousands
    return `Rp ${formatted}`;
  };
  
  const initCurrencyFormatters = () => {
    // Find all currency output display elements
    document.querySelectorAll('[data-currency-output]').forEach(output => {
      const inputKey = output.dataset.currencyOutput;
      const input = document.querySelector(`input[data-currency-input="${inputKey}"], input[data-currency-price="${inputKey}"]`);
      if (input) {
        // Initialize display with current value
        output.textContent = formatCurrency(input.value);
        
        // Listen for changes
        input.addEventListener('input', (e) => {
          output.textContent = formatCurrency(e.target.value);
          
          // Update subtotal if this is a unit_price field
          updateSubtotal(inputKey);
        });
      }
    });
  };
  
  const updateSubtotal = (priceKey) => {
    // Extract row index from key like "items[0][unit_price]"
    const match = priceKey.match(/items\[(\d+)\]/);
    if (!match) return;
    const rowIdx = match[1];
    const qtyKey = `items[${rowIdx}][quantity]`;
    const priceDisplay = formatCurrency(document.querySelector(`input[data-currency-price="${priceKey}"]`)?.value);
    const qtyInput = document.querySelector(`input[data-currency-qty="${qtyKey}"]`);
    const priceInput = document.querySelector(`input[data-currency-price="${priceKey}"]`);
    
    if (qtyInput && priceInput) {
      const qty = qtyInput.value || 0;
      const price = parseFloat(priceInput.value) || 0;
      const subtotal = Math.round(qty * price);
      const subtotalOutput = document.querySelector(`div[data-subtotal-row="items[${rowIdx}]"]`);
      if (subtotalOutput) {
        subtotalOutput.textContent = `${qty || '—'} × ${priceDisplay} = Rp ${subtotal.toLocaleString('id-ID')}`;
      }
    }
  };
  
  // ========== ITEM ADD/REMOVE ==========
  
  const list = document.getElementById('items-desktop');
  const template = document.getElementById('item-template-desktop');
  const add = document.getElementById('add-item-desktop');
  let next = {{ count(old('items', $invoice?->items?->toArray() ?? [[]])) }};
  
  add?.addEventListener('click', () => {
    const node = template.content.cloneNode(true);
    node.querySelectorAll('[data-field]').forEach(el => {
      el.name = `items[${next}][${el.dataset.field}]`;
      
      // Set data attributes for currency formatting
      if (el.dataset.field === 'unit_price') {
        el.dataset.currencyPrice = `items[${next}][unit_price]`;
        el.dataset.currencyQty = `items[${next}][quantity]`;
      } else if (el.dataset.field === 'quantity') {
        el.dataset.currencyQty = `items[${next}][quantity]`;
      }
    });
    
    // Update data attributes in output divs
    node.querySelectorAll('[data-currency-output]').forEach(el => {
      el.dataset.currencyOutput = el.dataset.currencyOutput.replace(/items\[\d+\]/, `items[${next}]`);
    });
    node.querySelectorAll('[data-subtotal-row]').forEach(el => {
      el.dataset.subtotalRow = `items[${next}]`;
    });
    
    next++;
    list.append(node);
    
    // Re-init currency formatters for new row
    initCurrencyFormatters();
  });
  
  list?.addEventListener('click', e => {
    if (e.target.closest('.remove-item') && list.querySelectorAll('.item-row').length > 1) {
      e.target.closest('.item-row').remove();
    }
  });
  
  // Initialize on load
  initCurrencyFormatters();
})();
```

**Files:** `resources/views/purchase-invoices/form.blade.php` (desktop-content script section)

### 13. Update Mobile Script (Currency Support)
**Current:** Separate IIFE for mobile items only

**Changes:** Keep mobile IIFE for add/remove logic, but integrate currency formatting similar to desktop

**Files:** `resources/views/purchase-invoices/form.blade.php` (mobile-content script section)

---

## Verification Strategy

After implementation:

1. **Manual testing (development server):**
   - Run Laravel dev server: `php artisan serve`
   - Run Vite in watch mode: `npm run dev`
   - Navigate to `http://127.0.0.1:8000/purchase-invoices/create` (create mode, mobile & desktop)
   - Navigate to `http://127.0.0.1:8000/purchase-invoices/{id}/edit` (edit mode with pre-filled data)

2. **Visual verification:**
   - **Mobile (<lg):** Stat cards (2×2 grid), form fields rounded-xl, save button with icon, currency display visible
   - **Desktop (≥lg):** Stat cards (4-column grid), form field layout, subtotal preview per item
   - **Colors:** #DC143C buttons, gray-100 borders, gray-500 text for descriptions

3. **Currency formatting:**
   - Edit invoice with document_total = 1500000 → display shows "Rp 1.500.000"
   - Add item with unit_price = 25000, qty = 5 → subtotal shows "5 × Rp 25.000 = Rp 125.000"
   - Edit mode: refresh page, pre-filled values format correctly on load
   - Add new item dynamically, enter price, currency display appears immediately

4. **Form submission:**
   - Save form (both create & edit modes) → no errors, data commits to DB
   - Verify input values (before formatting) are still submitted correctly (e.g., 1500000, not "Rp 1.500.000")

5. **Responsiveness:**
   - Test on actual mobile device or Chrome DevTools mobile view
   - Verify no horizontal scroll, proper alignment, icon visibility

---

## Implementation Notes

### Dependencies
- **Tailwind CSS v4**: Already configured in project (app.css uses @theme); no additional setup needed
- **Material Symbols Outlined**: Already used throughout project; no new dependency
- **JavaScript:** Vanilla ES6 IIFE; no external library required

### Browser Compatibility
- Formatter uses `Number.prototype.toLocaleString('id-ID')` — supported in all modern browsers
- CSS grid, flexbox, absolute positioning — all standard CSS3

### Testing Approach
- No automated tests required (this is a UI/UX upgrade to an existing form)
- Manual browser testing is primary verification
- Follow Laravel conventions: form submission via POST/PUT, validation on server-side

### Rollback Risk
- Low risk: pure front-end CSS/HTML/JS changes, no database migrations or API changes
- Form submission logic unchanged; only display and layout modified
- Can revert single file if needed

---

## Edge Cases Documented

| Scenario | Expected Behavior |
|----------|-------------------|
| Form load in edit mode with pre-filled values | Currency display shows formatted value on page load |
| User manually enters number in field | Display updates in real-time as user types |
| User adds new item dynamically | Newly cloned row's currency displays are initialized and functional |
| User removes an item | Display elements removed with row; no orphaned listeners |
| User tries to add item when only 1 item exists | Add button allows (no restriction); display still works |
| User enters invalid/empty unit price | Display shows "Rp 0"; form still submits (server validates) |
| Rapid clicking of Add button | Each new row gets unique incrementing index; no collisions |
| Very large numbers (e.g., 999999999) | Formatted correctly with Indonesian separator (titik) |
| Mobile to desktop orientation change | Layout responds correctly; formatters continue to work |

---

## File Summary

**Single file to modify:**
- `resources/views/purchase-invoices/form.blade.php`

**Changes breakdown:**
- Mobile section: header colors, document-info stat cards (2×2), form styling, currency overlays on inputs, item displays with subtotal, button with icon
- Desktop section: document-info stat cards (4-column), currency overlays on inputs, item displays with subtotal, button with icon, enhanced JavaScript formatter
- No new files created; no migrations, no controllers, no models

**Total scope:** ~400 lines of Blade/HTML/CSS/JS changes in single file

---

## Implementation Order

1. Add mobile document-info stat cards (4 cards with styling)
2. Add desktop document-info stat cards (4 cards with styling & icons)
3. Update mobile form field styling (rounded-xl, p-3)
4. Update desktop form field styling (verify already correct)
5. Add currency display overlays to document_total (mobile & desktop)
6. Add currency display overlays to unit_price in items (mobile & desktop)
7. Add subtotal preview rows to items (mobile & desktop)
8. Update JavaScript: extract formatter function, init on load, handle dynamic rows, update subtotal calculations
9. Update mobile script with currency integration
10. Update save buttons to include icon (mobile & desktop)
11. Test: create mode mobile/desktop, edit mode mobile/desktop, dynamic add/remove, currency formatting, form submission

