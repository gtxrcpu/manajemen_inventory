<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use SoftDeletes;

    public const TYPE_WAREHOUSE = 'warehouse';

    public const TYPE_OUTLET = 'outlet';

    protected $fillable = ['code', 'name', 'type', 'address', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
