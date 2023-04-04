<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Extension\OrderDataStorageExtension;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractProductDataStorageExtensionTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class OrderDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    private Order $entity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entity = new Order();

        $this->extension = new OrderDataStorageExtension(
            $this->getRequestStack(),
            $this->storage,
            PropertyAccess::createPropertyAccessor(),
            $this->doctrineHelper,
            $this->aclHelper,
            $this->logger
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function getTargetEntity(): object
    {
        return $this->entity;
    }

    public function testBuildForm(): void
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ]
            ]
        ];

        $productUnit = $this->getProductUnit('item');
        $product = $this->getProduct($sku, $productUnit);

        $this->expectsEntityMetadata();
        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsGetProductFromEntityRepository($product);

        $this->extension->buildForm($this->getFormBuilder(), []);

        $this->assertCount(1, $this->entity->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->entity->getLineItems()->first();

        $this->assertEquals($product, $lineItem->getProduct());
        $this->assertEquals($product->getSku(), $lineItem->getProductSku());
        $this->assertEquals($productUnit, $lineItem->getProductUnit());
        $this->assertEquals($productUnit->getCode(), $lineItem->getProductUnitCode());
        $this->assertEquals($qty, $lineItem->getQuantity());
    }

    public function testBuildFormWithoutUnit(): void
    {
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ]
            ]
        ];
        $order = new Order();

        $product = $this->getProduct($sku);

        $this->expectsEntityMetadata();
        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsGetProductFromEntityRepository($product);

        $this->extension->buildForm($this->getFormBuilder(), []);

        $this->assertEmpty($order->getLineItems());
    }

    public function testBuildFormWithoutQuantity(): void
    {
        $sku = 'TEST';
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                ]
            ]
        ];

        $productUnit = $this->getProductUnit('item');
        $product = $this->getProduct($sku, $productUnit);

        $this->expectsEntityMetadata();
        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsGetProductFromEntityRepository($product);

        $this->extension->buildForm($this->getFormBuilder(), []);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->entity->getLineItems()->first();
        $this->assertEquals(1, $lineItem->getQuantity());
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([OrderType::class], OrderDataStorageExtension::getExtendedTypes());
    }
}
