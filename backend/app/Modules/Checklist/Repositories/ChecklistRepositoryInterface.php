<?php

namespace App\Modules\Checklist\Repositories;

use App\Modules\Checklist\Models\Checklist;
use App\Modules\Mission\Models\Mission;

interface ChecklistRepositoryInterface
{
    public function createForMission(Mission $mission): Checklist;
    public function findByMission(int $missionId): ?Checklist;
    public function update(Checklist $checklist, array $data): Checklist;
    public function updateItem(int $itemId, array $data): void;
}
