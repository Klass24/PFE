<?php

namespace App\Modules\Document\Repositories;

use App\Modules\Document\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DocumentRepository implements DocumentRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Document::query()
            ->when(!empty($filters['type']), fn($q) => $q->where('type', $filters['type']))
            ->when(!empty($filters['from']), fn($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when(!empty($filters['to']),   fn($q) => $q->whereDate('created_at', '<=', $filters['to']))
            ->orderBy('expiry_date')
            ->paginate($perPage);
    }

    public function findByUuid(string $uuid): Document
    {
        return Document::where('uuid', $uuid)->firstOrFail();
    }

    public function create(array $data): Document
    {
        return Document::create($data);
    }

    public function update(Document $document, array $data): Document
    {
        $document->update($data);
        return $document->fresh();
    }

    public function delete(Document $document): void
    {
        $document->delete();
    }

    public function getExpiringSoon(int $days = 30): Collection
    {
        return Document::whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)])
            ->get();
    }
}
