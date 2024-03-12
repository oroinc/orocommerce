<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Functional\ProductKit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 *
 * @covers \Oro\Bundle\ProductBundle\ProductKit\EventListener\StatusListener
 * @covers \Oro\Bundle\ProductBundle\ProductKit\Resolver\ProductKitInventoryStatusResolver
 */
class ProductInventoryStatusListenerTest extends WebTestCase
{
    private ManagerRegistry $registry;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures(
            [
                LoadProductKitData::class,
            ]
        );
        $this->registry = self::getContainer()->get(ManagerRegistry::class);
        $this->registry->getManager()->clear();
    }

    public function testStatusesCorrectlySetAfterFixtureLoaded(): void
    {
        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $kit2 = $this->getReference(LoadProductKitData::PRODUCT_KIT_2);
        $kit3 = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);

        self::assertEquals(
            [
                Product::INVENTORY_STATUS_IN_STOCK,
                Product::INVENTORY_STATUS_IN_STOCK,
                Product::INVENTORY_STATUS_OUT_OF_STOCK,
            ],
            [
                $kit1->getInventoryStatus()->getId(),
                $kit2->getInventoryStatus()->getId(),
                $kit3->getInventoryStatus()->getId(),
            ]
        );
    }

    public function testStatusChangedIfRelatedProductChangedToOutOfStock(): void
    {
        $product = $this->getReference('product-1');
        $product->setInventoryStatus($this->getInventoryStatus(Product::INVENTORY_STATUS_OUT_OF_STOCK));
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);

        self::assertEquals(Product::INVENTORY_STATUS_OUT_OF_STOCK, $kit1->getInventoryStatus()->getId());
    }

    public function testStatusChangedIfRelatedProductChangedToDiscontinued(): void
    {
        $product = $this->getReference('product-1');
        $product->setInventoryStatus($this->getInventoryStatus(Product::INVENTORY_STATUS_DISCONTINUED));
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);

        self::assertEquals(Product::INVENTORY_STATUS_OUT_OF_STOCK, $kit1->getInventoryStatus()->getId());
    }

    public function testStatusChangedIfRelatedOutOfStockAndDiscontinuedProductsRemoved(): void
    {
        $product = $this->getReference('product-3');
        $product->setInventoryStatus($this->getInventoryStatus(Product::INVENTORY_STATUS_DISCONTINUED));
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        $kit1 = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);

        self::assertEquals(Product::INVENTORY_STATUS_OUT_OF_STOCK, $kit1->getInventoryStatus()->getId());

        $product = $this->getReference('product-4');
        $this->registry->getManager()->remove($product);
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        $kit3 = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);

        self::assertEquals(Product::INVENTORY_STATUS_OUT_OF_STOCK, $kit3->getInventoryStatus()->getId());
    }

    public function testStatusChangedIfProductKitItemWithOutOfStockProductIsRemoved(): void
    {
        /** @var Product $productKit */
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);
        $productKitItems = $productKit->getKitItems()->filter(
            static fn (ProductKitItem $kitItem) => $kitItem->getProducts()->filter(
                static fn (Product $product) => in_array($product->getSku(), [
                    LoadProductData::PRODUCT_3
                ], true)
            )->count()
        );
        foreach ($productKitItems as $productKitItem) {
            $this->registry->getManager()->remove($productKitItem);
        }
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        $kit3 = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);

        self::assertEquals(Product::INVENTORY_STATUS_IN_STOCK, $kit3->getInventoryStatus()->getId());
    }

    public function testStatusChangedIfProductKitItemWithOutOfStockProductTurnedIntoOptional(): void
    {
        /** @var Product $productKit */
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);
        $productKitItems = $productKit->getKitItems()->filter(
            static fn (ProductKitItem $kitItem) => $kitItem->getProducts()->filter(
                static fn (Product $product) => in_array($product->getSku(), [
                    LoadProductData::PRODUCT_3,
                ], true)
            )->count()
        );
        foreach ($productKitItems as $productKitItem) {
            $productKitItem->setOptional(true);
        }

        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        $kit3 = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);

        self::assertEquals(Product::INVENTORY_STATUS_IN_STOCK, $kit3->getInventoryStatus()->getId());
    }

    public function testStatusChangedIfProductKitItemProductWithOutOfStockProductWasRemoved(): void
    {
        /** @var Product $productKit */
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);
        $productKitItems = $productKit->getKitItems()->filter(
            static fn (ProductKitItem $kitItem) => $kitItem->getProducts()->filter(
                static fn (Product $product) => in_array($product->getSku(), [
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_3,
                ], true)
            )->count()
        );
        /** @var ProductKitItem $productKitItem */
        foreach ($productKitItems as $productKitItem) {
            $productKitItemProduct = $productKitItem->getKitItemProducts()->filter(
                fn (ProductKitItemProduct $product) => in_array($product->getProduct()->getSku(), [
                    LoadProductData::PRODUCT_4,
                    LoadProductData::PRODUCT_3,
                ], true)
            );

            $productKitItemProduct = $productKitItemProduct->first();
            $this->registry->getManager()->remove($productKitItemProduct);
        }
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        $kit3 = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);

        self::assertEquals(Product::INVENTORY_STATUS_IN_STOCK, $kit3->getInventoryStatus()->getId());
    }

    public function testStatusChangedIfProductKitItemProductWithInStockProductWasChanged(): void
    {
        /** @var Product $productKit */
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);
        $productKitItem = $productKit->getKitItems()->filter(
            static fn (ProductKitItem $kitItem) => $kitItem->getProducts()->filter(
                static fn (Product $product) => $product->getSku() === LoadProductData::PRODUCT_5
            )->count()
        );
        /** @var ProductKitItem $productKitItem */
        $productKitItem = $productKitItem->first();
        $productKitItemProduct = $productKitItem->getKitItemProducts()->filter(
            fn (ProductKitItemProduct $product) => $product->getProduct()->getSku() === LoadProductData::PRODUCT_5
        );
        $productKitItemProduct = $productKitItemProduct->first();
        $productKitItemProduct->setProduct($this->getReference('product-3'));
        $this->registry->getManager()->flush();
        $this->registry->getManager()->clear();

        $kit3 = $this->getReference(LoadProductKitData::PRODUCT_KIT_3);

        self::assertEquals(Product::INVENTORY_STATUS_OUT_OF_STOCK, $kit3->getInventoryStatus()->getId());
    }

    private function getInventoryStatus(string $inventoryStatusId): AbstractEnumValue
    {
        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');

        return $this->registry->getRepository($inventoryStatusClassName)->findOneBy([
            'id' => $inventoryStatusId,
        ]);
    }
}
