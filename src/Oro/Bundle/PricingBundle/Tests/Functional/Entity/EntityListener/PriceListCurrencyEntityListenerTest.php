<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
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
        $this->initClient();
        $this->loadFixtures([
            LoadPriceRules::class
        ]);
        $this->cleanScheduledMessages();
    }

    public function testPostPersist()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductPrice::class);

        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList->addCurrencyByCode('UAH');
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $priceList->getId(),
                PriceListTriggerFactory::PRODUCT => null
            ]
        );
    }

    public function testPreRemove()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductPrice::class);

        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $priceList->removeCurrencyByCode('USD');
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $priceList->getId(),
                PriceListTriggerFactory::PRODUCT => null
            ]
        );
    }
}
