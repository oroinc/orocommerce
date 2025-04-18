<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductKitItemRepository;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ProductKitItemRepositoryTest extends WebTestCase
{
    private ProductKitItemRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadProductKitData::class]);

        $this->repository = self::getContainer()->get('doctrine')->getRepository(ProductKitItem::class);
    }

    public function testFindProductKitsSkuByUnitPrecisionWhenNewEntity(): void
    {
        self::assertEmpty($this->repository->findProductKitsSkuByUnitPrecision(new ProductUnitPrecision()));
    }

    public function testFindProductKitsSkuByUnitPrecisionWhenNoRelatedProductsKits(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_6);

        self::assertEmpty(
            $this->repository->findProductKitsSkuByUnitPrecision($product->getPrimaryUnitPrecision())
        );
    }

    public function testFindProductKitsSkuByUnitPrecisionWhenHasRelatedProductsKits(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        self::assertEqualsCanonicalizing(
            [LoadProductKitData::PRODUCT_KIT_1, LoadProductKitData::PRODUCT_KIT_2],
            $this->repository->findProductKitsSkuByUnitPrecision($product->getPrimaryUnitPrecision())
        );
    }

    public function testFindProductKitsSkuByUnitPrecisionWithLimitWhenHasRelatedProductsKits(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        self::assertEqualsCanonicalizing(
            [LoadProductKitData::PRODUCT_KIT_1, LoadProductKitData::PRODUCT_KIT_2],
            $this->repository->findProductKitsSkuByUnitPrecision($product->getPrimaryUnitPrecision(), 2)
        );
    }

    public function testFindProductKitsSkuByProductWhenNewEntity(): void
    {
        self::assertEmpty($this->repository->findProductKitsSkuByProduct(new Product()));
    }

    public function testFindProductKitsSkuByProductWhenNoRelatedProductsKits(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_6);

        self::assertEmpty(
            $this->repository->findProductKitsSkuByProduct($product)
        );
    }

    public function testFindProductKitsSkuByProductWhenHasRelatedProductsKits(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        self::assertEqualsCanonicalizing(
            [LoadProductKitData::PRODUCT_KIT_1, LoadProductKitData::PRODUCT_KIT_2, LoadProductKitData::PRODUCT_KIT_3],
            $this->repository->findProductKitsSkuByProduct($product)
        );
    }

    public function testFindProductKitsSkuByProductWithLimitWhenHasRelatedProductsKits(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        self::assertEqualsCanonicalizing(
            [LoadProductKitData::PRODUCT_KIT_2, LoadProductKitData::PRODUCT_KIT_3],
            $this->repository->findProductKitsSkuByProduct($product, 2)
        );
    }

    public function testGetKitItemsCountWhenNoProduct(): void
    {
        self::assertEquals(0, $this->repository->getKitItemsCount(self::BIGINT));
    }

    public function testGetKitItemsCountWhenNoKitItems(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        self::assertEquals(0, $this->repository->getKitItemsCount($product->getId()));
    }

    public function testGetKitItemsCount(): void
    {
        /** @var Product $productKit */
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);

        self::assertEquals(1, $this->repository->getKitItemsCount($productKit->getId()));
    }

    public function testGetRequiredProductKitItemStatuses(): void
    {
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_2);
        $data = $this->repository->getRequiredProductKitItemStatuses($productKit->getId());
        self::assertArrayHasKey($productKit->getId(), $data);
        self::assertCount(1, $data[$productKit->getId()]);
        self::assertEquals([
            'enabled',
            'enabled',
        ], $data[$productKit->getId()][0]['status']);
    }

    public function testGetRequiredProductKitItemInventoryStatuses(): void
    {
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_2);
        $data = $this->repository->getRequiredProductKitItemInventoryStatuses($productKit->getId());

        self::assertArrayHasKey($productKit->getId(), $data);
        self::assertCount(1, $data[$productKit->getId()]);
        self::assertEquals([
            'prod_inventory_status.in_stock',
            'prod_inventory_status.in_stock',
        ], $data[$productKit->getId()][0]['status']);
    }

    public function testGetProductKitItemByIdAndOrganization(): void
    {
        $productKit = $this->getReference(LoadProductKitData::PRODUCT_KIT_2);
        $kitItem = $productKit->getKitItems()->first();

        $result = $this->repository->getProductKitItemByIdAndOrganization(
            $kitItem->getId(),
            $productKit->getOrganization()->getId()
        );

        self::assertEquals($kitItem, $result);

        $organization = new Organization();
        ReflectionUtil::setId($organization, 100);
        $result = $this->repository->getProductKitItemByIdAndOrganization(
            $kitItem->getId(),
            $organization->getId()
        );

        self::assertNull($result);
    }
}
