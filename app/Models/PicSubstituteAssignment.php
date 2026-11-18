<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PicSubstituteAssignment extends Model
{
    protected $fillable = [
        'location_id', 'primary_pic_user_id', 'substitute_pic_user_id', 'assigned_by',
        'starts_at', 'ends_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function primaryPic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'primary_pic_user_id')->withTrashed();
    }

    public function substitutePic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substitute_pic_user_id')->withTrashed();
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by')->withTrashed();
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by')->withTrashed();
    }
}
