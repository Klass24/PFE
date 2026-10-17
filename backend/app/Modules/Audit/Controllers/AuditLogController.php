<?php

namespace App\Modules\Audit\Controllers;

use App\Core\Traits\ApiResponseTrait;
use App\Modules\Audit\Models\AuditLog;
use App\Modules\Audit\Resources\AuditLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController
{
    use ApiResponseTrait;

    public function index(Request $request): JsonResponse
    {
        $this->authorize();

        $paginator = AuditLog::where('company_id', auth()->user()->company_id)
            ->when($request->user_id, fn($q, $v) => $q->where('user_id', $v))
            ->when($request->action, fn($q, $v) => $q->where('action', $v))
            ->when($request->auditable_type, fn($q, $v) => $q->where('auditable_type', 'like', "%{$v}%"))
            ->when($request->from, fn($q, $v) => $q->where('created_at', '>=', $v))
            ->when($request->to, fn($q, $v) => $q->where('created_at', '<=', $v))
            ->with('user:id,uuid,name')
            ->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->input('per_page', 10), 100));

        return $this->paginatedResponse(AuditLogResource::collection($paginator)->resource, 'Logs d\'audit récupérés.');
    }

    protected function authorize(): void
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Action non autorisée.');
        }
    }
}
