<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerFactory;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class PriceAttributeProductPriceEntityListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadPriceAttributeProductPrices::class,
            LoadPriceRuleLexemes::class
        ]);
        $this->cleanScheduledMessages();
    }

    public function testPostPersist()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        /** @var PriceAttributePriceList $priceAttribute */
        $priceAttribute = $this->getReference('price_attribute_price_list_1');
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_4);

        $price = new PriceAttributeProductPrice();
        $price->setProduct($product)
            ->setPriceList($priceAttribute)
            ->setQuantity(1)
            ->setUnit($this->getReference('product_unit.box'))
            ->setPrice(Price::create(42, 'USD'));

        $em->persist($price);
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );
    }

    public function testPreUpdate()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var PriceAttributeProductPrice $price */
        $price = $this->getReference('price_attribute_product_price.1');
        $price->setPrice(Price::create(1000, 'USD'));

        $em->persist($price);
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );
    }

    public function testPreRemove()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var PriceAttributeProductPrice $price */
        $price = $this->getReference('price_attribute_product_price.1');
        $em->remove($price);
        $em->flush();

        $this->sendScheduledMessages();

        self::assertMessageSent(
            Topics::RESOLVE_PRICE_RULES,
            [
                PriceListTriggerFactory::PRICE_LIST => $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId(),
                PriceListTriggerFactory::PRODUCT => $product->getId()
            ]
        );
    }
}
