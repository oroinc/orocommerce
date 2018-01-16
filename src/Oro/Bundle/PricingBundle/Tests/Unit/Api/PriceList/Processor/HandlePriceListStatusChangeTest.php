<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceList\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Update\UpdateProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceList\Processor\HandlePriceListStatusChange;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;

class HandlePriceListStatusChangeTest extends UpdateProcessorTestCase
{
    /**
     * @var PriceListRelationTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceListChangesHandler;

    /**
     * @var HandlePriceListStatusChange
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->priceListChangesHandler = $this->createMock(PriceListRelationTriggerHandler::class);

        $this->processor = new HandlePriceListStatusChange($this->priceListChangesHandler);
    }

    public function testProcessWrongType()
    {
        $this->priceListChangesHandler
            ->expects(static::never())
            ->method('handlePriceListStatusChange');

        $this->processor->process($this->context);
    }

    public function testProcessStatusNotChanged()
    {
        $this->priceListChangesHandler
            ->expects(static::never())
            ->method('handlePriceListStatusChange');

        $priceList = new PriceList();
        $priceList->setActive(true);

        $this->context->setResult($priceList);
        $this->processor->process($this->context);
        $this->processor->process($this->context);
    }

    public function testProcessStatusChanged()
    {
        $oldPriceList = new PriceList();
        $oldPriceList->setActive(false);

        $newPriceList = new PriceList();
        $newPriceList->setActive(true);

        $this->priceListChangesHandler
            ->expects(static::once())
            ->method('handlePriceListStatusChange')
            ->with($newPriceList);

        $this->context->setResult($oldPriceList);
        $this->processor->process($this->context);

        $this->context->setResult($newPriceList);
        $this->processor->process($this->context);
    }
}
