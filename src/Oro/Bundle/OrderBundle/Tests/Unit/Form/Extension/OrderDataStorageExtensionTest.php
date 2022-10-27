<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Extension\OrderDataStorageExtension;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractProductDataStorageExtensionTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class OrderDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $requestStack = $this->createMock(RequestStack::class);
        $this->request = $this->createMock(Request::class);

        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);
        $this->entity = new Order();
        $this->extension = new OrderDataStorageExtension(
            $requestStack,
            $this->storage,
            $this->doctrineHelper,
            $this->aclHelper,
            $this->productClass
        );
        $this->extension->setDataClass(Order::class);
        $this->setUpLoggerMock($this->extension);
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
        $this->entity = new Order();

        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $product = $this->getProductEntity($sku, $productUnit);

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        $this->extension->buildForm($this->getFormBuilder(true), []);

        $this->assertCount(1, $this->entity->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->entity->getLineItems()->first();

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

        $this->extension->buildForm($this->getFormBuilder(true), []);

        $this->assertEmpty($order->getLineItems());
    }

    public function testBuildWithoutQuantity()
    {
        $sku = 'TEST';
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                ],
            ]
        ];
        $this->entity = new Order();

        $productUnit = new ProductUnit();
        $productUnit->setCode('item');

        $product = $this->getProductEntity($sku, $productUnit);

        $this->assertMetadataCalled();
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertProductRepositoryCalled($product);

        $this->extension->buildForm($this->getFormBuilder(true), []);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->entity->getLineItems()->first();
        $this->assertEquals(1, $lineItem->getQuantity());
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([OrderType::class], OrderDataStorageExtension::getExtendedTypes());
    }
}
