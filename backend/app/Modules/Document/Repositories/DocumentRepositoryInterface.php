<?php

namespace App\Modules\Document\Repositories;

use App\Modules\Document\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DocumentRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function findByUuid(string $uuid): Document;
    public function create(array $data): Document;
    public function update(Document $document, array $data): Document;
    public function delete(Document $document): void;
    public function getExpiringSoon(int $days = 30): Collection;
}
