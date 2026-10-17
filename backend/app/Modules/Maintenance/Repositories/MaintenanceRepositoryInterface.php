<?php

namespace App\Modules\Maintenance\Repositories;

use App\Modules\Maintenance\Models\MaintenanceSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MaintenanceRepositoryInterface
{
    public function paginate(array $filters = []): LengthAwarePaginator;
    public function create(array $data): MaintenanceSchedule;
    public function update(MaintenanceSchedule $schedule, array $data): MaintenanceSchedule;
    public function delete(MaintenanceSchedule $schedule): void;
}
