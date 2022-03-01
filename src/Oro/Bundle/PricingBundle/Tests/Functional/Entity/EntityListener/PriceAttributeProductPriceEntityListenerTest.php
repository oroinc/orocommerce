<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PriceAttributeProductPriceEntityListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadPriceAttributeProductPrices::class,
            LoadPriceRuleLexemes::class
        ]);
        $this->enableMessageBuffering();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);
    }

    public function testPostPersist()
    {
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

        $em = $this->getEntityManager();
        $em->persist($price);
        $em->flush();

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );
    }

    public function testPreUpdate()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var PriceAttributeProductPrice $price */
        $price = $this->getReference('price_attribute_product_price.1');
        $price->setPrice(Price::create(1000, 'USD'));

        $em = $this->getEntityManager();
        $em->persist($price);
        $em->flush();

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );
    }

    public function testPreRemove()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        /** @var PriceAttributeProductPrice $price */
        $price = $this->getReference('price_attribute_product_price.1');

        $em = $this->getEntityManager();
        $em->remove($price);
        $em->flush();

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $this->getReference(LoadPriceLists::PRICE_LIST_1)->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );
    }
}
