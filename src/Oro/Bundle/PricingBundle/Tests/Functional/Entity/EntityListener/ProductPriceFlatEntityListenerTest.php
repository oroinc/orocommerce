<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;

/**
 * @dbIsolationPerTest
 */
class ProductPriceFlatEntityListenerTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConfigManagerAwareTestTrait;

    private const VERSION = 12345;

    private ?string $savedStorage = null;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductPrices::class]);

        $this->enableFlatPricingStorage();

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_flat');

        self::enableMessageBuffering();
        $this->clearMessageCollector();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restorePricingStorage();

        parent::tearDown();
    }

    public function testReindexRequestedOnPriceCreate(): void
    {
        $priceManager = $this->getPriceManager();
        $priceManager->persist($this->createProductPrice(LoadProductData::PRODUCT_5, Price::create(10, 'USD'), 10));
        $priceManager->flush();

        $this->assertProductReindexed(LoadProductData::PRODUCT_5);
    }

    public function testReindexRequestedOnPriceUpdate(): void
    {
        $priceManager = $this->getPriceManager();

        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference(LoadProductPrices::PRODUCT_PRICE_1);
        $productPrice->setPrice(Price::create(1000, 'USD'));

        $priceManager->persist($productPrice);
        $priceManager->flush();

        $this->assertProductReindexed(LoadProductData::PRODUCT_1);
    }

    public function testReindexRequestedOnPriceRemove(): void
    {
        $priceManager = $this->getPriceManager();
        $priceManager->remove($this->getReference(LoadProductPrices::PRODUCT_PRICE_1));
        $priceManager->flush();

        $this->assertProductReindexed(LoadProductData::PRODUCT_1);
    }

    public function testReindexNotRequestedOnVersionedPriceCreate(): void
    {
        $priceManager = $this->getPriceManager();

        $newPrice1 = $this->createProductPrice(LoadProductData::PRODUCT_5, Price::create(10, 'USD'), 10);
        $newPrice1->setVersion(self::VERSION);
        $newPrice2 = $this->createProductPrice(LoadProductData::PRODUCT_5, Price::create(20, 'USD'), 20);
        $newPrice2->setVersion(self::VERSION);

        $priceManager->persist($newPrice1);
        $priceManager->persist($newPrice2);
        $priceManager->flush();

        $this->assertNoReindexRequested();
    }

    public function testReindexNotRequestedOnVersionedPriceUpdate(): void
    {
        $priceManager = $this->getPriceManager();

        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference(LoadProductPrices::PRODUCT_PRICE_1);
        $productPrice->setPrice(Price::create(1000, 'USD'));
        $productPrice->setVersion(self::VERSION);

        $priceManager->persist($productPrice);
        $priceManager->flush();

        $this->assertNoReindexRequested();
    }

    /**
     * The second run of the same import bumps the version of an already versioned price.
     */
    public function testReindexNotRequestedOnVersionChangeOfAlreadyVersionedPrice(): void
    {
        $priceManager = $this->getPriceManager();

        $productPrice = $this->createVersionedProductPrice(LoadProductData::PRODUCT_5);

        $this->clearMessageCollector();

        $productPrice->setPrice(Price::create(20, 'USD'));
        $productPrice->setVersion(self::VERSION + 1);
        $priceManager->persist($productPrice);
        $priceManager->flush();

        $this->assertNoReindexRequested();
    }

    /**
     * A regular edit of an already versioned price does not change the version and must be processed as usual.
     */
    public function testReindexRequestedOnRegularUpdateOfVersionedPrice(): void
    {
        $priceManager = $this->getPriceManager();

        $productPrice = $this->createVersionedProductPrice(LoadProductData::PRODUCT_5);

        $this->clearMessageCollector();

        $productPrice->setPrice(Price::create(20, 'USD'));
        $priceManager->persist($productPrice);
        $priceManager->flush();

        $this->assertProductReindexed(LoadProductData::PRODUCT_5);
    }

    public function testReindexRequestedOnVersionedPriceRemove(): void
    {
        $priceManager = $this->getPriceManager();

        $productPrice = $this->createVersionedProductPrice(LoadProductData::PRODUCT_5);

        $this->clearMessageCollector();

        $priceManager->remove($productPrice);
        $priceManager->flush();

        $this->assertProductReindexed(LoadProductData::PRODUCT_5);
    }

    /**
     * With sharding enabled no version is set to the prices written by a mass operation,
     * so they still have to be reindexed one by one.
     */
    public function testReindexRequestedOnPriceCreateWithShardingEnabled(): void
    {
        $shardManager = $this->getShardManager();
        $shardManager->setEnableSharding(true);
        try {
            $priceList = new PriceList();
            $priceList->setName('Sharded Price List');
            $priceList->setCurrencies(['USD']);
            $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(PriceList::class);
            $entityManager->persist($priceList);
            $entityManager->flush();

            $this->clearMessageCollector();

            $priceManager = $this->getPriceManager();
            $productPrice = $this->createProductPrice(LoadProductData::PRODUCT_5, Price::create(10, 'USD'), 10);
            $productPrice->setPriceList($priceList);
            $priceManager->persist($productPrice);
            $priceManager->flush();
        } finally {
            $shardManager->setEnableSharding(false);
        }

        $this->assertProductReindexed(LoadProductData::PRODUCT_5);
    }

    private function assertProductReindexed(string $productReference): void
    {
        /** @var Product $product */
        $product = $this->getReference($productReference);

        $messages = self::getTopicSentMessages(WebsiteSearchReindexTopic::getName());
        self::assertNotEmpty($messages, 'No website search reindexation messages sent');

        $reindexedIds = [];
        foreach ($messages as $message) {
            self::assertTrue($message['message']['granulize'] ?? false);
            self::assertArrayNotHasKey('jobId', $message['message']);
            $reindexedIds[] = $message['message']['context']['entityIds'] ?? [];
        }

        self::assertContains($product->getId(), array_merge(...$reindexedIds));
    }

    private function assertNoReindexRequested(): void
    {
        self::assertCount(0, self::getTopicSentMessages(WebsiteSearchReindexTopic::getName()));
    }

    /**
     * Creates a price stamped with a version and reloads it, so that the entity is in the same state
     * as a price loaded from the database within a subsequent request.
     */
    private function createVersionedProductPrice(string $productReference): ProductPrice
    {
        $priceManager = $this->getPriceManager();

        $productPrice = $this->createProductPrice($productReference, Price::create(10, 'USD'), 10);
        $productPrice->setVersion(self::VERSION);
        $priceManager->persist($productPrice);
        $priceManager->flush();

        self::getContainer()->get('doctrine')
            ->getManagerForClass(ProductPrice::class)
            ->refresh($productPrice);
        self::assertSame(self::VERSION, $productPrice->getVersion());

        return $productPrice;
    }

    private function createProductPrice(string $productReference, Price $price, int $quantity): ProductPrice
    {
        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference(LoadProductUnits::LITER);
        /** @var Product $product */
        $product = $this->getReference($productReference);
        /** @var PriceList $priceList */
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);

        $productPrice = new ProductPrice();
        $productPrice
            ->setQuantity($quantity)
            ->setUnit($productUnit)
            ->setProduct($product)
            ->setPriceList($priceList)
            ->setPrice($price);

        return $productPrice;
    }

    private function enableFlatPricingStorage(): void
    {
        $configManager = $this->getGlobalConfigManager();
        $this->savedStorage = $configManager->get('oro_pricing.price_storage');
        $configManager->set('oro_pricing.price_storage', 'flat');
        $configManager->flush();
        self::getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
    }

    private function restorePricingStorage(): void
    {
        if (null === $this->savedStorage) {
            return;
        }

        $configManager = $this->getGlobalConfigManager();
        $configManager->set('oro_pricing.price_storage', $this->savedStorage);
        $configManager->flush();
        self::getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();
        $this->savedStorage = null;
    }

    private function getGlobalConfigManager(): ConfigManager
    {
        return self::getConfigManager('global');
    }

    private function getPriceManager(): PriceManager
    {
        return self::getContainer()->get('oro_pricing.manager.price_manager');
    }

    private function getShardManager(): ShardManager
    {
        return self::getContainer()->get('oro_pricing.shard_manager');
    }
}
