<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PricingBundle\Async\Topic\ResolveCombinedPriceByPriceListTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Manager\PriceManager;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedPriceListWithCustomerRelation;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceLists;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ProductPriceCPLEntityListenerTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConfigManagerAwareTestTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductPrices::class, LoadCombinedPriceListWithCustomerRelation::class]);

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_cpl');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_to_product');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_prices');

        $this->enableMessageBuffering();
    }

    public function testOnCreate()
    {
        $priceManager = $this->getPriceManager();

        // create two prices with same product and priceList
        // to ensure that duplicate triggers won't be flushed
        $priceManager->persist(
            $this->getProductPrice(
                LoadProductData::PRODUCT_5,
                LoadPriceLists::PRICE_LIST_1,
                Price::create(10, 'USD'),
                10
            )
        );
        $priceManager->persist(
            $this->getProductPrice(
                LoadProductData::PRODUCT_5,
                LoadPriceLists::PRICE_LIST_1,
                Price::create(100, 'USD'),
                100
            )
        );
        $priceManager->flush();

        // assert that needed productPriceRelationCreated
        $plToProductRelations = $this->getPriceListToProductRepository()->findBy([
            'product'   => $this->getReference(LoadProductData::PRODUCT_5),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
        ]);
        $this->assertCount(1, $plToProductRelations);
    }

    public function testOnCreateWithDisabledListener()
    {
        $this->disableListener();

        $priceManager = $this->getPriceManager();
        $priceManager->persist(
            $this->getProductPrice(
                LoadProductData::PRODUCT_5,
                LoadPriceLists::PRICE_LIST_1,
                Price::create(10, 'USD'),
                10
            )
        );
        $priceManager->flush();

        $this->assertMessagesEmpty(ResolveCombinedPriceByPriceListTopic::getName());
    }

    public function testOnUpdateChangeTriggerCreated()
    {
        $priceList = $this->getReference(LoadCombinedPriceListWithCustomerRelation::DEFAULT_PRICE_LIST);
        $product = $this->getReference(LoadProductData::PRODUCT_2);
        $productPrices = $this->getProductPriceRepository()->findByPriceList(
            $this->getShardManager(),
            $priceList,
            [
                'priceList' => $priceList,
                'product'   => $product
            ]
        );
        $productPrice = $productPrices[0];
        $productPrice->setPrice(Price::create(1000, 'EUR'));

        $priceManager = $this->getPriceManager();
        $priceManager->persist($productPrice);
        $priceManager->flush();
        static::assertMessageSent(ResolveCombinedPriceByPriceListTopic::getName());
    }

    public function testOnUpdateFeatureDisabled()
    {
        $configManager = self::getConfigManager('global');
        $savedStorage = $configManager->get('oro_pricing.price_storage');
        $configManager->set('oro_pricing.price_storage', 'flat');
        $this->getContainer()->get('oro_featuretoggle.checker.feature_checker')->resetCache();

        $priceList = $this->getReference('price_list_2');
        $productPrices = $this->getProductPriceRepository()->findByPriceList(
            $this->getShardManager(),
            $priceList,
            [
                'priceList' => $priceList,
                'product'   => $this->getReference(LoadProductData::PRODUCT_2)
            ]
        );
        $productPrice = $productPrices[0];
        $productPrice->setPrice(Price::create(1000, 'EUR'));

        $priceManager = $this->getPriceManager();
        $priceManager->persist($productPrice);
        $priceManager->flush();

        $configManager->set('oro_pricing.price_storage', $savedStorage);

        self::assertEmpty(self::getSentMessages());
    }

    public function testOnUpdateChangeTriggerCreatedWithDisabledListener()
    {
        $this->disableListener();

        $priceList = $this->getReference('price_list_2');
        $productPrices = $this->getProductPriceRepository()->findByPriceList(
            $this->getShardManager(),
            $priceList,
            [
                'priceList' => $priceList,
                'product'   => $this->getReference(LoadProductData::PRODUCT_2)
            ]
        );
        $productPrice = $productPrices[0];
        $productPrice->setPrice(Price::create(1000, 'EUR'));

        $priceManager = $this->getPriceManager();
        $priceManager->persist($productPrice);
        $priceManager->flush();

        self::assertEmpty(self::getSentMessages());
    }

    public function testOnUpdatePriceToProductRelation()
    {
        $this->getPriceListToProductRepository()
            ->createQueryBuilder('pltp')
            ->delete()
            ->getQuery()
            ->execute();

        $productPrices = $this->getProductPriceRepository()->findByPriceList(
            $this->getShardManager(),
            $this->getReference(LoadPriceLists::PRICE_LIST_1),
            [
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
                'product'   => $this->getReference(LoadProductData::PRODUCT_1)
            ]
        );
        $productPrice1 = $productPrices[0];

        $productPrices = $this->getProductPriceRepository()->findByPriceList(
            $this->getShardManager(),
            $this->getReference(LoadPriceLists::PRICE_LIST_2),
            [
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_2),
                'product'   => $this->getReference(LoadProductData::PRODUCT_2)
            ]
        );
        $productPrice2 = $productPrices[0];

        $productPrices = $this->getProductPriceRepository()->findByPriceList(
            $this->getShardManager(),
            $this->getReference(LoadPriceLists::PRICE_LIST_2),
            [
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_2),
                'product'   => $this->getReference(LoadProductData::PRODUCT_1)
            ]
        );
        $productPrice3 = $productPrices[0];

        /** @var Product $newProduct */
        $newProduct = $this->getReference(LoadProductData::PRODUCT_2);
        $productPrice1->setProduct($newProduct);

        /** @var PriceList $newPriceList */
        $newPriceList = $this->getReference(LoadPriceLists::PRICE_LIST_5);
        $productPrice2->setPriceList($newPriceList);

        $productPrice3->setQuantity(10000);

        $priceManager = $this->getPriceManager();
        $priceManager->persist($productPrice1);
        $priceManager->persist($productPrice2);
        $priceManager->persist($productPrice3);
        $priceManager->flush();

        // new relation should be created when new product specified
        $this->assertCount(1, $this->getPriceListToProductRepository()->findBy([
            'product'   => $this->getReference(LoadProductData::PRODUCT_2),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
        ]));

        // new relation should be created when new price list specified
        $this->assertCount(1, $this->getPriceListToProductRepository()->findBy([
            'product'   => $this->getReference(LoadProductData::PRODUCT_2),
            'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_5),
        ]));

        $this->assertCount(3, $this->getPriceListToProductRepository()->findBy([]));
    }

    public function testOnUpdatePriceToProductRelationWithDisabledListener()
    {
        $this->getPriceListToProductRepository()
            ->createQueryBuilder('pltp')
            ->delete()
            ->getQuery()
            ->execute();

        $productPrices = $this->getProductPriceRepository()->findByPriceList(
            $this->getShardManager(),
            $this->getReference(LoadPriceLists::PRICE_LIST_1),
            [
                'priceList' => $this->getReference(LoadPriceLists::PRICE_LIST_1),
                'product'   => $this->getReference(LoadProductData::PRODUCT_1)
            ]
        );
        $productPrice1 = $productPrices[0];

        /** @var Product $newProduct */
        $newProduct = $this->getReference(LoadProductData::PRODUCT_2);
        $productPrice1->setProduct($newProduct);

        $this->clearMessageCollector();
        $this->disableListener();

        $priceManager = $this->getPriceManager();
        $priceManager->persist($productPrice1);
        $priceManager->flush();

        $this->assertMessagesEmpty(ResolveCombinedPriceByPriceListTopic::getName());
    }

    public function testOnDelete()
    {
        $priceList = $this->getReference(LoadPriceLists::PRICE_LIST_1);
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $this->assertPriceListToProductCount($priceList, $product, 1);

        $priceManager = $this->getPriceManager();
        $priceManager->remove($this->getReference(LoadProductPrices::PRODUCT_PRICE_1));

        $this->assertPriceListToProductCount($priceList, $product, 1);
        $this->clearMessageCollector();
        $priceManager->flush();
        $this->assertPriceListToProductCount($priceList, $product, 1);

        $priceManager->remove($this->getReference(LoadProductPrices::PRODUCT_PRICE_2));
        $priceManager->remove($this->getReference(LoadProductPrices::PRODUCT_PRICE_7));
        $priceManager->remove($this->getReference(LoadProductPrices::PRODUCT_PRICE_10));

        $this->assertPriceListToProductCount($priceList, $product, 1);
        $this->clearMessageCollector();
        $priceManager->flush();
        $this->assertPriceListToProductCount($priceList, $product, 0);
    }

    private function assertPriceListToProductCount(PriceList $priceList, Product $product, int $count)
    {
        $this->assertCount(
            $count,
            $this->getPriceListToProductRepository()->findBy(['priceList' => $priceList, 'product' => $product])
        );
    }

    public function testOnDeleteWithDisabledListener()
    {
        $priceManager = $this->getPriceManager();
        $priceManager->remove($this->getReference(LoadProductPrices::PRODUCT_PRICE_1));

        $this->clearMessageCollector();
        $this->disableListener();

        $priceManager->flush();

        $this->assertMessagesEmpty(ResolveCombinedPriceByPriceListTopic::getName());
    }

    /**
     * @param string  $productReference
     * @param string  $priceListReference
     * @param Price   $price
     * @param integer $quantity
     *
     * @return ProductPrice
     */
    private function getProductPrice($productReference, $priceListReference, Price $price, $quantity)
    {
        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference('product_unit.liter');
        /** @var Product $product */
        $product = $this->getReference($productReference);
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListReference);

        $productPrice = new ProductPrice();
        $productPrice
            ->setQuantity($quantity)
            ->setUnit($productUnit)
            ->setProduct($product)
            ->setPriceList($priceList)
            ->setPrice($price);

        return $productPrice;
    }

    private function disableListener()
    {
        $this->getContainer()->get('oro_pricing.entity_listener.product_price_cpl')->setEnabled(false);
    }

    private function getPriceManager(): PriceManager
    {
        return $this->getContainer()->get('oro_pricing.manager.price_manager');
    }

    private function getShardManager(): ShardManager
    {
        return $this->getContainer()->get('oro_pricing.shard_manager');
    }

    private function getProductPriceRepository(): ProductPriceRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(ProductPrice::class);
    }

    private function getPriceListToProductRepository(): PriceListToProductRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository(PriceListToProduct::class);
    }
}
