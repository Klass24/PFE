<?php

namespace App\Modules\Admin\Services;

use App\Modules\Company\Models\Company;
use App\Modules\Expense\Models\Expense;
use App\Modules\Mission\Models\Mission;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function getStats(): array
    {
        return [
            'total_companies'     => Company::count(),
            'active_companies'    => Company::where('status', 'active')->count(),
            'suspended_companies' => Company::where('status', 'suspended')->count(),
            'total_users'         => User::count(),
            'companies_by_plan'   => Company::select('plan', DB::raw('count(*) as count'))
                ->groupBy('plan')
                ->pluck('count', 'plan'),
            'recent_companies'    => Company::orderBy('created_at', 'desc')
                ->take(5)
                ->get(['uuid', 'name', 'plan', 'status', 'created_at']),
        ];
    }
}
