<?php

namespace App\Modules\Vehicle\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleStatusLog extends Model
{
    public const UPDATED_AT = null; // table n'a que created_at

    protected $fillable = [
        'vehicle_id', 'company_id', 'old_status', 'new_status', 'changed_by', 'reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class, 'changed_by');
    }
}
