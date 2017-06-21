<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Entity\EntityListener\HandlePriceListStatusChangeListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use PHPUnit\Framework\TestCase;

class HandlePriceListStatusChangeListenerTest extends TestCase
{
    /**
     * @var PriceListRelationTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceListChangesHandler;

    /**
     * @var HandlePriceListStatusChangeListener
     */
    private $listener;

    protected function setUp()
    {
        $this->priceListChangesHandler = $this->createMock(PriceListRelationTriggerHandler::class);

        $this->listener = new HandlePriceListStatusChangeListener($this->priceListChangesHandler);
    }

    public function testPostUpdateForNotChangedActiveField()
    {
        $this->priceListChangesHandler->expects(static::any())
            ->method('handlePriceListStatusChange');

        $this->listener->postUpdate(new PriceList());
    }

    public function testPostUpdateForChangedActiveField()
    {
        $event = $this->createMock(PreUpdateEventArgs::class);
        $event->expects(static::once())
            ->method('hasChangedField')
            ->with('active')
            ->willReturn(true);

        $priceList = new PriceList();

        $this->listener->preUpdate($priceList, $event);

        $this->priceListChangesHandler->expects(static::once())
            ->method('handlePriceListStatusChange');

        $this->listener->postUpdate($priceList);
    }
}
