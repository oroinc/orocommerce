<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ComponentProcessor;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ProductBundle\ComponentProcessor\ComponentProcessorDataAccessor;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ComponentProcessorDataAccessorTest extends \PHPUnit\Framework\TestCase
{
    private ComponentProcessorDataAccessor $dataAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->dataAccessor = new ComponentProcessorDataAccessor();
    }

    public function testGetItem(): void
    {
        $collection = new ArrayCollection();
        $collection->add(new \ArrayObject());
        $collection->add(new \ArrayObject());

        self::assertSame($collection[0], $this->dataAccessor->getItem($collection, 0));
        self::assertSame($collection[1], $this->dataAccessor->getItem($collection, 1));
    }

    public function testGetItemSku(): void
    {
        $sku = 'sku1';
        $item = new \ArrayObject([ProductDataStorage::PRODUCT_SKU_KEY => $sku]);

        self::assertEquals($sku, $this->dataAccessor->getItemSku($item));
    }

    public function testGetItemSkuWhenItIsNull(): void
    {
        $item = new \ArrayObject([ProductDataStorage::PRODUCT_SKU_KEY => null]);

        self::assertNull($this->dataAccessor->getItemSku($item));
    }

    public function testGetOrganizationName(): void
    {
        $organizationName = 'Org1';
        $item = new \ArrayObject([ProductDataStorage::PRODUCT_ORGANIZATION_KEY => $organizationName]);

        self::assertEquals($organizationName, $this->dataAccessor->getItemOrganizationName($item));
    }

    public function testGetOrganizationNameWhenItIsNull(): void
    {
        $item = new \ArrayObject();

        self::assertNull($this->dataAccessor->getItemOrganizationName($item));
    }

    public function testGetProductSku(): void
    {
        $sku = 'sku1';
        $product = ['sku' => $sku];

        self::assertEquals($sku, $this->dataAccessor->getProductSku($product));
    }

    public function testGetProductSkuWhenItDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "sku" attribute does not exist.');

        self::assertNull($this->dataAccessor->getProductSku([]));
    }

    public function testGetProductOrganizationId(): void
    {
        $organizationId = 123;
        $product = ['orgId' => $organizationId];

        self::assertEquals($organizationId, $this->dataAccessor->getProductOrganizationId($product));
    }

    public function testGetProductOrganizationIdWhenItIsNull(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "orgId" attribute does not exist.');

        self::assertNull($this->dataAccessor->getProductOrganizationId([]));
    }

    public function testUpdateItem(): void
    {
        $item = new \ArrayObject();

        $productId = 1;
        $product = ['id' => $productId];

        $this->dataAccessor->updateItem($item, $product);

        self::assertEquals($productId, $item[ProductDataStorage::PRODUCT_ID_KEY]);
    }

    public function testUpdateItemWhenProductIdAlreadySet(): void
    {
        $itemProductId = 2;
        $item = new \ArrayObject([ProductDataStorage::PRODUCT_ID_KEY => $itemProductId]);

        $productId = 1;
        $product = ['id' => $productId];

        $this->dataAccessor->updateItem($item, $product);

        self::assertEquals($itemProductId, $item[ProductDataStorage::PRODUCT_ID_KEY]);
    }

    public function testUpdateItemThenProductIdDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The "id" attribute does not exist.');

        $this->dataAccessor->updateItem(new \ArrayObject(), []);
    }
}
