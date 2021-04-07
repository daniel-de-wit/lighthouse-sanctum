<?php

declare(strict_types=1);

namespace DanielDeWit\LighthousePaperclip\Tests\Directives;

use Czim\Paperclip\Attachment\Attachment;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Mockery\MockInterface;

class AttachmentDirectiveTest extends DirectiveTest
{
    /** @test */
    public function it_returns_the_attachment_url(): void
    {
        /** @var Attachment|MockInterface $attachment */
        $attachment = Mockery::mock(Attachment::class, [
            'url' => 'https://domain.com/small',
        ]);

        Mockery::mock(Model::class, [
            'getAttribute' => $attachment,
        ]);

        $this->mockResolver($attachment);

        $this->schema = /** @lang GraphQL */
            '
            type Query {
                image: String @attachment @mock
            }
        ';

        $this->graphQL(/** @lang GraphQL */ '
        {
            image
        }
        ')->assertExactJson([
            'data' => [
                'image' => 'https://domain.com/small',
            ],
        ]);
    }
}
