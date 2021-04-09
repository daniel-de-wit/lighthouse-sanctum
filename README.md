# Lighthouse Sanctum

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daniel-de-wit/lighthouse-sanctum.svg?style=flat-square)](https://packagist.org/packages/daniel-de-wit/lighthouse-sanctum)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/daniel-de-wit/lighthouse-sanctum/master.svg?style=flat-square)](https://travis-ci.org/daniel-de-wit/lighthouse-sanctum)
[![Coverage Status](https://coveralls.io/repos/github/daniel-de-wit/lighthouse-sanctum/badge.svg?branch=master)](https://coveralls.io/github/daniel-de-wit/lighthouse-sanctum?branch=master)
[![Quality Score](https://img.shields.io/scrutinizer/g/daniel-de-wit/lighthouse-sanctum.svg?style=flat-square)](https://scrutinizer-ci.com/g/daniel-de-wit/lighthouse-sanctum)
[![Total Downloads](https://img.shields.io/packagist/dt/daniel-de-wit/lighthouse-sanctum.svg?style=flat-square)](https://packagist.org/packages/daniel-de-wit/lighthouse-sanctum)

This package will add several graphql mutation and queries that will support working with [Laravel Sanctum](https://github.com/laravel/sanctum).

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
