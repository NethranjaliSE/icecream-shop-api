<?php

namespace App\Policies\Concerns;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

trait ChecksPermissions
{
    /**
     * Allow or deny based on the user's role, with a 403 message that tells
     * the client which permission was missing.
     */
    protected function permit(User $user, Permission $permission): Response
    {
        if ($user->hasPermission($permission)) {
            return Response::allow();
        }

        $role = $user->role?->value ?? 'none';

        return Response::deny("Your role ({$role}) is not allowed to perform this action [{$permission->value}].");
    }
}
