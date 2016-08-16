<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity\EventListener;

use Doctrine\Common\Cache\Cache;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Entity\EntityListener\PriceRuleEntityListener;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Model\PriceRuleChangeTriggerHandler;

class PriceRuleEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var PriceRuleChangeTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRuleChangeTriggerHandler;

    /**
     * @var PriceRuleEntityListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->cache = $this->getMock(Cache::class);
        $this->priceRuleChangeTriggerHandler = $this->getMockBuilder(PriceRuleChangeTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new PriceRuleEntityListener($this->cache, $this->priceRuleChangeTriggerHandler);
    }

    public function testPreUpdate()
    {
        $priceList = new PriceList();
        /** @var PriceRule $priceRule */
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 42, 'priceList' => $priceList]);
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('pr_42');
        $this->priceRuleChangeTriggerHandler->expects($this->once())
            ->method('addTriggersForPriceList')
            ->with($priceList);
        $this->listener->preUpdate($priceRule);
    }

    public function testPreRemove()
    {
        $priceList = new PriceList();
        /** @var PriceRule $priceRule */
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 2, 'priceList' => $priceList]);
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('pr_2');
        $this->priceRuleChangeTriggerHandler->expects($this->once())
            ->method('addTriggersForPriceList')
            ->with($priceList);
        $this->listener->preRemove($priceRule);
    }
}
