<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationContextController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless(! $user->isLocationScopedUser(), 403);

        $data = $request->validate([
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)->whereNull('deleted_at')],
        ]);

        if (empty($data['location_id'])) {
            $request->session()->forget('selected_location_id');
        } else {
            $request->session()->put('selected_location_id', (int) $data['location_id']);
        }

        $previousUrl = $request->headers->get('referer', '');
        $previousPath = rtrim((string) parse_url($previousUrl, PHP_URL_PATH), '/');
        $dashboardPath = rtrim((string) parse_url(route('dashboard'), PHP_URL_PATH), '/');
        if ($previousPath === '' || $previousPath === $dashboardPath) {
            parse_str((string) parse_url($previousUrl, PHP_URL_QUERY), $previousQuery);

            return redirect()->route('dashboard', array_filter([
                'period' => $previousQuery['period'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''))->with('status', 'Filter lokasi berhasil diperbarui.');
        }

        return back()->with('status', 'Filter lokasi berhasil diperbarui.');
    }
}
