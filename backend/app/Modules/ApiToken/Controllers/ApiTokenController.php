<?php

namespace App\Modules\ApiToken\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\ApiToken\Models\ApiToken;
use App\Modules\ApiToken\Requests\StoreApiTokenRequest;
use App\Modules\ApiToken\Resources\ApiTokenResource;
use App\Modules\ApiToken\Services\ApiTokenService;
use Illuminate\Http\JsonResponse;

class ApiTokenController
{
    use ApiResponseTrait;

    public function __construct(private ApiTokenService $service) {}

    public function index(): JsonResponse
    {
        $this->authorizeAdmin();
        $tokens = $this->service->list();
        return $this->successResponse(ApiTokenResource::collection($tokens));
    }

    public function store(StoreApiTokenRequest $request): JsonResponse
    {
        $this->authorizeAdmin();
        $result = $this->service->generate($request->validated());
        return $this->successResponse([
            'token'      => new ApiTokenResource($result['token']),
            'plain_text' => $result['plainText'],
        ], 201, 'Token API créé. Conservez-le, il ne sera plus affiché.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $this->authorizeAdmin();
        $token = ApiToken::where('uuid', $uuid)
            ->where('company_id', auth()->user()->company_id)
            ->firstOrFail();
        $this->service->revoke($token);
        return $this->successResponse(null, 200, 'Token API révoqué.');
    }

    protected function authorizeAdmin(): void
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Action non autorisée.');
        }
    }
}
