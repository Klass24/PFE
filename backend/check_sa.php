<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sa = \App\Modules\User\Models\User::whereHas('roles', fn($q) => $q->where('name', 'super_admin'))->first();
echo "Super admin: {$sa->email}\n";

// Simulate auth as super admin
auth()->login($sa);

// Call the repository directly
$repo = new \App\Modules\User\Repositories\UserRepository();
$result = $repo->paginate(['role' => ['admin', 'manager']]);
echo "Total results: {$result->total()}\n";
foreach ($result as $u) {
    $roleName = $u->roles->first()?->name ?? 'no-role';
    $companyName = $u->company_name ?? 'N/A';
    echo "  {$u->name} | {$u->email} | role={$roleName} | company={$companyName}\n";
}
