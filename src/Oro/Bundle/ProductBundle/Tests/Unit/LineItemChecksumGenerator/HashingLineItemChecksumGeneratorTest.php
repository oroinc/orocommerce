<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\LineItemChecksumGenerator;

use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\HashingLineItemChecksumGenerator;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HashingLineItemChecksumGeneratorTest extends TestCase
{
    private LineItemChecksumGeneratorInterface|MockObject $innerChecksumGenerator;

    private HashingLineItemChecksumGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerChecksumGenerator = $this->createMock(LineItemChecksumGeneratorInterface::class);
        $this->generator = new HashingLineItemChecksumGenerator($this->innerChecksumGenerator);
    }

    public function testGetChecksumWhenIsNull(): void
    {
        $lineItem = new ProductLineItemStub(1);

        $this->innerChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn(null);

        self::assertNull($this->generator->getChecksum($lineItem));
    }

    public function testGetChecksumWhenNotNull(): void
    {
        $lineItem = new ProductLineItemStub(1);

        $checksum = 'sample_string';
        $this->innerChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn($checksum);

        self::assertEquals(sha1($checksum), $this->generator->getChecksum($lineItem));
    }
}
