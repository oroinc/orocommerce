<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceListCurrenciesTopic;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\PricingBundle\Entity\EntityListener\PriceListCurrencyEntityListener;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListCurrency;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Component\Testing\Unit\EntityTrait;

class PriceListCurrencyEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RuleCache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var PriceListTriggerHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $priceListTriggerHandler;

    /**
     * @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject
     */
    private $featureChecker;

    /** @var PriceListCurrencyEntityListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->cache = $this->createMock(RuleCache::class);
        $this->priceListTriggerHandler = $this->createMock(PriceListTriggerHandler::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->listener = new PriceListCurrencyEntityListener(
            $this->cache,
            $this->priceListTriggerHandler
        );
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('oro_price_lists_combined');
    }

    public function testPostPersistDisabled()
    {
        $this->listener->setEnabled(false);
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);
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

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);
        $this->priceListTriggerHandler->expects($this->exactly(2))
            ->method('handlePriceListTopic')
            ->withConsecutive(
                [ResolveCombinedPriceListCurrenciesTopic::getName(), $priceList],
                [ResolvePriceRulesTopic::getName(), $priceList]
            );

        $this->cache->expects($this->once())
            ->method('delete')
            ->withConsecutive(
                ['pr_4']
            );

        $this->listener->postPersist($priceListCurrency);
        $this->assertFalse($priceList->isActual());
    }

    public function testPostPersistFeatureDisabled()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        /** @var PriceRule $priceRule */
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 4]);
        $priceList->addPriceRule($priceRule);

        $priceListCurrency = new PriceListCurrency();
        $priceListCurrency->setPriceList($priceList);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(false);
        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(ResolvePriceRulesTopic::getName(), $priceList);

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

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);
        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(ResolveCombinedPriceListCurrenciesTopic::getName(), $priceList);

        $this->listener->postPersist($priceListCurrency);
        $this->assertTrue($priceList->isActual());
    }

    public function testPreRemoveDisabled()
    {
        $this->listener->setEnabled(false);
        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);
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

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(true);
        $this->priceListTriggerHandler->expects($this->exactly(2))
            ->method('handlePriceListTopic')
            ->withConsecutive(
                [ResolveCombinedPriceListCurrenciesTopic::getName(), $priceList],
                [ResolvePriceRulesTopic::getName(), $priceList]
            );

        $this->cache->expects($this->once())
            ->method('delete')
            ->withConsecutive(
                ['pr_4']
            );

        $this->listener->preRemove($priceListCurrency);
        $this->assertFalse($priceList->isActual());
    }

    public function testPreRemoveFeatureDisabled()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(PriceList::class, ['id' => 42]);
        /** @var PriceRule $priceRule */
        $priceRule = $this->getEntity(PriceRule::class, ['id' => 4]);
        $priceList->addPriceRule($priceRule);

        $priceListCurrency = new PriceListCurrency();
        $priceListCurrency->setPriceList($priceList);

        $this->featureChecker->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('oro_price_lists_combined')
            ->willReturn(false);
        $this->priceListTriggerHandler->expects($this->once())
            ->method('handlePriceListTopic')
            ->with(ResolvePriceRulesTopic::getName(), $priceList);

        $this->cache->expects($this->once())
            ->method('delete')
            ->withConsecutive(
                ['pr_4']
            );

        $this->listener->preRemove($priceListCurrency);
        $this->assertFalse($priceList->isActual());
    }
}
