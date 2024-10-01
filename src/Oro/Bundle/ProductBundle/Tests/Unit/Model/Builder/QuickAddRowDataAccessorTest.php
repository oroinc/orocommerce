<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model\Builder;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowDataAccessor;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuickAddRowDataAccessorTest extends \PHPUnit\Framework\TestCase
{
    private QuickAddRowDataAccessor $dataAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->dataAccessor = new QuickAddRowDataAccessor();
    }

    public function testGetItem(): void
    {
        $collection = new QuickAddRowCollection();
        $collection->add(new QuickAddRow(0, 'sku1', 1.0));
        $collection->add(new QuickAddRow(1, 'sku2', 1.0));

        self::assertSame($collection[0], $this->dataAccessor->getItem($collection, 0));
        self::assertSame($collection[1], $this->dataAccessor->getItem($collection, 1));
    }

    public function testGetItemSku(): void
    {
        $sku = 'sku1';
        $item = new QuickAddRow(0, 'sku1', 1.0);

        self::assertEquals($sku, $this->dataAccessor->getItemSku($item));
    }

    public function testGetOrganizationName(): void
    {
        $organizationName = 'Org1';
        $item = new QuickAddRow(0, 'sku1', 1.0, 'item', $organizationName);

        self::assertEquals($organizationName, $this->dataAccessor->getItemOrganizationName($item));
    }

    public function testGetOrganizationNameWhenItIsNull(): void
    {
        $item = new QuickAddRow(0, 'sku1', 1.0);

        self::assertNull($this->dataAccessor->getItemOrganizationName($item));
    }

    public function testGetProductSku(): void
    {
        $sku = 'sku1';
        $product = new Product();
        $product->setSku($sku);

        self::assertEquals($sku, $this->dataAccessor->getProductSku($product));
    }

    public function testGetProductSkuWhenItIsNull(): void
    {
        $product = new Product();

        self::assertNull($this->dataAccessor->getProductSku($product));
    }

    public function testGetProductOrganizationId(): void
    {
        $organizationId = 123;
        $organization = new Organization();
        $organization->setId($organizationId);
        $product = new Product();
        $product->setOrganization($organization);

        self::assertEquals($organizationId, $this->dataAccessor->getProductOrganizationId($product));
    }

    public function testGetProductOrganizationIdWhenItIsNull(): void
    {
        $product = new Product();

        self::assertNull($this->dataAccessor->getProductOrganizationId($product));
    }

    public function testUpdateItem(): void
    {
        $item = new QuickAddRow(0, 'sku1', 1.0);

        $unitCode = 'items';
        $unit = new ProductUnit();
        $unit->setCode($unitCode);
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);
        $product = new Product();
        $product->setPrimaryUnitPrecision($unitPrecision);

        $this->dataAccessor->updateItem($item, $product);

        self::assertSame($product, $item->getProduct());
        self::assertEquals($unitCode, $item->getUnit());
    }

    public function testUpdateItemWhenUnitAlreadySet(): void
    {
        $item = new QuickAddRow(0, 'sku1', 1.0, 'each');

        $unit = new ProductUnit();
        $unit->setCode('items');
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);
        $product = new Product();
        $product->setPrimaryUnitPrecision($unitPrecision);

        $this->dataAccessor->updateItem($item, $product);

        self::assertSame($product, $item->getProduct());
        self::assertEquals('each', $item->getUnit());
    }

    public function testUpdateItemWhenProductAlreadySet(): void
    {
        $itemProduct = new Product();
        $item = new QuickAddRow(0, 'sku1', 1.0);
        $item->setProduct($itemProduct);

        $unit = new ProductUnit();
        $unit->setCode('items');
        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision->setUnit($unit);
        $product = new Product();
        $product->setPrimaryUnitPrecision($unitPrecision);

        $this->dataAccessor->updateItem($item, $product);

        self::assertSame($itemProduct, $item->getProduct());
        self::assertNull($item->getUnit());
    }
}
