<?php

namespace App\Modules\ApiToken\Services;

use App\Modules\ApiToken\Models\ApiToken;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ApiTokenService
{
    public function list(): \Illuminate\Database\Eloquent\Collection
    {
        return ApiToken::where('company_id', auth()->user()->company_id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function generate(array $data): array
    {
        $plainToken = Str::random(64);

        $apiToken = ApiToken::create([
            'company_id' => auth()->user()->company_id,
            'name'       => $data['name'],
            'token'      => hash('sha256', $plainToken),
            'abilities'  => $data['abilities'] ?? ['*'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        Log::info('API token generated', ['token_id' => $apiToken->id, 'by' => auth()->id()]);

        // Le token en clair n'est retourné qu'une seule fois
        return [
            'token'     => $apiToken,
            'plainText' => $plainToken,
        ];
    }

    public function revoke(ApiToken $apiToken): void
    {
        $apiToken->delete();
        Log::info('API token revoked', ['token_id' => $apiToken->id, 'by' => auth()->id()]);
    }
}
