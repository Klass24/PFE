<?php

namespace App\Modules\Document\Policies;

use App\Modules\Document\Models\Document;
use App\Modules\User\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function view(User $user, Document $document): bool
    {
        return $document->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasAnyRole(['admin', 'manager'])
            && $document->company_id === $user->company_id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasRole('admin')
            && $document->company_id === $user->company_id;
    }
}
