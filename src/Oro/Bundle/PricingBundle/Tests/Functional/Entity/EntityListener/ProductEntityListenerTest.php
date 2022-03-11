<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductEntityListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductData::class,
            LoadPriceRuleLexemes::class
        ]);
        $this->enableMessageBuffering();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);
    }

    private function addDefaultName(Product $product, string $name)
    {
        $defaultName = new ProductName();
        $defaultName->setString($name);

        $product->addName($defaultName);
    }

    public function testPreUpdate()
    {
        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getReference('price_list_1');
        /** @var Product $product */
        $product = $this->getReference('product-1');
        $this->assertNotEquals(Product::STATUS_DISABLED, $product->getStatus());
        $product->setStatus(Product::STATUS_DISABLED);

        $em = $this->getEntityManager();
        $em->persist($product);
        $em->flush();

        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $expectedPriceList->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );
    }

    public function testPostPersist()
    {
        $product = new Product();
        $product->setSku('TEST');
        $this->addDefaultName($product, LoadProductData::PRODUCT_1);

        $em = $this->getEntityManager();
        $em->persist($product);
        $em->flush();

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        self::assertMessageSent(
            ResolvePriceRulesTopic::getName(),
            [
                'product' => [
                    $priceList->getId() => [
                        $product->getId()
                    ]
                ]
            ]
        );
    }
}
