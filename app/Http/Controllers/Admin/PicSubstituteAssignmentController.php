<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\PicSubstituteAssignment;
use App\Models\User;
use App\Services\PicSubstituteAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PicSubstituteAssignmentController extends Controller
{
    public function index(): View
    {
        return view('admin.pic-substitutes.index', [
            'assignments' => PicSubstituteAssignment::with(['location', 'primaryPic', 'substitutePic', 'assigner', 'revoker'])
                ->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.pic-substitutes.create', $this->formOptions());
    }

    public function store(Request $request, PicSubstituteAssignmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'primary_pic_user_id' => ['required', 'integer', 'exists:users,id'],
            'substitute_pic_user_id' => ['required', 'integer', 'different:primary_pic_user_id', 'exists:users,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $service->assign($data);

        return redirect()->route('admin.pic-substitutes.index')->with('status', 'Penugasan PIC pengganti tersimpan dan akan aktif hanya pada rentang waktu yang ditentukan.');
    }

    public function revoke(Request $request, PicSubstituteAssignment $assignment, PicSubstituteAssignmentService $service): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:1000']]);
        $service->revoke($assignment, $data['reason']);

        return back()->with('status', 'Penugasan PIC pengganti dicabut dan perubahan dicatat di audit log.');
    }

    private function formOptions(): array
    {
        $pics = User::query()->where('is_active', true)->whereHas('roles', fn ($roles) => $roles->where('name', 'pic_outlet'))->orderBy('name')->get(['id', 'name', 'email', 'location_id']);

        return [
            'locations' => Location::where('type', Location::TYPE_OUTLET)->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'primaryPics' => $pics->filter(fn ($user) => $user->location_id)->values(),
            'substitutePics' => $pics,
            'minimumStart' => Carbon::now()->format('Y-m-d\TH:i'),
        ];
    }
}
