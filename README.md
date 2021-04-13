# Lighthouse Sanctum

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/daniel-de-wit/lighthouse-sanctum/run-tests?label=tests)](https://github.com/daniel-de-wit/lighthouse-sanctum/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/daniel-de-wit/lighthouse-sanctum.svg?style=flat-square)](https://packagist.org/packages/daniel-de-wit/lighthouse-sanctum)
[![Total Downloads](https://img.shields.io/packagist/dt/daniel-de-wit/lighthouse-sanctum.svg?style=flat-square)](https://packagist.org/packages/daniel-de-wit/lighthouse-sanctum)

Add [Laravel Sanctum](https://github.com/laravel/sanctum) support to [Lighthouse](https://github.com/nuwave/lighthouse)

## Requirements

- [laravel/laravel:^8.36.2](https://github.com/laravel/laravel)
- [laravel/sanctum:^2.0](https://github.com/laravel/sanctum)
- [nuwave/lighthouse:^5.0](https://github.com/nuwave/lighthouse)

## Installation

1. Install using composer:

```bash
composer require daniel-de-wit/lighthouse-sanctum
```

2. Publish configuration and schema

```bash
php artisan vendor:publish --tag=lighthouse-sanctum
```

3. Import the published schema into your main GraphQL schema (`./graphql/schema.graphql`)

```graphql
type Query
type Mutation

#import sanctum.grapqhl
```

## Usage

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

When registering a user while implementing the `MustVerifyEmail` contract you can optionally define the url for email verification.
Both `{{ID}}` and `{{HASH}}` will be replaced with the proper values.

```graphql
mutation Register {
    register(input: {
        name: "John Doe"
        email: "john.doe@gmail.com"
        password: "secret"
        password_confirmation: "secret"
        verification_url: "https://my-front-end.com/verify?id={{ID}}&token={{HASH}}"
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

### Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Daniel de Wit](https://github.com/daniel-de-wit)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
