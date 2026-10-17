<?php

namespace App\Modules\Company\Repositories;

use App\Modules\Company\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function paginate(): LengthAwarePaginator
    {
        return Company::latest()->paginate(15);
    }

    public function findBySlug(string $slug): ?Company
    {
        return Company::where('slug', $slug)->first();
    }

    public function create(array $data): Company
    {
        return Company::create($data);
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);
        return $company->fresh();
    }
}
