<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Integration\GraphQL\Mutations;

use DanielDeWit\LighthouseSanctum\Tests\Integration\AbstractIntegrationTest;
use DanielDeWit\LighthouseSanctum\Tests\stubs\Users\UserHasApiTokens;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class UpdatePasswordTest extends AbstractIntegrationTest
{
    /**
     * @test
     */
    public function it_updates_the_password(): void
    {
        $user = $this->actAsUser();

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                updatePassword(input: {
                    current_password: "mypass",
                    password: "secret",
                    password_confirmation: "secret"
                }) {
                    status
                }
            }
        ')->assertJson([
            'data' => [
                'updatePassword' => [
                    'status' => 'PASSWORD_UPDATED',
                ],
            ],
        ]);

        $user->refresh();

        static::assertTrue(Hash::check('secret', $user->getAuthPassword()));
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_user_is_unauthenticated(): void
    {
        $this->graphQL(/** @lang GraphQL */'
            mutation {
                updatePassword(input: {
                    current_password: "mypass",
                    password: "secret",
                    password_confirmation: "secret"
                }) {
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Unauthenticated.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_current_password_is_not_the_same(): void
    {
        $this->actAsUser();

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                updatePassword(input: {
                    current_password: "otherpass",
                    password: "secret",
                    password_confirmation: "secret"
                }) {
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [updatePassword].')
            ->assertGraphQLValidationError(
                'input.current_password',
                'The current_password and user password must match.',
            );
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_new_password_is_not_different(): void
    {
        $this->actAsUser();

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                updatePassword(input: {
                    current_password: "mypass",
                    password: "mypass",
                    password_confirmation: "mypass"
                }) {
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [updatePassword].')
            ->assertGraphQLValidationError(
                'input.password',
                'The password and user password must be different.',
            );
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_current_password_field_is_missing(): void
    {
        $this->actAsUser();

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                updatePassword(input: {
                    password: "secret",
                    password_confirmation: "secret"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage(
            'Field UpdatePasswordInput.current_password of required type String! was not provided.'
        );
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_current_password_field_is_not_a_string(): void
    {
        $this->actAsUser();

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                updatePassword(input: {
                    current_password: 12345,
                    password: "secret",
                    password_confirmation: "secret"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field "updatePassword" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_field_is_missing(): void
    {
        $this->actAsUser();

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                updatePassword(input: {
                    current_password: "mypass",
                    password_confirmation: "secret"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field UpdatePasswordInput.password of required type String! was not provided.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_field_is_not_a_string(): void
    {
        $this->actAsUser();

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                updatePassword(input: {
                    current_password: "mypass",
                    password: 12345,
                    password_confirmation: "secret"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field "updatePassword" argument "input" requires type String!, found 12345.');
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_field_is_not_confirmed(): void
    {
        $this->actAsUser();

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                updatePassword(input: {
                    current_password: "mypass",
                    password: "secret",
                    password_confirmation: "somethingelse"
                }) {
                    status
                }
            }
        ')
            ->assertGraphQLErrorMessage('Validation failed for the field [updatePassword].')
            ->assertGraphQLValidationError(
                'input.password',
                'The input.password confirmation does not match.',
            );
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_confirmation_field_is_missing(): void
    {
        $this->actAsUser();

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                updatePassword(input: {
                    current_password: "mypass",
                    password: "secret"
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage(
            'Field UpdatePasswordInput.password_confirmation of required type String! was not provided.'
        );
    }

    /**
     * @test
     */
    public function it_returns_an_error_if_the_password_confirmation_field_is_not_a_string(): void
    {
        $this->actAsUser();

        $this->graphQL(/** @lang GraphQL */'
            mutation {
                updatePassword(input: {
                    current_password: "mypass",
                    password: "secret",
                    password_confirmation: 12345
                }) {
                    status
                }
            }
        ')->assertGraphQLErrorMessage('Field "updatePassword" argument "input" requires type String!, found 12345.');
    }

    protected function actAsUser(): UserHasApiTokens
    {
        /** @var UserHasApiTokens $user */
        $user = UserHasApiTokens::factory()->create([
            'password' => Hash::make('mypass'),
        ]);

        Sanctum::actingAs($user);

        return $user;
    }
}
