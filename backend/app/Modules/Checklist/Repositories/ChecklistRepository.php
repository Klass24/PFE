<?php

namespace App\Modules\Checklist\Repositories;

use App\Modules\Checklist\Models\Checklist;
use App\Modules\Checklist\Models\ChecklistItem;
use App\Modules\Mission\Models\Mission;

class ChecklistRepository implements ChecklistRepositoryInterface
{
    /** Items standards à créer pour chaque check-list de départ. */
    private const DEFAULT_ITEMS = [
        ['item_key' => 'tire_pressure',    'label' => 'Pression des pneus',          'requires_photo' => false],
        ['item_key' => 'fire_extinguisher','label' => 'Extincteur présent et valide', 'requires_photo' => true],
        ['item_key' => 'lights',           'label' => 'Éclairages fonctionnels',      'requires_photo' => false],
        ['item_key' => 'brakes',           'label' => 'Freins vérifiés',              'requires_photo' => false],
        ['item_key' => 'engine_oil',       'label' => 'Niveau huile moteur',          'requires_photo' => false],
        ['item_key' => 'windshield',       'label' => 'Pare-brise sans fissure',      'requires_photo' => false],
        ['item_key' => 'documents',        'label' => 'Documents de bord présents',   'requires_photo' => false],
        ['item_key' => 'first_aid_kit',    'label' => 'Trousse de premiers secours',  'requires_photo' => false],
    ];

    public function createForMission(Mission $mission): Checklist
    {
        $checklist = Checklist::create([
            'mission_id' => $mission->id,
            'driver_id'  => $mission->driver_id,
            'vehicle_id' => $mission->vehicle_id,
            'company_id' => $mission->company_id,
            'status'     => 'draft',
        ]);

        foreach (self::DEFAULT_ITEMS as $item) {
            ChecklistItem::create(array_merge($item, ['checklist_id' => $checklist->id]));
        }

        return $checklist->load('items');
    }

    public function findByMission(int $missionId): ?Checklist
    {
        return Checklist::where('mission_id', $missionId)->with('items')->first();
    }

    public function update(Checklist $checklist, array $data): Checklist
    {
        $checklist->update($data);
        return $checklist->fresh('items');
    }

    public function updateItem(int $itemId, array $data): void
    {
        ChecklistItem::where('id', $itemId)->update($data);
    }
}
