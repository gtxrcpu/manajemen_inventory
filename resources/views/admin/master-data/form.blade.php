@extends('layouts.dashboard')

@php
    $editing = (bool) $item;
    $pageTitle = ($editing ? 'Edit ' : 'Tambah ') . $config['label'] . ' — Technolife';
    $fields = match($resource) {
        'products'   => [['sku','SKU','text',true],['name','Nama Produk','text',true],['category_id','Kategori','select',true],['unit_id','Satuan','select',true],['description','Deskripsi','textarea',false]],
        'categories' => [['name','Nama Kategori','text',true],['description','Deskripsi','textarea',false]],
        'units'      => [['name','Nama Satuan','text',true],['symbol','Simbol','text',true],['description','Deskripsi','textarea',false]],
        'suppliers'  => [['name','Nama Supplier','text',true],['contact_person','Nama Kontak','text',false],['email','Email','email',false],['phone','Telepon','text',false],['address','Alamat','textarea',false],['notes','Catatan','textarea',false]],
        'locations'  => [['code','Kode Lokasi','text',true],['name','Nama Lokasi','text',true],['type','Tipe','type_select',true],['address','Alamat','textarea',false],['description','Deskripsi','textarea',false]],
    };
    $backUrl = $editing
        ? route('admin.master-data.show', [$resource, $item->id])
        : route('admin.master-data.index', $resource);
    $formAction = $editing
        ? route('admin.master-data.update', [$resource, $item->id])
        : route('admin.master-data.store', $resource);
    // Field descriptions for helper text
    $hints = [
        'sku'            => 'Kode unik produk, contoh: SKU-BFF-042',
        'symbol'         => 'Singkatan satuan, contoh: kg, pcs, ltr',
        'code'           => 'Kode singkat lokasi, contoh: GDG-01',
        'contact_person' => 'Nama PIC yang bisa dihubungi',
        'type'           => 'Gudang untuk penyimpanan stok, Outlet untuk titik penjualan',
    ];
@endphp

