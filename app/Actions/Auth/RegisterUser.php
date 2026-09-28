<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Tenancy\CreateOrganization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

final readonly class RegisterUser
{
    public function __construct(private CreateOrganization $createOrganization) {}

    /**
     * @param  array{name: string, email: string, password: string}  $input
     */
    public function handle(array $input): User
    {
        $user = User::create($input);

        $this->createOrganization->handle([
            'name' => $input['name']."'s workspace",
            'slug' => $input['name'].' workspace',
            'owner' => $user,
        ]);

        event(new Registered($user));

        return $user;
    }
}
