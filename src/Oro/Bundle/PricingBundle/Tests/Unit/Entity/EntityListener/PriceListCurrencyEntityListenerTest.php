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

class PriceListCurrencyEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListTriggerHandler;

    /** @var PriceListCurrencyEntityListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->cache = $this->createMock(Cache::class);
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);

        $this->listener = new PriceListCurrencyEntityListener(
            $this->cache,
            $this->priceListTriggerHandler
        );
    }

    public function testPostPersistDisabled()
    {
        $this->listener->setEnabled(false);
        $this->priceListTriggerHandler->expects($this->never())
            ->method($this->anything());

        $this->listener->postPersist(new PriceListCurrency());
    }

    public function testPostPersist()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        /** @var PriceRule $priceRule */
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 4]);
        $priceList->addPriceRule($priceRule);

        $priceListCurrency = new PriceListCurrency();
        $priceListCurrency->setPriceList($priceList);

        $this->priceListTriggerHandler->expects($this->exactly(2))
            ->method('handlePriceListTopic')
            ->withConsecutive(
                [Topics::RESOLVE_COMBINED_CURRENCIES, $priceList],
                [Topics::RESOLVE_PRICE_RULES, $priceList]
            );

        $this->cache->expects($this->once())
            ->method('delete')
            ->withConsecutive(
                ['pr_4']
            );

        $this->listener->postPersist($priceListCurrency);
        $this->assertFalse($priceList->isActual());
    }

    public function testPostPersistWithoutRules()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        $priceListCurrency = new PriceListCurrency();
        $priceListCurrency->setPriceList($priceList);

        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(Topics::RESOLVE_COMBINED_CURRENCIES, $priceList);

        $this->listener->postPersist($priceListCurrency);
        $this->assertTrue($priceList->isActual());
    }

    public function testPreRemoveDisabled()
    {
        $this->listener->setEnabled(false);
        $this->priceListTriggerHandler->expects($this->never())
            ->method($this->anything());

        $this->listener->preRemove(new PriceListCurrency());
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

        $this->priceListTriggerHandler->expects($this->exactly(2))
            ->method('handlePriceListTopic')
            ->withConsecutive(
                [Topics::RESOLVE_COMBINED_CURRENCIES, $priceList],
                [Topics::RESOLVE_PRICE_RULES, $priceList]
            );

        $this->cache->expects($this->once())
            ->method('delete')
            ->withConsecutive(
                ['pr_4']
            );

        $this->listener->preRemove($priceListCurrency);
        $this->assertFalse($priceList->isActual());
    }
}
