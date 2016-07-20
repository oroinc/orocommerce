<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity\EventListener;

use Doctrine\Common\Cache\Cache;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Entity\EntityListener\PriceListEntityListener;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListChangeTriggerHandler;

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
     * @var PriceListEntityListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->triggerHandler = $this->getMockBuilder(PriceListChangeTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->cache = $this->getMock(Cache::class);
        $this->listener = new PriceListEntityListener($this->triggerHandler, $this->cache);
    }

    public function testPostUpdate()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('ar_42');
        $this->listener->postUpdate($priceList);
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
