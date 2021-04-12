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

## Usage

Import the published schema into your main GraphQL schema (`./graphql/schema.graphql`)

```graphql
type Query
type Mutation

#import sanctum.grapqhl
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
