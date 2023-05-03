<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\ProductKit\Checksum;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checksum\CompositeLineItemChecksumGenerator;
use Oro\Bundle\ShoppingListBundle\ProductKit\Checksum\LineItemChecksumGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompositeLineItemChecksumGeneratorTest extends TestCase
{
    private LineItemChecksumGeneratorInterface|MockObject $innerGenerator1;

    private LineItemChecksumGeneratorInterface|MockObject $innerGenerator2;

    private CompositeLineItemChecksumGenerator $generator;

    protected function setUp(): void
    {
        $this->innerGenerator1 = $this->createMock(LineItemChecksumGeneratorInterface::class);
        $this->innerGenerator2 = $this->createMock(LineItemChecksumGeneratorInterface::class);
        $this->generator = new CompositeLineItemChecksumGenerator([$this->innerGenerator1, $this->innerGenerator2]);
    }

    public function testGetChecksumWhenNoInnerGenerators(): void
    {
        self::assertNull($this->generator->getChecksum(new LineItem()));
    }

    public function testGetChecksumWhenNoSupportedInnerGenerators(): void
    {
        $lineItem = new LineItem();

        $this->innerGenerator1
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn(null);

        $this->innerGenerator2
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn(null);

        self::assertNull($this->generator->getChecksum(new LineItem()));
    }

    public function testGetChecksumWhenHasSupportedInnerGenerators(): void
    {
        $lineItem = new LineItem();
        $checksum = 'sample_string';
        $this->innerGenerator1
            ->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn($checksum);

        $this->innerGenerator2
            ->expects(self::never())
            ->method('getChecksum');

        self::assertEquals($checksum, $this->generator->getChecksum(new LineItem()));
    }
}
