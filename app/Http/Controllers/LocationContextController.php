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
        abort_unless(! $user->hasRole('pic_outlet'), 403);

        $data = $request->validate([
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)->whereNull('deleted_at')],
        ]);

        if (empty($data['location_id'])) {
            $request->session()->forget('selected_location_id');
        } else {
            $request->session()->put('selected_location_id', (int) $data['location_id']);
        }

        return back()->with('status', 'Filter lokasi berhasil diperbarui.');
    }
}
