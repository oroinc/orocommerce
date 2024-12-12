<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\LineItemChecksumGenerator;

use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\CompositeLineItemChecksumGenerator;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompositeLineItemChecksumGeneratorTest extends TestCase
{
    private LineItemChecksumGeneratorInterface|MockObject $innerGenerator1;
    private LineItemChecksumGeneratorInterface|MockObject $innerGenerator2;
    private CompositeLineItemChecksumGenerator $generator;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerGenerator1 = $this->createMock(LineItemChecksumGeneratorInterface::class);
        $this->innerGenerator2 = $this->createMock(LineItemChecksumGeneratorInterface::class);

        $this->generator = new CompositeLineItemChecksumGenerator([
            $this->innerGenerator1,
            $this->innerGenerator2
        ]);
    }

    public function testGetChecksumWhenNoSupportedInnerGenerators(): void
    {
        $lineItem = new ProductLineItemStub(1);

        $this->innerGenerator1->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn(null);

        $this->innerGenerator2->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn(null);

        self::assertNull($this->generator->getChecksum(new ProductLineItemStub(1)));
    }

    public function testGetChecksumWhenHasSupportedInnerGenerators(): void
    {
        $lineItem = new ProductLineItemStub(1);
        $checksum = 'sample_string';
        $this->innerGenerator1->expects(self::once())
            ->method('getChecksum')
            ->with($lineItem)
            ->willReturn($checksum);

        $this->innerGenerator2->expects(self::never())
            ->method('getChecksum');

        self::assertEquals($checksum, $this->generator->getChecksum(new ProductLineItemStub(1)));
    }
}
