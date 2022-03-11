<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListStatusHandler;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListStatusHandlerInterface;

class CombinedPriceListStatusHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testStatusWhenFirstHandlerIsPositive(): void
    {
        $firstHandler = $this->createMock(CombinedPriceListStatusHandlerInterface::class);
        $firstHandler
            ->expects($this->once())
            ->method('isReadyForBuild')
            ->willReturn(true);
        $secondHandler = $this->createMock(CombinedPriceListStatusHandlerInterface::class);
        $secondHandler
            ->expects($this->never())
            ->method('isReadyForBuild');

        $handler = new CombinedPriceListStatusHandler([$firstHandler, $secondHandler]);
        $this->assertTrue($handler->isReadyForBuild(new CombinedPriceList()));
    }

    public function testStatusWhenSecondHandlerIsPositive(): void
    {
        $firstHandler = $this->createMock(CombinedPriceListStatusHandlerInterface::class);
        $firstHandler
            ->expects($this->once())
            ->method('isReadyForBuild')
            ->willReturn(false);
        $secondHandler = $this->createMock(CombinedPriceListStatusHandlerInterface::class);
        $secondHandler
            ->expects($this->once())
            ->method('isReadyForBuild')
            ->willReturn(true);

        $handler = new CombinedPriceListStatusHandler([$firstHandler, $secondHandler]);
        $this->assertTrue($handler->isReadyForBuild(new CombinedPriceList()));
    }

    public function testStatusWhenHandlersNotPositive(): void
    {
        $firstHandler = $this->createMock(CombinedPriceListStatusHandlerInterface::class);
        $firstHandler
            ->expects($this->once())
            ->method('isReadyForBuild')
            ->willReturn(false);
        $secondHandler = $this->createMock(CombinedPriceListStatusHandlerInterface::class);
        $secondHandler
            ->expects($this->once())
            ->method('isReadyForBuild')
            ->willReturn(false);

        $handler = new CombinedPriceListStatusHandler([$firstHandler, $secondHandler]);
        $this->assertFalse($handler->isReadyForBuild(new CombinedPriceList()));
    }

    public function testStatusWhenHandlersNotExists(): void
    {
        $handler = new CombinedPriceListStatusHandler([]);
        $this->assertFalse($handler->isReadyForBuild(new CombinedPriceList()));
    }
}
