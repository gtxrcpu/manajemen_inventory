<?php

namespace App\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\MasterDataService;
use App\Services\ProductImageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class MasterDataController
{
    private const RESOURCES = [
        'products' => ['label' => 'Produk', 'model' => Product::class],
        'categories' => ['label' => 'Kategori', 'model' => Category::class],
        'units' => ['label' => 'Satuan', 'model' => Unit::class],
        'suppliers' => ['label' => 'Supplier', 'model' => Supplier::class],
        'locations' => ['label' => 'Gudang & Outlet', 'model' => Location::class],
    ];

    public function index(Request $request, string $resource)
    {
        $config = $this->config($resource);
        $model = $config['model'];
        $query = $model::query();
        if ($resource === 'products') {
            $query->with(['category', 'unit']);
        }
        $term = trim((string) $request->query('q', ''));
        if ($term !== '') {
            $columns = match ($resource) {
                'products' => ['sku', 'name'], 'categories', 'units', 'suppliers' => ['name'],
                'locations' => ['code', 'name'],
            };
            $query->where(fn ($q) => collect($columns)->each(fn ($column) => $q->orWhere($column, 'like', "%{$term}%")));
        }
        if ($request->query('status') === 'active') {
            $query->where('is_active', true);
        }
        if ($request->query('status') === 'inactive') {
            $query->where('is_active', false);
        }
        if ($resource === 'locations' && in_array($request->query('type'), ['warehouse', 'outlet'], true)) {
            $query->where('type', $request->query('type'));
        }

        return view('admin.master-data.index', ['resource' => $resource, 'config' => $config, 'items' => $query->orderBy('name')->paginate(15)->withQueryString(), 'q' => $term]);
    }

    public function importProducts(Request $request, MasterDataService $service)
    {
        $validated = $request->validate(['rows' => ['required', 'string', 'max:5000000']]);
        $rows = json_decode($validated['rows'], true);
        if (! is_array($rows) || ! isset($rows[0]) || ! is_array($rows[0]) || count($rows) < 2 || count($rows) > 1001) {
            return back()->withErrors(['workbook' => 'File harus berisi header dan maksimal 1.000 baris produk.']);
        }

        $headerAliases = [
            'sku' => 'sku', 'nama_produk' => 'name', 'nama' => 'name',
            'kategori' => 'category', 'satuan' => 'unit', 'deskripsi' => 'description',
            'lacak_kedaluwarsa' => 'expiry', 'aktif' => 'active',
        ];
        $columns = [];
        foreach ($rows[0] as $index => $heading) {
            $normalized = Str::of((string) $heading)->trim()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
            if (isset($headerAliases[$normalized])) $columns[$headerAliases[$normalized]] = $index;
        }
        foreach (['sku', 'name', 'category', 'unit'] as $requiredColumn) {
            if (! array_key_exists($requiredColumn, $columns)) {
                return back()->withErrors(['workbook' => 'Kolom wajib tidak lengkap. Gunakan template impor produk yang disediakan.']);
            }
        }

        $categories = Category::query()->where('is_active', true)->get(['id', 'name'])
            ->keyBy(fn (Category $category) => mb_strtolower(trim($category->name)));
        $units = Unit::query()->where('is_active', true)->get(['id', 'name', 'symbol']);
        $unitLookup = [];
        foreach ($units as $unit) {
            $unitLookup[mb_strtolower(trim($unit->name))] ??= $unit;
            $unitLookup[mb_strtolower(trim($unit->symbol))] ??= $unit;
        }

        $imported = 0;
        $errors = [];
        $seenSkus = [];
        foreach (array_slice($rows, 1) as $offset => $cells) {
            if (! is_array($cells) || collect($cells)->every(fn ($cell) => trim((string) $cell) === '')) continue;
            $line = $offset + 2;
            $value = fn (string $key) => trim((string) ($cells[$columns[$key]] ?? ''));
            $fields = [
                'sku' => $value('sku'), 'name' => $value('name'), 'category' => $value('category'),
                'unit' => $value('unit'), 'description' => isset($columns['description']) ? $value('description') : null,
            ];
            $rowValidator = Validator::make($fields, [
                'sku' => ['required', 'string', 'max:64'], 'name' => ['required', 'string', 'max:255'],
                'category' => ['required', 'string', 'max:255'], 'unit' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
            ]);
            if ($rowValidator->fails()) {
                $errors[] = 'Baris '.$line.': '.$rowValidator->errors()->first();
                continue;
            }

            $skuKey = mb_strtolower($fields['sku']);
            if (isset($seenSkus[$skuKey]) || Product::withTrashed()->where('sku', $fields['sku'])->exists()) {
                $errors[] = 'Baris '.$line.': SKU '.$fields['sku'].' sudah digunakan atau berulang di file.';
                continue;
            }
            $category = $categories->get(mb_strtolower($fields['category']));
            if (! $category) {
                $errors[] = 'Baris '.$line.': kategori "'.$fields['category'].'" tidak ditemukan atau nonaktif.';
                continue;
            }
            $unit = $unitLookup[mb_strtolower($fields['unit'])] ?? null;
            if (! $unit) {
                $errors[] = 'Baris '.$line.': satuan "'.$fields['unit'].'" tidak ditemukan atau nonaktif.';
                continue;
            }

            $booleanValue = function (string $key, bool $default) use ($columns, $cells): ?bool {
                if (! isset($columns[$key])) return $default;
                $raw = mb_strtolower(trim((string) ($cells[$columns[$key]] ?? '')));
                if ($raw === '') return $default;
                return match ($raw) {
                    'ya', 'yes', 'true', '1', 'aktif' => true,
                    'tidak', 'no', 'false', '0', 'nonaktif' => false,
                    default => null,
                };
            };
            $requiresExpiry = $booleanValue('expiry', false);
            $isActive = $booleanValue('active', true);
            if ($requiresExpiry === null || $isActive === null) {
                $errors[] = 'Baris '.$line.': isi Lacak Kedaluwarsa dan Aktif dengan Ya/Tidak.';
                continue;
            }

            $service->create(Product::class, [
                'sku' => $fields['sku'], 'name' => $fields['name'], 'category_id' => $category->id,
                'unit_id' => $unit->id, 'description' => $fields['description'],
                'requires_expiry_tracking' => $requiresExpiry, 'is_active' => $isActive,
            ]);
            $seenSkus[$skuKey] = true;
            $imported++;
        }

        $skipped = count($errors);
        $message = $imported.' produk berhasil diimpor'.($skipped ? '; '.$skipped.' baris dilewati karena data tidak valid.' : '.');
        return redirect()->route('admin.master-data.index', 'products')->with('status', $message)->with('import_errors', array_slice($errors, 0, 50));
    }

    public function importCategories(Request $request, MasterDataService $service)
    {
        $validated = $request->validate(['rows' => ['required', 'string', 'max:5000000']]);
        $rows = json_decode($validated['rows'], true);
        if (! is_array($rows) || ! isset($rows[0]) || ! is_array($rows[0]) || count($rows) < 2 || count($rows) > 1001) {
            return back()->withErrors(['workbook' => 'File harus berisi header dan maksimal 1.000 baris kategori.']);
        }

        $aliases = ['nama_kategori' => 'name', 'nama' => 'name', 'deskripsi' => 'description', 'aktif' => 'active'];
        $columns = [];
        foreach ($rows[0] as $index => $heading) {
            $normalized = Str::of((string) $heading)->trim()->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
            if (isset($aliases[$normalized])) $columns[$aliases[$normalized]] = $index;
        }
        if (! isset($columns['name'])) return back()->withErrors(['workbook' => 'Kolom Nama Kategori tidak ditemukan. Gunakan template impor kategori.']);

        $imported = 0;
        $errors = [];
        $seenNames = [];
        foreach (array_slice($rows, 1) as $offset => $cells) {
            if (! is_array($cells) || collect($cells)->every(fn ($cell) => trim((string) $cell) === '')) continue;
            $line = $offset + 2;
            $fields = [
                'name' => trim((string) ($cells[$columns['name']] ?? '')),
                'description' => isset($columns['description']) ? trim((string) ($cells[$columns['description']] ?? '')) : null,
            ];
            $validator = Validator::make($fields, [
                'name' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:5000'],
            ]);
            if ($validator->fails()) {
                $errors[] = 'Baris '.$line.': '.$validator->errors()->first();
                continue;
            }

            $nameKey = mb_strtolower($fields['name']);
            if (isset($seenNames[$nameKey]) || Category::withTrashed()->where('name', $fields['name'])->exists()) {
                $errors[] = 'Baris '.$line.': kategori "'.$fields['name'].'" sudah terdaftar atau berulang di file.';
                continue;
            }

            $rawActive = isset($columns['active']) ? mb_strtolower(trim((string) ($cells[$columns['active']] ?? ''))) : '';
            $isActive = match ($rawActive) {
                '', 'ya', 'yes', 'true', '1', 'aktif' => true,
                'tidak', 'no', 'false', '0', 'nonaktif' => false,
                default => null,
            };
            if ($isActive === null) {
                $errors[] = 'Baris '.$line.': kolom Aktif harus diisi Ya atau Tidak.';
                continue;
            }

            $service->create(Category::class, ['name' => $fields['name'], 'description' => $fields['description'], 'is_active' => $isActive]);
            $seenNames[$nameKey] = true;
            $imported++;
        }

        $skipped = count($errors);
        $message = $imported.' kategori berhasil diimpor'.($skipped ? '; '.$skipped.' baris dilewati karena data tidak valid.' : '.');
        return redirect()->route('admin.master-data.index', 'categories')->with('status', $message)->with('import_errors', array_slice($errors, 0, 50));
    }

    public function create(string $resource)
    {
        return view('admin.master-data.form', ['resource' => $resource, 'config' => $this->config($resource), 'item' => null, 'options' => $this->options()]);
    }

    public function store(Request $request, string $resource, MasterDataService $service, ProductImageService $images)
    {
        $config = $this->config($resource);
        $attributes = $this->validated($request, $resource);
        if ($resource === 'products' && $request->hasFile('image')) {
            $attributes['image_path'] = $images->convertAndStore($request->file('image'));
        }
        $service->create($config['model'], $attributes);

        return redirect()->route('admin.master-data.index', $resource)
            ->with('status', $config['label'].' berhasil ditambahkan.');
    }

    public function show(string $resource, int $id)
    {
        $config = $this->config($resource);
        $item = $config['model']::query()->when($resource === 'products', fn ($q) => $q->with(['category', 'unit']))->findOrFail($id);

        return view('admin.master-data.show', compact('resource', 'config', 'item'));
    }

    public function edit(string $resource, int $id)
    {
        $config = $this->config($resource);
        $item = $config['model']::findOrFail($id);

        return view('admin.master-data.form', ['resource' => $resource, 'config' => $config, 'item' => $item, 'options' => $this->options()]);
    }

    public function update(Request $request, string $resource, int $id, MasterDataService $service, ProductImageService $images)
    {
        $oldImage = null;
        $config = $this->config($resource);
        $item = $config['model']::findOrFail($id);
        $attributes = $this->validated($request, $resource, $item);
        if ($resource === 'products' && $request->hasFile('image')) {
            $oldImage = $item->image_path;
            $attributes['image_path'] = $images->convertAndStore($request->file('image'));
        }
        $service->update($item, $attributes);
        if (! empty($oldImage)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.master-data.index', $resource)
            ->with('status', $config['label'].' berhasil diperbarui.');
    }

    public function destroy(string $resource, int $id, MasterDataService $service)
    {
        $config = $this->config($resource);
        $service->delete($config['model']::findOrFail($id));

        return redirect()->route('admin.master-data.index', $resource)->with('status', $config['label'].' berhasil dihapus.');
    }

    private function config(string $resource): array
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);

        return self::RESOURCES[$resource];
    }

    private function options(): array
    {
        return ['categories' => Category::where('is_active', true)->orderBy('name')->get(), 'units' => Unit::where('is_active', true)->orderBy('name')->get()];
    }

    private function validated(Request $request, string $resource, ?Model $item = null): array
    {
        $id = $item?->getKey();
        $rules = match ($resource) {
            'categories' => ['name' => ['required', 'string', 'max:255', Rule::unique('categories')->ignore($id)], 'description' => ['nullable', 'string', 'max:5000'], 'is_active' => ['required', 'boolean']],
            'units' => ['name' => ['required', 'string', 'max:255', Rule::unique('units')->ignore($id)], 'symbol' => ['required', 'string', 'max:24', Rule::unique('units')->ignore($id)], 'description' => ['nullable', 'string', 'max:5000'], 'is_active' => ['required', 'boolean']],
            'suppliers' => ['name' => ['required', 'string', 'max:255', Rule::unique('suppliers')->ignore($id)], 'contact_person' => ['nullable', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'], 'address' => ['nullable', 'string', 'max:5000'], 'notes' => ['nullable', 'string', 'max:5000'], 'is_active' => ['required', 'boolean']],
            'locations' => ['code' => ['required', 'string', 'max:40', Rule::unique('locations')->ignore($id)], 'name' => ['required', 'string', 'max:255'], 'type' => ['required', Rule::in(['warehouse', 'outlet'])], 'address' => ['nullable', 'string', 'max:5000'], 'description' => ['nullable', 'string', 'max:5000'], 'is_active' => ['required', 'boolean']],
            'products' => ['sku' => ['required', 'string', 'max:64', Rule::unique('products')->ignore($id)], 'name' => ['required', 'string', 'max:255'], 'category_id' => ['required', 'integer', 'exists:categories,id,deleted_at,NULL,is_active,1'], 'unit_id' => ['required', 'integer', 'exists:units,id,deleted_at,NULL,is_active,1'], 'description' => ['nullable', 'string', 'max:5000'], 'requires_expiry_tracking' => ['required', 'boolean'], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'is_active' => ['required', 'boolean']],
        };

        return $request->validate($rules);
    }
}
