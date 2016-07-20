<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity\EventListener;

use Doctrine\Common\Cache\Cache;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Entity\EntityListener\PriceRuleEntityListener;
use OroB2B\Bundle\PricingBundle\Entity\PriceRule;

class PriceRuleEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var PriceRuleEntityListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->cache = $this->getMock(Cache::class);
        $this->listener = new PriceRuleEntityListener($this->cache);
    }

    public function testPostUpdate()
    {
        /** @var PriceRule $priceRule */
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 42]);
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('pr_42');
        $this->listener->postUpdate($priceRule);
    }

    public function testPreRemove()
    {
        /** @var PriceRule $priceRule */
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 2]);
        $this->cache->expects($this->once())
            ->method('delete')
            ->with('pr_2');
        $this->listener->preRemove($priceRule);
    }
}
