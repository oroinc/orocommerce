<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ChainDefaultProductUnitProvider;
use Oro\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;

class ChainDefaultProductUnitProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ChainDefaultProductUnitProvider */
    private $chainProvider;

    /** @var DefaultProductUnitProviderInterface */
    private $highPriorityProvider;

    /** @var DefaultProductUnitProviderInterface */
    private $lowPriorityProvider;

    /** @var ProductUnitPrecision */
    private $unitPrecision;

    protected function setUp(): void
    {
        $this->unitPrecision = new ProductUnitPrecision();

        $this->highPriorityProvider = $this->createMock(DefaultProductUnitProviderInterface::class);
        $this->lowPriorityProvider = $this->createMock(DefaultProductUnitProviderInterface::class);

        $this->chainProvider = new ChainDefaultProductUnitProvider([
            $this->highPriorityProvider,
            $this->lowPriorityProvider
        ]);
    }

    public function testGetDefaultProductUnitPrecisionByHighPriorityProvider()
    {
        $this->highPriorityProvider->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->willReturn($this->unitPrecision);
        $this->lowPriorityProvider->expects($this->never())
            ->method('getDefaultProductUnitPrecision');

        $this->assertSame($this->unitPrecision, $this->chainProvider->getDefaultProductUnitPrecision());
    }

    public function testGetDefaultProductUnitPrecisionByLowPriorityProvider()
    {
        $this->highPriorityProvider->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->willReturn(null);
        $this->lowPriorityProvider->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->willReturn($this->unitPrecision);

        $this->assertSame($this->unitPrecision, $this->chainProvider->getDefaultProductUnitPrecision());
    }

    public function testGetDefaultProductUnitPrecisionNone()
    {
        $this->highPriorityProvider->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->willReturn(null);
        $this->lowPriorityProvider->expects($this->once())
            ->method('getDefaultProductUnitPrecision')
            ->willReturn(null);

        $this->assertNull($this->chainProvider->getDefaultProductUnitPrecision());
    }
}
