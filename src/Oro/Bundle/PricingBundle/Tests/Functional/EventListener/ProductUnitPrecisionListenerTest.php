<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\EventListener;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListForUnitTesting;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ProductUnitPrecisionListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadPriceListForUnitTesting::class
        ]);
        self::clearMessageCollector();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        self::clearMessageCollector();
    }

    /**
     * @dataProvider productDataProvider
     */
    public function testNewUnitPrecisionAddedDirectly(string $productReference, int $expectedCount)
    {
        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(ProductUnitPrecision::class);
        $product = $this->getReference($productReference);
        $unit = $this->getReference(LoadProductUnits::BOX);

        $productUnitPrecision = $this->getProductUnitPrecision($product, $unit);
        $manager->persist($productUnitPrecision);
        $manager->flush();

        self::assertMessagesCount(ResolvePriceRulesTopic::getName(), $expectedCount);
    }

    /**
     * @dataProvider productDataProvider
     */
    public function testNewUnitPrecisionAddedToProduct(string $productReference, int $expectedCount)
    {
        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(ProductUnitPrecision::class);
        /** @var Product $product */
        $product = $this->getReference($productReference);
        $unit = $this->getReference(LoadProductUnits::BOX);

        $productUnitPrecision = $this->getProductUnitPrecision($product, $unit);
        $product->addAdditionalUnitPrecision($productUnitPrecision);
        $manager->flush();

        self::assertMessagesCount(ResolvePriceRulesTopic::getName(), $expectedCount);
    }

    public static function productDataProvider(): array
    {
        return [
            'assigned to price list' => [LoadProductData::PRODUCT_1, 1],
            'not assigned to price list' => [LoadProductData::PRODUCT_2, 0]
        ];
    }

    public function testExistingUnitPrecisionChangedValuableField()
    {
        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(ProductUnitPrecision::class);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $unit = $this->getReference(LoadProductUnits::BOX);

        $productUnitPrecision = $this->getProductUnitPrecision($product, $unit);
        $product->addAdditionalUnitPrecision($productUnitPrecision);
        $manager->flush();

        // Clear collected messages and internal state of listener
        self::clearMessageCollector();
        self::assertMessagesEmpty(ResolvePriceRulesTopic::getName());
        $this->getContainer()->get('oro_pricing.listener.product_unit_precision')->onClear();

        $productUnitPrecision->setPrecision(3);
        $manager->flush();

        self::assertMessagesCount(ResolvePriceRulesTopic::getName(), 1);
    }

    public function testExistingUnitPrecisionChangedNotValuableField()
    {
        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(ProductUnitPrecision::class);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $unit = $this->getReference(LoadProductUnits::BOX);

        $productUnitPrecision = $this->getProductUnitPrecision($product, $unit);
        $productUnitPrecision->setSell(false);
        $product->addAdditionalUnitPrecision($productUnitPrecision);
        $manager->flush();

        // Clear collected messages and internal state of listener
        self::clearMessageCollector();
        self::assertMessagesEmpty(ResolvePriceRulesTopic::getName());
        $this->getContainer()->get('oro_pricing.listener.product_unit_precision')->onClear();

        $productUnitPrecision->setSell(true);
        $manager->flush();

        self::assertMessagesEmpty(ResolvePriceRulesTopic::getName());
    }

    private function getProductUnitPrecision(Product $product, ProductUnit $unit): ProductUnitPrecision
    {
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setProduct($product);
        $productUnitPrecision->setPrecision(1);
        $productUnitPrecision->setUnit($unit);
        $productUnitPrecision->setConversionRate(1);
        $productUnitPrecision->setSell(true);

        return $productUnitPrecision;
    }
}
