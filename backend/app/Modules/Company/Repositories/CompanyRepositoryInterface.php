<?php

namespace App\Modules\Company\Repositories;

use App\Modules\Company\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CompanyRepositoryInterface
{
    public function paginate(): LengthAwarePaginator;
    public function findBySlug(string $slug): ?Company;
    public function create(array $data): Company;
    public function update(Company $company, array $data): Company;
}
