<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity\EventListener;

use Doctrine\Common\Cache\Cache;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Entity\EntityListener\PriceListEntityListener;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;
use OroB2B\Bundle\PricingBundle\TriggersFiller\PriceRuleTriggerFiller;

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
     * @var PriceRuleTriggerFiller|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRuleTriggersFiller;

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
        $this->priceRuleTriggersFiller = $this->getMockBuilder(PriceRuleTriggerFiller::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new PriceListEntityListener(
            $this->triggerHandler,
            $this->cache,
            $this->priceRuleTriggersFiller
        );
    }

    public function testPreUpdate()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('ar_42');
        $this->priceRuleTriggersFiller->expects($this->once())
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
        $this->priceRuleTriggersFiller->expects($this->never())
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
