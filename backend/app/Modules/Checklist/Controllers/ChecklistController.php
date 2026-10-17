<?php

namespace App\Modules\Checklist\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Checklist\Models\Checklist;
use App\Modules\Checklist\Models\ChecklistItem;
use App\Modules\Checklist\Requests\SubmitChecklistRequest;
use App\Modules\Checklist\Requests\UpdateChecklistItemsRequest;
use App\Modules\Checklist\Resources\ChecklistResource;
use App\Modules\Checklist\Services\ChecklistService;
use App\Modules\Mission\Models\Mission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController
{
    use ApiResponseTrait;

    public function __construct(private ChecklistService $service) {}

    /**
     * Liste de checklist par défaut (standalone - non liée à une mission).
     */
    public function index(): JsonResponse
    {
        return $this->current();
    }

    /**
     * Checklist courante pour vérification de départ.
     * Retourne les items standards de vérification pré-départ.
     */
    public function current(): JsonResponse
    {
        // Items de checklist standards pour vérification avant départ
        $defaultItems = [
            // Documents
            ['id' => '1', 'label' => 'Permis de conduire', 'category' => 'documents', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '2', 'label' => 'Carte grise', 'category' => 'documents', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '3', 'label' => "Attestation d'assurance", 'category' => 'documents', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '4', 'label' => "Carnet d'entretien", 'category' => 'documents', 'is_checked' => false, 'requires_photo' => false],
            // Véhicule
            ['id' => '5', 'label' => 'Pression des pneus', 'category' => 'vehicle', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '6', 'label' => "Niveau d'huile", 'category' => 'vehicle', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '7', 'label' => 'Niveau liquide de frein', 'category' => 'vehicle', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '8', 'label' => 'Niveau de carburant', 'category' => 'vehicle', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '9', 'label' => 'Feux avant/arrière', 'category' => 'vehicle', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '10', 'label' => 'Clignotants', 'category' => 'vehicle', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '11', 'label' => 'Essuie-glaces', 'category' => 'vehicle', 'is_checked' => false, 'requires_photo' => false],
            // Sécurité
            ['id' => '12', 'label' => 'Gilet de sécurité', 'category' => 'security', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '13', 'label' => 'Triangle de signalisation', 'category' => 'security', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '14', 'label' => 'Extincteur', 'category' => 'security', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '15', 'label' => 'Trousse de premiers soins', 'category' => 'security', 'is_checked' => false, 'requires_photo' => false],
            // Chargement
            ['id' => '16', 'label' => 'Arrimage de la cargaison', 'category' => 'loading', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '17', 'label' => 'Poids dans les limites', 'category' => 'loading', 'is_checked' => false, 'requires_photo' => false],
            ['id' => '18', 'label' => 'Scellés intacts', 'category' => 'loading', 'is_checked' => false, 'requires_photo' => false],
        ];

        return $this->successResponse($defaultItems);
    }

    /**
     * Valider une checklist standalone (non liée à mission).
     */
    public function validateStandalone(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.is_checked' => 'required|boolean',
        ]);

        // Log la validation pour audit
        $user = auth()->user();
        \Log::info('Checklist standalone validée', [
            'user_id' => $user?->id,
            'items_count' => count($request->items),
            'validated_at' => now()->toIso8601String(),
        ]);

        return $this->successResponse([
            'validated' => true,
            'validated_at' => now()->toIso8601String(),
        ], 200, 'Check-list validée avec succès.');
    }

    /**
     * Récupérer la check-list d'une mission.
     */
    public function show(string $missionUuid): JsonResponse
    {
        $mission = Mission::where('uuid', $missionUuid)->firstOrFail();
        $checklist = $this->service->getForMission($mission);

        if (!$checklist) {
            return $this->errorResponse('Check-list introuvable pour cette mission.', 404);
        }

        return $this->successResponse(new ChecklistResource($checklist));
    }

    /**
     * Mettre à jour les items (cochage par le chauffeur).
     */
    public function updateItems(UpdateChecklistItemsRequest $request, string $missionUuid): JsonResponse
    {
        $mission = Mission::where('uuid', $missionUuid)->firstOrFail();
        $checklist = $this->service->getForMission($mission);

        if (!$checklist || $checklist->status === 'validated') {
            return $this->errorResponse('Modification de la check-list non autorisée.', 422);
        }

        $checklist = $this->service->updateItems($checklist, $request->validated()['items']);
        return $this->successResponse(new ChecklistResource($checklist), 200, 'Check-list mise à jour.');
    }

    /**
     * Téléverser la photo d'un item spécifique.
     */
    public function uploadItemPhoto(Request $request, string $missionUuid, int $itemId): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|max:5120',
        ]);

        $mission = Mission::where('uuid', $missionUuid)->firstOrFail();
        $checklist = $this->service->getForMission($mission);

        if (!$checklist) {
            return $this->errorResponse('Check-list introuvable.', 404);
        }

        $item = ChecklistItem::where('id', $itemId)
            ->where('checklist_id', $checklist->id)
            ->firstOrFail();

        $item = $this->service->uploadItemPhoto($item, $request->file('photo'));

        return $this->successResponse([
            'id'        => $item->id,
            'has_photo' => true,
        ], 200, 'Photo enregistrée.');
    }

    /**
     * Soumettre la check-list (chauffeur valide).
     */
    public function submit(SubmitChecklistRequest $request, string $missionUuid): JsonResponse
    {
        $mission = Mission::where('uuid', $missionUuid)->firstOrFail();
        $checklist = $this->service->getForMission($mission);

        if (!$checklist) {
            return $this->errorResponse('Check-list introuvable.', 404);
        }

        $saisieAt = $request->date_saisie_reelle
            ? new \DateTime($request->date_saisie_reelle)
            : null;

        $checklist = $this->service->submit($checklist, $request->device_id, $saisieAt);

        return $this->successResponse(new ChecklistResource($checklist), 200, 'Check-list soumise.');
    }

    /**
     * Valider la check-list (gestionnaire confirme).
     */
    public function validate(string $missionUuid): JsonResponse
    {
        $mission = Mission::where('uuid', $missionUuid)->firstOrFail();
        $checklist = $this->service->getForMission($mission);

        if (!$checklist) {
            return $this->errorResponse('Check-list introuvable.', 404);
        }

        $checklist = $this->service->validate($checklist);

        return $this->successResponse(new ChecklistResource($checklist), 200, 'Check-list validée.');
    }

    protected function authorize(string $ability, mixed $target): void
    {
        app(\Illuminate\Contracts\Auth\Access\Gate::class)->authorize($ability, $target);
    }
}
