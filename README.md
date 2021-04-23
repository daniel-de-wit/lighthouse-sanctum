# Lighthouse Sanctum

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/daniel-de-wit/lighthouse-sanctum/run-tests?label=tests)](https://github.com/daniel-de-wit/lighthouse-sanctum/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Coverage Status](https://coveralls.io/repos/github/daniel-de-wit/lighthouse-sanctum/badge.svg?branch=master)](https://coveralls.io/github/daniel-de-wit/lighthouse-sanctum?branch=master)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/daniel-de-wit/lighthouse-sanctum.svg?style=flat-square)](https://packagist.org/packages/daniel-de-wit/lighthouse-sanctum)
[![Total Downloads](https://img.shields.io/packagist/dt/daniel-de-wit/lighthouse-sanctum.svg?style=flat-square)](https://packagist.org/packages/daniel-de-wit/lighthouse-sanctum)

Add [Laravel Sanctum](https://github.com/laravel/sanctum) support to [Lighthouse](https://github.com/nuwave/lighthouse)

## Requirements

- [laravel/laravel:^8.36.2](https://github.com/laravel/laravel)
- [laravel/sanctum:^2.0](https://github.com/laravel/sanctum)
- [nuwave/lighthouse:^5.0](https://github.com/nuwave/lighthouse)

## Installation

#### 1. Install using composer:

```bash
composer require daniel-de-wit/lighthouse-sanctum
```

#### 2. Publish configuration and schema

```bash
php artisan vendor:publish --tag=lighthouse-sanctum
```

#### 3. Import the published schema into your main GraphQL schema (`./graphql/schema.graphql`)

```graphql
type Query
type Mutation

#import sanctum.grapqhl
```

#### 4. HasApiTokens

Apply the `Laravel\Sanctum\HasApiTokens` trait to your Authenticatable model as [described in the Laravel Sanctum documentation](https://laravel.com/docs/8.x/sanctum#issuing-api-tokens).

```php
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\Contracts\HasApiTokens as HasApiTokensContract;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements HasApiTokensContract
{
    use HasApiTokens;
}

```

#### 5. Configuration

This package relies on [API Token Authentication](https://laravel.com/docs/8.x/sanctum#api-token-authentication), which uses stateless Bearer tokens to authenticate requests.

By default, [Laravel Sanctum](https://laravel.com/docs/8.x/sanctum) assumes that requests made from localhost should use the stateful [Spa Authentication](https://laravel.com/docs/8.x/sanctum#spa-authentication) instead.
To disable this behaviour, remove any lines in your sanctum configuration:

```php
// File: ./config/sanctum.php

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => [
        // Remove entries here    
    ],
```

Make sure the following middleware is enabled for Lighthouse:

```php
// File: ./config/lighthouse.php
    'middleware' => [
        ...
        
        \Nuwave\Lighthouse\Support\Http\Middleware\AttemptAuthentication::class,

        ...
    ],
```

## Usage

- [Login](#login)
- [Logout](#logout)
- [Register](#register)
- [Email Verification](#email-verification)
- [Forgot Password](#forgot-password)

### Login

Authenticate the user to receive a Bearer token.

```graphql
mutation Login {
    login(input: {
        email: "john.doe@gmail.com"
        password: "secret"
    }) {
        token
    }
}
```

Apply the Authorization header on subsequent calls using the token

```json
  "Authorization": "Bearer 1|lJo1cMhrW9tIUuGwlV1EPjKnvfZKzvgpGgplbwX9"
```

### Logout

Revoke the current token.

```graphql
mutation Logout {
    logout {
        status
        message
    }
}
```

### Register

Successfully registering a user will immediately yield a bearer token (unless email verification is required).

```graphql
mutation Register {
    register(input: {
        name: "John Doe"
        email: "john.doe@gmail.com"
        password: "secret"
        password_confirmation: "secret"
    }) {
        token
        status
    }
}
```

When registering a user in combination with the `MustVerifyEmail` contract you can optionally define the url for email verification.
Both `__ID__` and `__HASH__` will be replaced with the proper values.

```graphql
mutation Register {
    register(input: {
        name: "John Doe"
        email: "john.doe@gmail.com"
        password: "secret"
        password_confirmation: "secret"
        verification_url: {
            url: "https://my-front-end.com/verify-email?id=__ID__&token=__HASH__"
        }
    }) {
        token
        status
    }
}
```

### Email Verification

```graphql
mutation VerifyEmail {
  verifyEmail(input: {
    id: "1"
    hash: "af269947ed80d4a7bc3f78a6dfd05ec369373f9d"
  }) {
    name
    email
  }
}
```

### Forgot Password

Sends a reset password notification.

Optionally use custom reset url using both `__EMAIL__` and `__TOKEN__` placeholders.

```graphql
mutation ForgotPassword {
    forgotPassword(input: {
        email: "john.doe@gmail.com"
        reset_password_url: {
            url: "https://my-front-end.com/reset-password?email=__EMAIL__&token=__TOKEN__"
        }
    }) {
        status
        message
    }
}
```

## Testing

```bash
composer test
```

## Coverage

```bash
composer coverage
```

## Static Analysis

```bash
composer analyze
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Daniel de Wit](https://github.com/daniel-de-wit)
- [wimski](https://github.com/wimski)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
