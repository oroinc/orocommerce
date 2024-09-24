<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Form\Extension\OrderDataStorageExtension;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractProductDataStorageExtensionTestCase;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Symfony\Component\PropertyAccess\PropertyAccess;

class OrderDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    private Order $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new Order();

        parent::setUp();

        $this->extension = new OrderDataStorageExtension(
            $this->getRequestStack(),
            $this->storage,
            PropertyAccess::createPropertyAccessor(),
            $this->doctrine,
            $this->logger
        );

        $this->initEntityMetadata([
            OrderProductKitItemLineItem::class => [
                'associationMappings' => [
                    'kitItem' => ['targetEntity' => ProductKitItemStub::class],
                    'product' => ['targetEntity' => ProductStub::class],
                    'productUnit' => ['targetEntity' => ProductUnit::class],
                ],
            ],
            ProductUnit::class => [
                'identifier' => ['code'],
            ],
        ]);
    }

    #[\Override]
    protected function getTargetEntity(): Order
    {
        return $this->entity;
    }

    public function testBuildForm(): void
    {
        $productId = 123;
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_ID_KEY => $productId,
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ]
            ]
        ];

        $productUnit = $this->getProductUnit('item');
        $product = $this->getProduct($sku, $productUnit);

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsFindProduct($productId, $product);

        $this->extension->buildForm($this->getFormBuilder(), []);

        self::assertCount(1, $this->entity->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->entity->getLineItems()->first();

        self::assertEquals($product, $lineItem->getProduct());
        self::assertEquals($product->getSku(), $lineItem->getProductSku());
        self::assertEquals($productUnit, $lineItem->getProductUnit());
        self::assertEquals($productUnit->getCode(), $lineItem->getProductUnitCode());
        self::assertEquals($qty, $lineItem->getQuantity());
    }

    public function testBuildFormWithoutUnit(): void
    {
        $productId = 123;
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_ID_KEY => $productId,
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                ]
            ]
        ];

        $product = $this->getProduct($sku);

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsFindProduct($productId, $product);

        $this->extension->buildForm($this->getFormBuilder(), []);

        self::assertEmpty($this->getTargetEntity()->getLineItems());
    }

    public function testBuildFormWithoutQuantity(): void
    {
        $productId = 123;
        $sku = 'TEST';
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_ID_KEY => $productId,
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                ]
            ]
        ];

        $productUnit = $this->getProductUnit('item');
        $product = $this->getProduct($sku, $productUnit);

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsFindProduct($productId, $product);

        $this->extension->buildForm($this->getFormBuilder(), []);

        /** @var OrderLineItem $lineItem */
        $lineItem = $this->entity->getLineItems()->first();
        self::assertEquals(1, $lineItem->getQuantity());
    }

    /**
     * @dataProvider getBuildFormWithProductKitDataProvider
     */
    public function testBuildFormWithProductKit(?string $productUnitCode): void
    {
        $productId = 123;
        $sku = 'TEST';
        $qty = 3;
        $kitItemLineItem1KitItemId = 1;
        $kitItemLineItem1ProductId = 1;
        $kitItemLineItem1Quantity = 2;
        $kitItemLineItemsData = [
            [
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_KIT_ITEM_KEY => $kitItemLineItem1KitItemId,
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_KEY => $kitItemLineItem1ProductId,
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_QUANTITY_KEY => $kitItemLineItem1Quantity,
                ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_UNIT_KEY => $productUnitCode,
            ],
        ];
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_ID_KEY => $productId,
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                    ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEMS_DATA_KEY => $kitItemLineItemsData,
                ]
            ]
        ];

        $productUnit = $this->getProductUnit('item');
        $product = $this->getProduct($sku, $productUnit);
        /** @var Product $product1 */
        $product1 = $this->getEntity(ProductStub::class, $kitItemLineItem1ProductId);
        $product1
            ->setSku('SKUPRODUCT1')
            ->setDefaultName('Product1 Name')
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($productUnit));

        /** @var ProductKitItem $kitItem */
        $kitItem = $this->getEntity(ProductKitItemStub::class, $kitItemLineItem1KitItemId);
        $kitItem
            ->setDefaultLabel('Base Unit')
            ->setMinimumQuantity(1)
            ->setMaximumQuantity(2)
            ->setOptional(false);

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsFindProduct($productId, $product);

        $this->extension->buildForm($this->getFormBuilder(), []);

        self::assertCount(1, $this->entity->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->entity->getLineItems()->first();

        self::assertEquals($product, $lineItem->getProduct());
        self::assertEquals($product->getSku(), $lineItem->getProductSku());
        self::assertEquals($productUnit, $lineItem->getProductUnit());
        self::assertEquals($productUnit->getCode(), $lineItem->getProductUnitCode());
        self::assertEquals($qty, $lineItem->getQuantity());

        self::assertCount(1, $lineItem->getKitItemLineItems());
        /** @var OrderProductKitItemLineItem $orderProductKitItemLineItem */
        $orderProductKitItemLineItem = $lineItem->getKitItemLineItems()->first();

        self::assertEquals($product1, $orderProductKitItemLineItem->getProduct());
        self::assertEquals($product1->getSku(), $orderProductKitItemLineItem->getProductSku());
        self::assertEquals($product1->getDenormalizedDefaultName(), $orderProductKitItemLineItem->getProductName());
        self::assertEquals($kitItem, $orderProductKitItemLineItem->getKitItem());
        self::assertEquals($kitItem->getDefaultLabel(), $orderProductKitItemLineItem->getKitItemLabel());
        self::assertEquals($kitItem->isOptional(), $orderProductKitItemLineItem->isOptional());
        self::assertEquals($kitItem->getMinimumQuantity(), $orderProductKitItemLineItem->getMinimumQuantity());
        self::assertEquals($kitItem->getMaximumQuantity(), $orderProductKitItemLineItem->getMaximumQuantity());
        self::assertEquals($productUnit, $orderProductKitItemLineItem->getProductUnit());
        self::assertEquals($productUnit->getCode(), $orderProductKitItemLineItem->getProductUnitCode());
        self::assertEquals(
            $productUnit->getDefaultPrecision(),
            $orderProductKitItemLineItem->getProductUnitPrecision()
        );
        self::assertEquals($kitItemLineItem1Quantity, $orderProductKitItemLineItem->getQuantity());
    }

    public function getBuildFormWithProductKitDataProvider(): array
    {
        return [
            'product unit code' => [
                'productUnitCode' => 'item',
            ],
            'empty product unit code' => [
                'productUnitCode' => null,
            ],
        ];
    }

    /**
     * @dataProvider getSkippedKitItemLineItemDataProvider
     */
    public function testBuildFormWithProductKitSkippedKitItemLineItem(array $kitItemLineItemsData): void
    {
        $productId = 123;
        $sku = 'TEST';
        $qty = 3;
        $data = [
            ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                [
                    ProductDataStorage::PRODUCT_ID_KEY => $productId,
                    ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                    ProductDataStorage::PRODUCT_QUANTITY_KEY => $qty,
                    ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEMS_DATA_KEY => $kitItemLineItemsData,
                ]
            ]
        ];

        $productUnit = $this->getProductUnit('item');
        $product = $this->getProduct($sku, $productUnit);

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsFindProduct($productId, $product);

        $this->extension->buildForm($this->getFormBuilder(), []);

        self::assertCount(1, $this->entity->getLineItems());
        /** @var OrderLineItem $lineItem */
        $lineItem = $this->entity->getLineItems()->first();

        self::assertEquals($product, $lineItem->getProduct());
        self::assertEquals($product->getSku(), $lineItem->getProductSku());
        self::assertEquals($productUnit, $lineItem->getProductUnit());
        self::assertEquals($productUnit->getCode(), $lineItem->getProductUnitCode());
        self::assertEquals($qty, $lineItem->getQuantity());
        self::assertEmpty($lineItem->getKitItemLineItems());
    }

    public function getSkippedKitItemLineItemDataProvider(): array
    {
        return [
            'no kitItem' => [
                'kitItemLineItemsData' => [
                    [
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_KIT_ITEM_KEY => null,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_KEY => 2,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_QUANTITY_KEY => 2,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_UNIT_KEY => 'item',
                    ],
                ],
            ],
            'no product' => [
                'kitItemLineItemsData' => [
                    [
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_KIT_ITEM_KEY => 2,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_KEY => null,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_QUANTITY_KEY => 2,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_UNIT_KEY => 'item',
                    ],
                ],
            ],
            'no kitItem and product' => [
                'kitItemLineItemsData' => [
                    [
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_KIT_ITEM_KEY => null,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_KEY => null,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_QUANTITY_KEY => 2,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_UNIT_KEY => 'item',
                    ],
                ],
            ],
            'no product unit' => [
                'kitItemLineItemsData' => [
                    [
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_KIT_ITEM_KEY => 2,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_KEY => 2,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_QUANTITY_KEY => 2,
                        ProductDataStorage::PRODUCT_KIT_ITEM_LINE_ITEM_PRODUCT_UNIT_KEY => null,
                    ],
                ],
            ],
        ];
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([OrderType::class], OrderDataStorageExtension::getExtendedTypes());
    }
}
