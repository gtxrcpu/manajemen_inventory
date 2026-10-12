<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\StockOpname;
use App\Services\StockOpnameService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockOpnameController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->hasPermissionTo('opname.manage') || auth()->user()->hasPermissionTo('opname.approve'), 403);

        $query = StockOpname::with(['location', 'counter', 'reviewer', 'items']);

        if ($q = $request->input('q')) {
            $query->where('reference', 'like', "%{$q}%");
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($locationId = $request->input('location_id')) {
            $query->where('location_id', $locationId);
        }

        $stats = [
            'total'           => StockOpname::count(),
            'counting'        => StockOpname::where('status', 'counting')->count(),
            'pending_approval'=> StockOpname::where('status', 'pending_approval')->count(),
            'approved'        => StockOpname::where('status', 'approved')->count(),
        ];

        return view('inventory.stock-opname.index', [
            'opnames'   => $query->latest()->paginate(15),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'stats'     => $stats,
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()->hasPermissionTo('opname.manage'), 403);
        return view('inventory.stock-opname.create', ['locations' => Location::where('is_active', true)->orderBy('name')->get()]);
    }

    public function start(Request $request, StockOpnameService $service): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id,deleted_at,NULL,is_active,1'],
            'period_type' => ['required', Rule::in(['weekly', 'month_end'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $opname = $service->start($data);
        return redirect()->route('inventory.stock-opname.show', $opname)->with('status', 'Snapshot saldo berhasil dibuat. Lakukan penghitungan fisik untuk setiap produk.');
    }

    public function show(StockOpname $stockOpname): View
    {
        abort_unless(auth()->user()->hasPermissionTo('opname.manage') || auth()->user()->hasPermissionTo('opname.approve'), 403);
        return view('inventory.stock-opname.show', ['opname' => $stockOpname->load(['location', 'counter', 'reviewer', 'items.product.unit'])]);
    }

    public function submit(Request $request, StockOpname $stockOpname, StockOpnameService $service): RedirectResponse
    {
        $items = $stockOpname->items()->get(['id']);
        $rules = ['items' => ['required', 'array', 'size:'.$items->count()]];
        foreach ($items as $item) {
            $rules["items.{$item->id}.physical_quantity"] = ['required', 'numeric', 'min:0', 'max:999999999'];
            $rules["items.{$item->id}.reason"] = ['nullable', 'string', 'max:500'];
        }
        $data = Validator::make($request->all(), $rules, [
            'items.size' => 'Semua produk harus dicatat. Muat ulang halaman jika item tidak lengkap.',
            '*.required' => 'Jumlah fisik wajib diisi.',
            '*.min' => 'Jumlah fisik tidak boleh kurang dari nol.',
        ])->validate();
        $service->submit($stockOpname, $data['items']);
        return redirect()->route('inventory.stock-opname.show', $stockOpname)->with('status', 'Hasil hitung terkirim ke Purchasing untuk ditinjau.');
    }

    public function approve(Request $request, StockOpname $stockOpname, StockOpnameService $service): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:5000']]);
        $service->approve($stockOpname, $data['review_note'] ?? null);
        return redirect()->route('inventory.stock-opname.show', $stockOpname)->with('status', 'Stock Opname disetujui dan saldo telah disesuaikan.');
    }

    public function reject(Request $request, StockOpname $stockOpname, StockOpnameService $service): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['required', 'string', 'max:5000']]);
        $service->reject($stockOpname, $data['review_note']);
        return redirect()->route('inventory.stock-opname.show', $stockOpname)->with('status', 'Stock Opname ditolak. Buat opname baru jika perlu menghitung ulang.');
    }
}
