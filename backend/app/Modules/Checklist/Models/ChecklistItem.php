<?php

namespace App\Modules\Checklist\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistItem extends Model
{
    protected $fillable = [
        'checklist_id', 'item_key', 'label', 'is_ok', 'requires_photo', 'photo_path', 'note',
    ];

    protected function casts(): array
    {
        return [
            'is_ok'          => 'boolean',
            'requires_photo' => 'boolean',
        ];
    }

    public function checklist()
    {
        return $this->belongsTo(Checklist::class);
    }
}
