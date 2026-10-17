<?php

namespace App\Modules\Incident\Services;

use App\Core\Services\BaseService;
use App\Modules\Incident\Events\IncidentReportedEvent;
use App\Modules\Incident\Models\Incident;
use App\Modules\Incident\Repositories\IncidentRepositoryInterface;
use App\Modules\Mission\Models\Mission;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IncidentService extends BaseService
{
    public function __construct(private IncidentRepositoryInterface $repo) {}

    public function create(Mission $mission, array $data): Incident
    {
        // M4 — incident lié à une mission active
        if (!in_array($mission->status, ['planned', 'in_progress'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'mission_id' => 'Les incidents ne peuvent être signalés que sur une mission active.',
            ]);
        }

        $data['mission_id']              = $mission->id;
        $data['company_id']              = auth()->user()->company_id;
        $data['reported_at']             = $data['reported_at'] ?? now();
        $data['date_reception_serveur']  = now();
        $data['sync_status']             = 'synced';

        // M7 — Déduplication offline
        if (!empty($data['device_id']) && !empty($data['reported_at'])) {
            $existing = Incident::withoutGlobalScopes()
                ->where('device_id', $data['device_id'])
                ->where('reported_at', $data['reported_at'])
                ->where('mission_id', $mission->id)
                ->first();

            if ($existing) {
                Log::info('Duplicate incident ignored (device_id dedup)', ['incident_id' => $existing->id]);
                return $existing;
            }
        }

        $incident = $this->repo->create($data);

        event(new IncidentReportedEvent($incident));
        Log::warning('Incident reported', ['incident_id' => $incident->id, 'type' => $incident->type]);

        return $incident;
    }

    public function update(Incident $incident, array $data): Incident
    {
        return $this->repo->update($incident, $data);
    }

    public function delete(Incident $incident): void
    {
        if ($incident->image) {
            Storage::disk('public')->delete($incident->image);
        }
        $this->repo->delete($incident);
    }
}
