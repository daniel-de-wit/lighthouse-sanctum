<?php

declare(strict_types=1);

namespace DanielDeWit\LighthousePaperclip\Tests\Directives;

use DanielDeWit\LighthousePaperclip\Providers\LighthousePaperclipServiceProvider;
use GraphQL\Type\Schema;
use Nuwave\Lighthouse\GraphQL;
use Nuwave\Lighthouse\LighthouseServiceProvider;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Nuwave\Lighthouse\Testing\MocksResolvers;
use Nuwave\Lighthouse\Testing\UsesTestSchema;
use Orchestra\Testbench\TestCase;

abstract class DirectiveTest extends TestCase
{
    use MakesGraphQLRequests;
    use MocksResolvers;
    use UsesTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpTestSchema();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LighthouseServiceProvider::class,
            LighthousePaperclipServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $app['config'];

        $config->set('app.debug', true);
    }

    /**
     * Build an executable schema from an SDL string.
     */
    protected function buildSchema(string $schema): Schema
    {
        $this->schema = $schema;

        return $this->app
            ->make(GraphQL::class)
            ->prepSchema();
    }
}
