<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\ProductKit\Checksum;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checksum\HashingLineItemChecksumGenerator;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checksum\LineItemChecksumGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HashingLineItemChecksumGeneratorTest extends TestCase
{
    private LineItemChecksumGeneratorInterface|MockObject $innerChecksumGenerator;

    private HashingLineItemChecksumGenerator $generator;

    protected function setUp(): void
    {
        $this->innerChecksumGenerator = $this->createMock(LineItemChecksumGeneratorInterface::class);
        $this->generator = new HashingLineItemChecksumGenerator($this->innerChecksumGenerator);
    }

    public function testGetChecksumWhenIsNull(): void
    {
        $lineItem = new LineItem();

        $this->innerChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn(null);

        self::assertNull($this->generator->getChecksum($lineItem));
    }

    public function testGetChecksumWhenNotNull(): void
    {
        $lineItem = new LineItem();

        $checksum = 'sample_string';
        $this->innerChecksumGenerator
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn($checksum);

        self::assertEquals(sha1($checksum), $this->generator->getChecksum($lineItem));
    }
}
