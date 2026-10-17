<?php

namespace App\Modules\Checklist\Services;

use App\Modules\Checklist\Models\Checklist;
use App\Modules\Checklist\Models\ChecklistItem;
use App\Modules\Checklist\Repositories\ChecklistRepositoryInterface;
use App\Modules\Mission\Models\Mission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ChecklistService
{
    public function __construct(private ChecklistRepositoryInterface $repo) {}

    public function createForMission(Mission $mission): Checklist
    {
        $checklist = $this->repo->createForMission($mission);
        Log::info('Checklist created for mission', ['mission_id' => $mission->id]);
        return $checklist;
    }

    public function getForMission(Mission $mission): ?Checklist
    {
        return $this->repo->findByMission($mission->id);
    }

    /**
     * Met à jour les items (is_ok, note) depuis le payload du chauffeur.
     * @param array $items [['id' => X, 'is_ok' => bool, 'note' => string|null], ...]
     */
    public function updateItems(Checklist $checklist, array $items): Checklist
    {
        foreach ($items as $itemData) {
            $this->repo->updateItem($itemData['id'], [
                'is_ok' => $itemData['is_ok'] ?? false,
                'note'  => $itemData['note'] ?? null,
            ]);
        }
        return $checklist->fresh('items');
    }

    /**
     * Téléverse la photo d'un item qui l'exige.
     */
    public function uploadItemPhoto(ChecklistItem $item, UploadedFile $file): ChecklistItem
    {
        if ($item->photo_path) {
            Storage::disk('public')->delete($item->photo_path);
        }
        $path = $file->store('checklists/photos', 'public');
        $this->repo->updateItem($item->id, ['photo_path' => $path]);
        return $item->fresh();
    }

    /**
     * Soumet la check-list après vérification.
     * Lance une ValidationException si des photos obligatoires manquent.
     */
    public function submit(Checklist $checklist, ?string $deviceId = null, ?\DateTime $saisieAt = null): Checklist
    {
        $missingPhotos = $checklist->items()
            ->where('requires_photo', true)
            ->whereNull('photo_path')
            ->pluck('label')
            ->toArray();

        if (!empty($missingPhotos)) {
            throw ValidationException::withMessages([
                'photos' => 'Photos manquantes pour : ' . implode(', ', $missingPhotos),
            ]);
        }

        return $this->repo->update($checklist, [
            'status'                 => 'submitted',
            'submitted_at'           => now(),
            'device_id'              => $deviceId,
            'date_saisie_reelle'     => $saisieAt ?? now(),
            'date_reception_serveur' => now(),
            'sync_status'            => 'synced',
        ]);
    }

    /**
     * Valide la check-list (par le gestionnaire).
     */
    public function validate(Checklist $checklist): Checklist
    {
        if ($checklist->status !== 'submitted') {
            throw ValidationException::withMessages([
                'status' => 'La check-list doit être soumise avant validation.',
            ]);
        }

        return $this->repo->update($checklist, ['status' => 'validated']);
    }
}
