<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

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

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $this->extension->buildForm($builder, ['data' => $this->entity]);

        $this->assertFalse($this->extension->isAddItemCalled());
    }

    public function testBuild()
    {
        $this->assertFalse($this->extension->isAddItemCalled());
        $this->entity->product = null;
        $this->entity->scalar = null;

        $sku = 'TEST';
        $product = $this->getProductEntity($sku);
        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => ['product' => 1, 'scalar' => 1],
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => 3,
                ],
            ],
        ];

        $this->assertMetadataCalled(['product' => ['targetClass' => 'OroB2B\Bundle\ProductBundle\Entity\Product']]);
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $this->extension->buildForm($builder, ['data' => $this->entity]);

        $this->assertTrue($this->extension->isAddItemCalled());

        $this->assertInstanceOf('OroB2B\Bundle\ProductBundle\Entity\Product', $this->entity->product);
        $this->assertEquals(1, $this->entity->product->getId());
        $this->assertEquals(1, $this->entity->scalar);
    }
}
