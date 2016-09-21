<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\EntityListener\PriceListCurrencyEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceListCurrencyEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var PriceListTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceListTriggerHandler;

    /**
     * @var PriceListCurrencyEntityListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->cache = $this->getMock(Cache::class);
        $this->priceListTriggerHandler = $this->getMockBuilder(PriceListTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PriceListCurrencyEntityListener(
            $this->cache,
            $this->priceListTriggerHandler
        );
    }

    public function testPrePersist()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        /** @var PriceRule $priceRule */
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 4]);
        $priceList->addPriceRule($priceRule);

        $priceListCurrency = new PriceListCurrency();
        $priceListCurrency->setPriceList($priceList);

        $this->priceListTriggerHandler->expects($this->once())
            ->method('addTriggerForPriceList')
            ->with(Topics::RESOLVE_PRICE_RULES, $priceList);

        $this->cache->expects($this->once())
            ->method('delete')
            ->withConsecutive(
                ['pr_4']
            );

        $this->listener->prePersist($priceListCurrency);
        $this->assertFalse($priceList->isActual());
    }

    public function testPrePersistWithoutRules()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $priceListCurrency = new PriceListCurrency();
        $priceListCurrency->setPriceList($priceList);

        $this->priceListTriggerHandler->expects($this->never())
            ->method('addTriggerForPriceList');
        $this->listener->prePersist($priceListCurrency);
        $this->assertTrue($priceList->isActual());
    }

    public function testPreRemove()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        /** @var PriceRule $priceRule */
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 4]);
        $priceList->addPriceRule($priceRule);

        $priceListCurrency = new PriceListCurrency();
        $priceListCurrency->setPriceList($priceList);

        $this->priceListTriggerHandler->expects($this->once())
            ->method('addTriggerForPriceList')
            ->with(Topics::RESOLVE_PRICE_RULES, $priceList);

        $this->cache->expects($this->once())
            ->method('delete')
            ->withConsecutive(
                ['pr_4']
            );

        $this->listener->preRemove($priceListCurrency);
        $this->assertFalse($priceList->isActual());
    }
}
