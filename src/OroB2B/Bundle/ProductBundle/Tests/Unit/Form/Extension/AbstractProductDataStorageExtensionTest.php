<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use OroB2B\Bundle\ProductBundle\Model\ProductRow;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;

class AbstractProductDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    public function testBuildEmptyData()
    {
        $this->assertFalse($this->extension->isAddItemCalled());

        $data = [ProductDataStorage::ENTITY_DATA_KEY => []];

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertFalse($this->extension->isAddItemCalled());
    }

    public function testBuild()
    {
        $this->assertFalse($this->extension->isAddItemCalled());
        $this->entity->product = null;
        $this->entity->scalar = null;

        $sku = 'TEST';
        $productRow = new ProductRow();
        $productRow->productSku = $sku;
        $productRow->productQuantity = 3;
        $product = $this->getProductEntity($sku);
        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => ['product' => 1, 'scalar' => 1],
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                $productRow,
            ],
        ];

        $this->assertMetadataCalled(['product' => ['targetClass' => 'OroB2B\Bundle\ProductBundle\Entity\Product']]);
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        $this->extension->buildForm($this->getBuilderMock(true), []);

        $this->assertTrue($this->extension->isAddItemCalled());

        $this->assertInstanceOf('OroB2B\Bundle\ProductBundle\Entity\Product', $this->entity->product);
        $this->assertEquals(1, $this->entity->product->getId());
        $this->assertEquals(1, $this->entity->scalar);
    }
}
