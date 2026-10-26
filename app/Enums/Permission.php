<?php

namespace App\Enums;

/**
 * Every action the API protects. Policies check these, never role names, so
 * the role → permission matrix lives in exactly one place (Role::permissions()).
 */
enum Permission: string
{
    case ViewItems = 'items.view';
    case CreateItems = 'items.create';
    case UpdateItems = 'items.update';
    case DeleteItems = 'items.delete';

    case ViewCustomers = 'customers.view';
    case CreateCustomers = 'customers.create';
    case UpdateCustomers = 'customers.update';
    case DeleteCustomers = 'customers.delete';
}
