<?php

declare(strict_types=1);

namespace DanielDeWit\LighthouseSanctum\Tests\Unit\Services;

use DanielDeWit\LighthouseSanctum\Services\SignatureService;
use DanielDeWit\LighthouseSanctum\Tests\Unit\AbstractUnitTestCase;
use Illuminate\Routing\Exceptions\InvalidSignatureException;

class SignatureServiceTest extends AbstractUnitTestCase
{
    protected SignatureService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SignatureService('12345');
    }

    /**
     * @test
     */
    public function it_generates_a_signature(): void
    {
        $signature = $this->service->generate([
            'foo' => 'bar',
        ]);

        static::assertSame('31a8221d421cd534c64bc15b9d622bfe4c2c3195d127c5816a29dee8f498e9a9', $signature);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_if_the_signature_is_invalid(): void
    {
        static::expectException(InvalidSignatureException::class);

        $this->service->verify([
            'foo' => 'bar',
        ], 'foobar');
    }

    /**
     * @test
     */
    public function it_does_not_throw_an_exception_if_the_signature_is_valid(): void
    {
        $this->expectNotToPerformAssertions();

        $this->service->verify([
            'foo' => 'bar',
        ], '31a8221d421cd534c64bc15b9d622bfe4c2c3195d127c5816a29dee8f498e9a9');
    }
}
