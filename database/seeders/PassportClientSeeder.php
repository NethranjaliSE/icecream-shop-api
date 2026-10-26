<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use RuntimeException;

/**
 * POST /api/auth/login issues Passport personal access tokens, which need a
 * personal access client. Creating it here means `migrate:fresh --seed`
 * always leaves the API ready to log in (no extra passport:client step).
 */
class PassportClientSeeder extends Seeder
{
    public const CLIENT_NAME = 'Ice Cream Shop Personal Access Client';

    public function run(ClientRepository $clients): void
    {
        $provider = config('auth.guards.api.provider');

        try {
            $clients->personalAccessClient($provider);
        } catch (RuntimeException) {
            $clients->createPersonalAccessGrantClient(self::CLIENT_NAME, $provider);
        }
    }
}