{{-- ======================================================================= --}}
{{-- MOBILE CONTENT                                                           --}}
{{-- ======================================================================= --}}
@section('mobile-content')
<div class="flex flex-col w-full p-4 space-y-4">

    <!-- Back + Header -->
    <div>
        <a href="{{ $backUrl }}" class="inline-flex items-center gap-1 text-sm font-semibold text-primary mb-3">
            <span class="material-symbols-outlined text-[18px]">arrow_back</span>Kembali
        </a>
        <h1 class="font-poppins text-xl font-semibold text-on-surface">
            {{ $editing ? 'Edit' : 'Tambah' }} {{ $config['label'] }}
        </h1>
        <p class="text-xs text-secondary mt-0.5">
            {{ $editing ? 'Ubah informasi data yang sudah ada.' : 'Isi form berikut untuk menambah data baru.' }}
        </p>
    </div>

    <!-- Validation errors summary -->
    @if($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 p-3 flex items-start gap-2.5">
        <span class="material-symbols-outlined text-red-500 text-[18px] shrink-0 mt-0.5">error</span>
        <div>
            <p class="text-xs font-semibold text-red-800 mb-1">Periksa kembali inputan:</p>
            <ul class="text-xs text-red-700 space-y-0.5 list-disc list-inside">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white rounded-2xl border border-outline shadow-xs overflow-hidden">
        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="p-4 space-y-4">
            @csrf
            @if($editing) @method('PUT') @endif

            @foreach($fields as [$field, $label, $type, $required])
            <div class="flex flex-col gap-1.5">
                <label for="{{ $field }}_m" class="text-sm font-semibold text-on-surface">
                    {{ $label }}
                    @if($required)<span class="text-primary">*</span>@endif
                </label>

                @if($type === 'select')
                    @php $optKey = $field === 'category_id' ? 'categories' : 'units'; @endphp
                    <div class="relative">
                        <select id="{{ $field }}_m" name="{{ $field }}" {{ $required ? 'required' : '' }}
                            class="w-full h-11 rounded-xl border @error($field) border-red-400 bg-red-50 @else border-outline @enderror pl-3 pr-9 text-sm text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all bg-white appearance-none">
                            <option value="">Pilih {{ strtolower($label) }}...</option>
                            @foreach($options[$optKey] as $opt)
                                <option value="{{ $opt->id }}" @selected(old($field, $item?->$field) == $opt->id)>
                                    {{ $field === 'unit_id' ? $opt->name.' ('.$opt->symbol.')' : $opt->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-secondary">
                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                        </div>
                    </div>

                @elseif($type === 'type_select')
                    <div class="relative">
                        <select id="{{ $field }}_m" name="type" required
                            class="w-full h-11 rounded-xl border @error('type') border-red-400 bg-red-50 @else border-outline @enderror pl-3 pr-9 text-sm text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all bg-white appearance-none">
                            <option value="warehouse" @selected(old('type', $item?->type) === 'warehouse')>Gudang (Warehouse)</option>
                            <option value="outlet"    @selected(old('type', $item?->type) === 'outlet')>Outlet</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-secondary">
                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                        </div>
                    </div>

                @elseif($type === 'textarea')
                    <textarea id="{{ $field }}_m" name="{{ $field }}" rows="3"
                        class="w-full rounded-xl border @error($field) border-red-400 bg-red-50 @else border-outline @enderror px-3 py-2.5 text-sm text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all resize-none">{{ old($field, $item?->$field) }}</textarea>

                @else
                    <input id="{{ $field }}_m" name="{{ $field }}" type="{{ $type }}"
                        value="{{ old($field, $item?->$field) }}"
                        {{ $required ? 'required' : '' }}
                        class="w-full h-11 rounded-xl border @error($field) border-red-400 bg-red-50 @else border-outline @enderror px-3 text-sm text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none transition-all">
                @endif

                @if(isset($hints[$field]))
                    <p class="text-[11px] text-secondary">{{ $hints[$field] }}</p>
                @endif
                @error($field)
                    <p class="text-xs text-red-600 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[13px]">error</span>{{ $message }}
                    </p>
                @enderror
            </div>
            @endforeach

            @if($resource === 'products')
            <div class="flex flex-col gap-2">
                <label for="image_m" class="text-sm font-semibold text-on-surface">Foto Produk</label>
                @if($item?->image_path)<img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->name }}" class="h-28 w-28 rounded-xl object-cover">@endif
                <input id="image_m" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="w-full text-sm" />
                <p class="text-[11px] text-secondary">JPG, PNG, atau WebP. Maksimal 5 MB. Otomatis dikonversi ke WebP (maks. 1 MB).</p>
                @error('image')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            @endif

            <!-- Status toggle -->
            <div class="flex items-center justify-between p-3.5 rounded-xl bg-surface-container-lowest border border-outline">
                <div>
                    <p class="text-sm font-semibold text-on-surface">Status Aktif</p>
                    <p class="text-xs text-secondary">Data tidak aktif tidak akan muncul di pilihan</p>
                </div>
                <input type="hidden" name="is_active" value="0">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                        @checked((bool) old('is_active', $item?->is_active ?? true))>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary/20 rounded-full peer peer-checked:bg-primary transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                </label>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-1">
                <button type="submit" class="flex-1 h-11 bg-primary text-on-primary text-sm font-semibold rounded-xl flex items-center justify-center gap-2 active:scale-[0.98] transition-transform shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    {{ $editing ? 'Simpan Perubahan' : 'Tambah '.$config['label'] }}
                </button>
                <a href="{{ $backUrl }}" class="h-11 px-5 border border-outline text-on-surface text-sm font-semibold rounded-xl flex items-center justify-center hover:bg-surface-container-low transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>

</div>
@endsection

{{-- ======================================================================= --}}
{{-- DESKTOP CONTENT                                                          --}}
{{-- ======================================================================= --}}
@section('desktop-content')

<div class="max-w-2xl mx-auto space-y-6">

    <!-- Breadcrumb + Header -->
    <div>
        <nav class="flex items-center gap-1.5 text-xs text-gray-400 mb-4">
            <a href="{{ route('admin.master-data.index', $resource) }}" class="hover:text-[#DC143C] transition-colors font-medium">Master Data</a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <a href="{{ route('admin.master-data.index', $resource) }}" class="hover:text-[#DC143C] transition-colors">{{ $config['label'] }}</a>
            <span class="material-symbols-outlined text-[14px]">chevron_right</span>
            <span class="text-gray-600 font-medium">{{ $editing ? 'Edit' : 'Tambah Baru' }}</span>
        </nav>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="font-poppins text-2xl font-semibold text-gray-900 tracking-tight">
                    {{ $editing ? 'Edit' : 'Tambah' }} {{ $config['label'] }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $editing ? 'Ubah informasi data yang sudah ada.' : 'Isi semua field bertanda (*) untuk membuat data baru.' }}
                </p>
            </div>
            <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 px-3 py-2 border border-[#EAEAEA] rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors shrink-0">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>Kembali
            </a>
        </div>
    </div>

    <!-- Validation errors -->
    @if($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 flex items-start gap-3">
        <span class="material-symbols-outlined text-red-500 text-[20px] shrink-0 mt-0.5">error</span>
        <div>
            <p class="text-sm font-semibold text-red-800 mb-1.5">Terdapat {{ $errors->count() }} kesalahan input:</p>
            <ul class="text-sm text-red-700 space-y-0.5 list-disc list-inside">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white border border-[#EAEAEA] rounded-xl shadow-[0_4px_12px_rgba(0,0,0,0.05)] overflow-hidden">

        <div class="px-6 py-4 border-b border-[#EAEAEA] flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-[#FFF1F0] flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-[#DC143C] text-[18px]">
                    {{ match($resource) {
                        'products'   => 'inventory_2',
                        'categories' => 'category',
                        'units'      => 'straighten',
                        'suppliers'  => 'local_shipping',
                        'locations'  => 'location_on',
                        default      => 'edit_note',
                    } }}
                </span>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-gray-900">
                    {{ $editing ? 'Edit data' : 'Data baru' }} — {{ $config['label'] }}
                </h2>
                <p class="text-xs text-gray-400">Field bertanda <span class="text-[#DC143C]">*</span> wajib diisi</p>
            </div>
        </div>

        <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="px-6 py-5 space-y-5">
            @csrf
            @if($editing) @method('PUT') @endif

            @foreach($fields as [$field, $label, $type, $required])
            <div class="flex flex-col gap-1.5">
                <label for="{{ $field }}_d" class="text-sm font-semibold text-gray-700">
                    {{ $label }}
                    @if($required)<span class="text-[#DC143C]">*</span>@endif
                </label>

                @if($type === 'select')
                    @php $optKey = $field === 'category_id' ? 'categories' : 'units'; @endphp
                    <div class="relative">
                        <select id="{{ $field }}_d" name="{{ $field }}" {{ $required ? 'required' : '' }}
                            class="w-full h-10 rounded-lg border @error($field) border-red-400 bg-red-50 @else border-[#EAEAEA] bg-[#F5F5FA] @enderror pl-3 pr-9 text-sm text-gray-900 focus:bg-white focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all appearance-none cursor-pointer">
                            <option value="">Pilih {{ strtolower($label) }}...</option>
                            @foreach($options[$optKey] as $opt)
                                <option value="{{ $opt->id }}" @selected(old($field, $item?->$field) == $opt->id)>
                                    {{ $field === 'unit_id' ? $opt->name.' ('.$opt->symbol.')' : $opt->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                        </div>
                    </div>

                @elseif($type === 'type_select')
                    <div class="relative">
                        <select id="{{ $field }}_d" name="type" required
                            class="w-full h-10 rounded-lg border @error('type') border-red-400 bg-red-50 @else border-[#EAEAEA] bg-[#F5F5FA] @enderror pl-3 pr-9 text-sm text-gray-900 focus:bg-white focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all appearance-none cursor-pointer">
                            <option value="warehouse" @selected(old('type', $item?->type) === 'warehouse')>Gudang (Warehouse)</option>
                            <option value="outlet"    @selected(old('type', $item?->type) === 'outlet')>Outlet</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                            <span class="material-symbols-outlined text-[18px]">expand_more</span>
                        </div>
                    </div>

                @elseif($type === 'textarea')
                    <textarea id="{{ $field }}_d" name="{{ $field }}" rows="3"
                        class="w-full rounded-lg border @error($field) border-red-400 bg-red-50 @else border-[#EAEAEA] bg-[#F5F5FA] @enderror px-3 py-2.5 text-sm text-gray-900 focus:bg-white focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all resize-none placeholder:text-gray-400">{{ old($field, $item?->$field) }}</textarea>

                @else
                    <input id="{{ $field }}_d" name="{{ $field }}" type="{{ $type }}"
                        value="{{ old($field, $item?->$field) }}"
                        {{ $required ? 'required' : '' }}
                        class="w-full h-10 rounded-lg border @error($field) border-red-400 bg-red-50 @else border-[#EAEAEA] bg-[#F5F5FA] @enderror px-3 text-sm text-gray-900 focus:bg-white focus:border-[#DC143C] focus:ring-1 focus:ring-[#DC143C]/20 outline-none transition-all placeholder:text-gray-400">
                @endif

                @if(isset($hints[$field]))
                    <p class="text-xs text-gray-400">{{ $hints[$field] }}</p>
                @endif
                @error($field)
                    <p class="text-xs text-red-600 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[13px]">error</span>{{ $message }}
                    </p>
                @enderror
            </div>
            @endforeach

            @if($resource === 'products')
            <div class="flex flex-col gap-2">
                <label for="image_d" class="text-sm font-semibold text-gray-700">Foto Produk</label>
                @if($item?->image_path)<img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->name }}" class="h-32 w-32 rounded-xl object-cover border border-[#EAEAEA]">@endif
                <input id="image_d" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="w-full rounded-lg border border-[#EAEAEA] bg-[#F5F5FA] p-2 text-sm" />
                <p class="text-xs text-gray-400">JPG, PNG, atau WebP. Maksimal 5 MB. Otomatis dikonversi ke WebP (maks. 1 MB).</p>
                @error('image')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            @endif

            <!-- Divider -->
            <div class="border-t border-[#EAEAEA] pt-5">
                <!-- Status toggle -->
                <div class="flex items-center justify-between p-4 rounded-xl bg-[#FBF9F8] border border-[#EAEAEA]">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Status Aktif</p>
                        <p class="text-xs text-gray-400 mt-0.5">Data tidak aktif tidak akan muncul di pilihan form</p>
                    </div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                            @checked((bool) old('is_active', $item?->is_active ?? true))>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-[#DC143C]/20 rounded-full peer peer-checked:bg-[#DC143C] transition-colors after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                    </label>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between gap-3 pt-1">
                <a href="{{ $backUrl }}" class="px-4 py-2.5 border border-[#EAEAEA] rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                    Batal
                </a>
                <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-[#DC143C] text-white text-sm font-semibold rounded-lg hover:bg-[#c01230] transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">save</span>
                    {{ $editing ? 'Simpan Perubahan' : 'Tambah '.$config['label'] }}
                </button>
            </div>

        </form>
    </div>

    @if($editing)
    <!-- Danger Zone -->
    <div class="bg-white border border-red-100 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-red-100 bg-red-50/50">
            <h3 class="text-sm font-semibold text-red-700">Zona Berbahaya</h3>
            <p class="text-xs text-red-500 mt-0.5">Tindakan ini tidak dapat dibatalkan</p>
        </div>
        <div class="px-6 py-4 flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-gray-700">Hapus {{ $config['label'] }} ini</p>
                <p class="text-xs text-gray-400 mt-0.5">Data akan dihapus permanen dari sistem</p>
            </div>
            <form method="POST" action="{{ route('admin.master-data.destroy', [$resource, $item->id]) }}"
                  onsubmit="return confirm('Hapus {{ $config['label'] }} ini secara permanen?')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-white border border-red-200 text-red-600 text-sm font-semibold rounded-lg hover:bg-red-50 transition-colors">
                    <span class="material-symbols-outlined text-[16px]">delete</span>Hapus
                </button>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection

@section('page-scripts')
    // Auto-resize textareas
    document.querySelectorAll('textarea').forEach(ta => {
        ta.addEventListener('input', () => {
            ta.style.height = 'auto';
            ta.style.height = ta.scrollHeight + 'px';
        });
    });

    // Auto-fill symbol from unit name (units resource only)
    @if($resource === 'units')
    (function() {
        // Common unit name → symbol map
        const symbolMap = {
            'kilogram': 'kg', 'gram': 'g', 'miligram': 'mg', 'ton': 'ton',
            'liter': 'L', 'mililiter': 'mL', 'ml': 'mL',
            'meter': 'm', 'sentimeter': 'cm', 'milimeter': 'mm',
            'pcs': 'pcs', 'pieces': 'pcs', 'buah': 'bh', 'lembar': 'lbr',
            'botol': 'btl', 'kaleng': 'klg', 'can': 'can', 'dus': 'dus',
            'karton': 'ktn', 'pack': 'pack', 'pak': 'pak', 'sachet': 'sct',
            'porsi': 'prs', 'ikat': 'ikt', 'roll': 'roll', 'lusin': 'lsn',
        };

        const nameInput   = document.getElementById('name_d') || document.getElementById('name_m');
        const symbolInput = document.getElementById('symbol_d') || document.getElementById('symbol_m');
        if (!nameInput || !symbolInput) return;

        // Track whether user has manually edited symbol
        let userEditedSymbol = symbolInput.value.length > 0;
        symbolInput.addEventListener('input', () => { userEditedSymbol = true; });

        nameInput.addEventListener('input', function() {
            if (userEditedSymbol) return;
            const raw   = this.value.trim().toLowerCase();
            const match = symbolMap[raw];
            if (match) {
                symbolInput.value = match;
            } else {
                // Fallback: take first 4 chars, lowercase, strip spaces
                symbolInput.value = raw.replace(/\s+/g, '').substring(0, 4);
            }
        });
    })();
    @endif
@endsection
