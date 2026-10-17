<?php

namespace App\Modules\Document\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Requests\StoreDocumentRequest;
use App\Modules\Document\Resources\DocumentResource;
use App\Modules\Document\Services\DocumentService;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentController
{
    use ApiResponseTrait;

    public function __construct(private DocumentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        $documents = Document::query()
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->documentable_type, fn($q) => $q->where('documentable_type', 'like', "%{$request->documentable_type}%"))
            ->when($request->filled('alert_sent'), fn($q) => $q->where('alert_sent', filter_var($request->alert_sent, FILTER_VALIDATE_BOOLEAN)))
            ->when($request->status, function ($q) use ($request) {
                if ($request->status === 'expired') {
                    $q->whereDate('expiry_date', '<', now()->toDateString());
                    return;
                }
                if ($request->status === 'expiring') {
                    $q->whereDate('expiry_date', '>=', now()->toDateString())
                      ->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString());
                    return;
                }
                if ($request->status === 'valid') {
                    $q->where(function ($sq) {
                        $sq->whereNull('expiry_date')
                           ->orWhereDate('expiry_date', '>', now()->addDays(30)->toDateString());
                    });
                }
            })
            ->when($request->from, fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to,   fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->orderBy('expiry_date')
            ->paginate(min((int) $request->input('per_page', 10), 100));
        return $this->paginatedResponse(DocumentResource::collection($documents)->resource);
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $document = $this->service->create($request->validated(), $request->file('file'));
        return $this->successResponse(new DocumentResource($document), 201, 'Document ajouté.');
    }

    public function show(string $uuid): JsonResponse
    {
        $document = Document::where('uuid', $uuid)->firstOrFail();
        $this->authorize('view', $document);
        return $this->successResponse(new DocumentResource($document));
    }

    public function destroy(string $uuid): JsonResponse
    {
        $document = Document::where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $document);
        $this->service->delete($document);
        return $this->successResponse(null, 200, 'Document supprimé.');
    }

    public function expiring(): JsonResponse
    {
        $documents = $this->service->getExpiringSoon();
        return $this->successResponse(DocumentResource::collection($documents));
    }

    protected function authorize(string $ability, mixed $target): void
    {
        app(Gate::class)->authorize($ability, $target);
    }
}
