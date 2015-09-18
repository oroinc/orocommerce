<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Form\Extension\OrderDataStorageExtension;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractProductDataStorageExtensionTest;

class OrderDataStorageExtensionTest extends AbstractProductDataStorageExtensionTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->extension = new OrderDataStorageExtension($this->storage, $this->doctrineHelper, $this->productClass);
        $this->extension->setRequest($this->request);
        $this->extension->setDataClass('OroB2B\Bundle\OrderBundle\Entity\Order');

        $this->entity = new Order();
    }

    public function testBuild()
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ],
            ]
        ];
        $order = new Order();

        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $product = $this->getProductEntity($sku, $productUnit);

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $this->extension->buildForm($builder, ['data' => $order]);

        $this->assertCount(1, $order->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $order->getLineItems()->first();

        $this->assertEquals($product, $lineItem->getProduct());
        $this->assertEquals($product->getSku(), $lineItem->getProductSku());
        $this->assertEquals($productUnit, $lineItem->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $lineItem->getProductUnitCode());
        $this->assertEquals($qty, $lineItem->getQuantity());
    }

    public function testBuildWithoutUnit()
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ],
            ]
        ];
        $order = new Order();

        $product = $this->getProductEntity($sku);

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $this->extension->buildForm($builder, ['data' => $order]);

        $this->assertEmpty($order->getLineItems());
    }
}
