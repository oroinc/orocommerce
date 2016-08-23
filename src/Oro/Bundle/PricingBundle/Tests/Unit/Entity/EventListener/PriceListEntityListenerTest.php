<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EventListener;

use Doctrine\Common\Cache\Cache;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PricingBundle\Entity\EntityListener\PriceListEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleChangeTriggerHandler;

class PriceListEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PriceListChangeTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerHandler;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var PriceRuleChangeTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRuleChangeTriggerHandler;

    /**
     * @var PriceListEntityListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->triggerHandler = $this->getMockBuilder(PriceListChangeTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache = $this->getMock(Cache::class);
        $this->priceRuleChangeTriggerHandler = $this->getMockBuilder(PriceRuleChangeTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new PriceListEntityListener(
            $this->triggerHandler,
            $this->cache,
            $this->priceRuleChangeTriggerHandler
        );
    }

    public function testPreUpdate()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('ar_42');
        $this->priceRuleChangeTriggerHandler->expects($this->once())
            ->method('addTriggersForPriceList')
            ->with($priceList);

        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('hasChangedField')
            ->with('productAssignmentRule')
            ->willReturn(true);
        $this->listener->preUpdate($priceList, $event);
    }

    public function testPreUpdateNoChanges()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $this->cache->expects($this->never())
            ->method('delete');
        $this->priceRuleChangeTriggerHandler->expects($this->never())
            ->method('addTriggersForPriceList');

        /** @var PreUpdateEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('hasChangedField')
            ->with('productAssignmentRule')
            ->willReturn(false);
        $this->listener->preUpdate($priceList, $event);
    }

    public function testPreRemove()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 2]);
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('ar_2');
        $this->triggerHandler->expects($this->once())
            ->method('handleFullRebuild');
        $this->listener->preRemove($priceList);
    }
}
