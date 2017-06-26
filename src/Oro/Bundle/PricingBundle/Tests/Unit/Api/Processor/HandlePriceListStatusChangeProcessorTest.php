<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\PricingBundle\Api\Processor\HandlePriceListStatusChangeProcessor;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\TestCase;

class HandlePriceListStatusChangeProcessorTest extends TestCase
{
    /**
     * @var PriceListRelationTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceListChangesHandler;

    /**
     * @var HandlePriceListStatusChangeProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->priceListChangesHandler = $this->createMock(PriceListRelationTriggerHandler::class);

        $this->processor = new HandlePriceListStatusChangeProcessor($this->priceListChangesHandler);
    }

    public function testProcessNoData()
    {
        $this->priceListChangesHandler->expects(static::any())
            ->method('handlePriceListStatusChange');

        $context = $this->createMock(ContextInterface::class);

        $this->processor->process($context);
    }

    public function testProcessStatusNotChanged()
    {
        $this->priceListChangesHandler->expects(static::any())
            ->method('handlePriceListStatusChange');

        $priceList = new PriceList();
        $priceList->setActive(true);

        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::any())
            ->method('getResult')
            ->willReturn($priceList);

        $this->processor->process($context);
        $this->processor->process($context);
    }

    public function testProcessStatusChanged()
    {
        $oldPriceList = new PriceList();
        $oldPriceList->setActive(false);

        $newPriceList = new PriceList();
        $newPriceList->setActive(true);

        $this->priceListChangesHandler->expects(static::once())
            ->method('handlePriceListStatusChange')
            ->with($newPriceList);

        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::at(0))
            ->method('getResult')
            ->willReturn($oldPriceList);

        $context->expects(static::at(1))
            ->method('getResult')
            ->willReturn($newPriceList);

        $this->processor->process($context);
        $this->processor->process($context);
    }
}
