<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRules;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class PriceListCurrencyEntityListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadPriceRules::class
        ]);
        $this->cleanQueueMessageTraces();
    }

    public function testPostPersist()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductPrice::class);

        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList->addCurrencyByCode('UAH');
        $em->flush();

        $traces = $this->getQueueMessageTraces(Topics::RESOLVE_PRICE_RULES);
        $this->assertCount(1, $traces);

        $this->assertEquals($priceList->getId(), $this->getPriceListIdFromTrace($traces[0]));
    }

    public function testPreRemove()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductPrice::class);

        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList->removeCurrencyByCode('USD');
        $em->flush();

        $traces = $this->getQueueMessageTraces(Topics::RESOLVE_PRICE_RULES);
        $this->assertCount(1, $traces);

        $this->assertEquals($priceList->getId(), $this->getPriceListIdFromTrace($traces[0]));
    }
}
