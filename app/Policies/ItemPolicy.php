<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Item;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Auth\Access\Response;

/**
 * Owner: view/create/update/delete. Manager: view/create/update.
 * Cashier: view/update only.
 */
class ItemPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): Response
    {
        return $this->permit($user, Permission::ViewItems);
    }

    public function view(User $user, Item $item): Response
    {
        return $this->permit($user, Permission::ViewItems);
    }

    public function create(User $user): Response
    {
        return $this->permit($user, Permission::CreateItems);
    }

    public function update(User $user, Item $item): Response
    {
        return $this->permit($user, Permission::UpdateItems);
    }

    public function delete(User $user, Item $item): Response
    {
        return $this->permit($user, Permission::DeleteItems);
    }
}
