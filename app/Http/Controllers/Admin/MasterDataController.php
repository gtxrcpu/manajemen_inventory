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
use Illuminate\Validation\Rule;

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
            'products' => ['sku' => ['required', 'string', 'max:64', Rule::unique('products')->ignore($id)], 'name' => ['required', 'string', 'max:255'], 'category_id' => ['required', 'integer', 'exists:categories,id,deleted_at,NULL,is_active,1'], 'unit_id' => ['required', 'integer', 'exists:units,id,deleted_at,NULL,is_active,1'], 'description' => ['nullable', 'string', 'max:5000'], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'is_active' => ['required', 'boolean']],
        };

        return $request->validate($rules);
    }
}
