<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private function filters(Request $request): array
    {
        $filters = $request->validate([
            'report' => ['nullable', Rule::in(InventoryReportService::TYPES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'transaction_type' => ['nullable', 'in:stock_in,stock_out,opname,adjustment,issued,received'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($request->user()->hasRole('pic_outlet')) {
            $filters['location_id'] = $request->user()->location_id;
        } elseif (empty($filters['location_id'])) {
            $filters['location_id'] = $request->session()->get('selected_location_id');
        }

        return $filters;
    }

    private function authorizeType(Request $request, string $type): void
    {
        abort_unless($request->user()->hasPermissionTo('reports.view'), 403);
        if ($type === 'purchasing') {
            abort_unless($request->user()->hasRole('super_admin') || $request->user()->hasRole('management')
                || $request->user()->hasRole('finance') || $request->user()->hasRole('purchasing'), 403);
        }
        if ($request->user()->hasRole('pic_outlet')) {
            abort_unless($request->user()->location_id, 403, 'Akun PIC Outlet belum ditetapkan ke outlet.');
        }
    }

    public function index(Request $request, InventoryReportService $reports): View
    {
        $filters = $this->filters($request);
        $type = $filters['report'] ?? 'stock';
        $this->authorizeType($request, $type);
        $definition = $reports->definition($type);
        $query = $reports->query($type, $filters, $request->user());
        $rows = $query->paginate(25)->withQueryString();
        $picLocation = $request->user()->hasRole('pic_outlet') ? $request->user()->location : null;

        return view('reports.index', [
            'reportType' => $type,
            'definition' => $definition,
            'columns' => $definition['columns'],
            'rows' => $rows,
            'filters' => $filters,
            'picLocation' => $picLocation,
            'reportTypes' => collect(InventoryReportService::TYPES)
                ->reject(fn ($key) => $key === 'purchasing' && ! ($request->user()->hasRole('super_admin') || $request->user()->hasRole('management') || $request->user()->hasRole('finance') || $request->user()->hasRole('purchasing')))
                ->mapWithKeys(fn ($key) => [$key => $reports->definition($key)['label']]),
            'locations' => $picLocation ? collect([$picLocation]) : Location::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::with('unit')->where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'transactionTypes' => $this->transactionTypes($type),
        ]);
    }

    public function export(Request $request, InventoryReportService $reports): StreamedResponse
    {
        $filters = $this->filters($request);
        $type = $filters['report'] ?? 'stock';
        $this->authorizeType($request, $type);
        $definition = $reports->definition($type);
        $query = $reports->query($type, $filters, $request->user());
        $filename = 'laporan-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query, $definition) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, array_values($definition['columns']), ';', '"', '\\');
            foreach ($query->cursor() as $row) {
                $values = [];
                foreach (array_keys($definition['columns']) as $key) {
                    $value = $row->{$key} ?? '';
                    if ($value instanceof \DateTimeInterface) $value = $value->format('Y-m-d H:i:s');
                    $value = (string) $value;
                    if ($value !== '' && ! is_numeric($value) && preg_match('/^[\s]*[=+@\-]/u', $value)) $value = "'".$value;
                    $values[] = $value;
                }
                fputcsv($output, $values, ';', '"', '\\');
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function transactionTypes(string $type): array
    {
        return match ($type) {
            'movements' => ['stock_in' => 'Stock In', 'stock_out' => 'Stock Out', 'opname' => 'Stock Opname', 'adjustment' => 'Adjustment'],
            'purchasing' => ['issued' => 'Menunggu barang', 'received' => 'Diterima'],
            default => [],
        };
    }
}
