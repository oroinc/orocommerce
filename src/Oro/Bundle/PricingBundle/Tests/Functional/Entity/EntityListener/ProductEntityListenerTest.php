<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductEntityListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductData::class,
            LoadPriceRuleLexemes::class
        ]);
        $this->cleanScheduledMessages();
    }

    public function testPreUpdate()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getReference('price_list_1');
        /** @var Product $product */
        $product = $this->getReference('product.1');
        $this->assertNotEquals(Product::STATUS_DISABLED, $product->getStatus());
        $product->setStatus(Product::STATUS_DISABLED);
        $em->persist($product);
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $expectedPriceList->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );
    }

    public function testPostPersist()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        $product = new Product();
        $product->setSku('TEST');

        $em->persist($product);
        $em->flush();

        $this->sendScheduledMessages();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $priceList->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );
    }
}
