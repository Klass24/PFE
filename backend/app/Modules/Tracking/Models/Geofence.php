<?php

namespace App\Modules\Tracking\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Geofence extends Model
{
    protected $fillable = [
        'uuid', 'company_id', 'name', 'type',
        'center_lat', 'center_lng', 'radius_meters',
        'polygon_points', 'trigger_on', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'polygon_points' => 'array',
            'is_active'      => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) $model->uuid = Str::uuid();
            if (auth()->check() && empty($model->company_id)) {
                $model->company_id = auth()->user()->company_id;
            }
        });

        static::addGlobalScope('company', function (Builder $query) {
            if (auth()->check()) {
                $query->where('geofences.company_id', auth()->user()->company_id);
            }
        });
    }

    public function containsPoint(float $lat, float $lng): bool
    {
        if ($this->type === 'circle') {
            $distance = $this->haversineDistance($this->center_lat, $this->center_lng, $lat, $lng);
            return $distance <= $this->radius_meters;
        }
        return false; // polygon check can be implemented later
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
