<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\ChecksPermissions;
use Illuminate\Auth\Access\Response;

/**
 * Owner: view/create/update/delete. Manager and Cashier: view/create/update.
 */
class CustomerPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): Response
    {
        return $this->permit($user, Permission::ViewCustomers);
    }

    public function view(User $user, Customer $customer): Response
    {
        return $this->permit($user, Permission::ViewCustomers);
    }

    public function create(User $user): Response
    {
        return $this->permit($user, Permission::CreateCustomers);
    }

    public function update(User $user, Customer $customer): Response
    {
        return $this->permit($user, Permission::UpdateCustomers);
    }

    public function delete(User $user, Customer $customer): Response
    {
        return $this->permit($user, Permission::DeleteCustomers);
    }
}
