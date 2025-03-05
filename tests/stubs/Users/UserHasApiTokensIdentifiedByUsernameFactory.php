<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\stubs\Users;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Orchestra\Testbench\Factories\UserFactory;

/**
 * @extends UserFactory<UserHasApiTokensIdentifiedByUsername>
 */
class UserHasApiTokensIdentifiedByUsernameFactory extends UserFactory
{
    /**
     * @var class-string<Model>
     */
    protected $model = UserHasApiTokensIdentifiedByUsername::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => $this->faker->name(),
            'username'          => $this->faker->userName(),
            'email_verified_at' => now(),
            'password'          => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token'    => Str::random(10),
        ];
    }
}
