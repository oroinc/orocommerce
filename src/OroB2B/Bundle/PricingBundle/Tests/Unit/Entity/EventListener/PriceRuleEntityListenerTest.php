<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity\EventListener;

use Doctrine\Common\Cache\Cache;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Entity\EntityListener\PriceRuleEntityListener;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\TriggersFiller\PriceRuleTriggerFiller;

class PriceRuleEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var PriceRuleTriggerFiller|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRuleTriggersFiller;

    /**
     * @var PriceRuleEntityListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->cache = $this->getMock(Cache::class);
        $this->priceRuleTriggersFiller = $this->getMockBuilder(PriceRuleTriggerFiller::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new PriceRuleEntityListener($this->cache, $this->priceRuleTriggersFiller);
    }

    public function testPreUpdate()
    {
        $priceList = new PriceList();
        /** @var PriceRule $priceRule */
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 42, 'priceList' => $priceList]);
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('pr_42');
        $this->priceRuleTriggersFiller->expects($this->once())
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
        $this->priceRuleTriggersFiller->expects($this->once())
            ->method('addTriggersForPriceList')
            ->with($priceList);
        $this->listener->preRemove($priceRule);
    }
}
