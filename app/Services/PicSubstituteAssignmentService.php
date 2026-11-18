<?php

namespace App\Services;

use App\Models\Location;
use App\Models\PicSubstituteAssignment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PicSubstituteAssignmentService
{
    public function __construct(private readonly AdminAuditLogger $auditLogger) {}

    public function assign(array $data): PicSubstituteAssignment
    {
        return DB::transaction(function () use ($data) {
            $location = Location::query()->lockForUpdate()->findOrFail($data['location_id']);
            $primary = User::query()->lockForUpdate()->findOrFail($data['primary_pic_user_id']);
            $substitute = User::query()->lockForUpdate()->findOrFail($data['substitute_pic_user_id']);
            $startsAt = Carbon::parse($data['starts_at']);
            $endsAt = Carbon::parse($data['ends_at']);

            if ($location->type !== Location::TYPE_OUTLET || ! $location->is_active
                || ! $primary->is_active || ! $primary->hasRole('pic_outlet') || (int) $primary->location_id !== (int) $location->id) {
                throw ValidationException::withMessages(['primary_pic_user_id' => 'PIC utama harus aktif dan ditugaskan pada outlet yang dipilih.']);
            }
            if (! $substitute->is_active || ! $substitute->hasRole('pic_outlet') || $substitute->id === $primary->id) {
                throw ValidationException::withMessages(['substitute_pic_user_id' => 'PIC pengganti harus akun PIC aktif yang berbeda dari PIC utama.']);
            }
            if ((int) $substitute->location_id === (int) $location->id) {
                throw ValidationException::withMessages(['substitute_pic_user_id' => 'PIC yang sudah menjadi PIC utama outlet ini tidak perlu dijadwalkan sebagai pengganti.']);
            }
            if ($endsAt->lessThanOrEqualTo($startsAt)) {
                throw ValidationException::withMessages(['ends_at' => 'Waktu selesai harus sesudah waktu mulai.']);
            }
            if ($startsAt->lt(now()->startOfMinute())) {
                throw ValidationException::withMessages(['starts_at' => 'Masa berlaku tidak dapat dimulai pada waktu lampau.']);
            }

            $overlap = PicSubstituteAssignment::query()->whereNull('revoked_at')
                ->where('substitute_pic_user_id', $substitute->id)
                ->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)
                ->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['substitute_pic_user_id' => 'PIC pengganti sudah memiliki penugasan yang waktunya bertumpang tindih.']);
            }
            $outletOverlap = PicSubstituteAssignment::query()->whereNull('revoked_at')
                ->where('location_id', $location->id)
                ->where('starts_at', '<', $endsAt)->where('ends_at', '>', $startsAt)
                ->exists();
            if ($outletOverlap) {
                throw ValidationException::withMessages(['location_id' => 'Outlet sudah memiliki jadwal PIC pengganti yang waktunya bertumpang tindih.']);
            }

            $assignment = PicSubstituteAssignment::create([
                'location_id' => $location->id,
                'primary_pic_user_id' => $primary->id,
                'substitute_pic_user_id' => $substitute->id,
                'assigned_by' => auth()->id(),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'notes' => $data['notes'] ?? null,
            ]);
            $assignment->load(['location', 'primaryPic', 'substitutePic']);

            $this->auditLogger->record('pic.substitute_assigned', $assignment, [
                'after' => $this->snapshot($assignment),
            ]);

            return $assignment;
        });
    }

    public function revoke(PicSubstituteAssignment $assignment, string $reason): void
    {
        DB::transaction(function () use ($assignment, $reason) {
            $assignment = PicSubstituteAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($assignment->revoked_at) {
                throw ValidationException::withMessages(['assignment' => 'Penugasan ini sudah dicabut.']);
            }

            $before = $this->snapshot($assignment);
            $assignment->forceFill(['revoked_at' => now(), 'revoked_by' => auth()->id()])->save();
            $this->auditLogger->record('pic.substitute_revoked', $assignment, [
                'before' => $before,
                'after' => $this->snapshot($assignment) + ['revocation_reason' => $reason],
            ]);
        });
    }

    private function snapshot(PicSubstituteAssignment $assignment): array
    {
        return [
            'location_id' => $assignment->location_id,
            'primary_pic_user_id' => $assignment->primary_pic_user_id,
            'substitute_pic_user_id' => $assignment->substitute_pic_user_id,
            'starts_at' => $assignment->starts_at?->toIso8601String(),
            'ends_at' => $assignment->ends_at?->toIso8601String(),
            'revoked_at' => $assignment->revoked_at?->toIso8601String(),
            'revoked_by' => $assignment->revoked_by,
            'notes' => $assignment->notes,
        ];
    }
}
