<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension\AbstractProductDataStorageExtensionTestCase;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Entity\RequestProductKitItemLineItem;
use Oro\Bundle\RFPBundle\Form\Extension\RequestDataStorageExtension;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestType;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class RequestDataStorageExtensionTest extends AbstractProductDataStorageExtensionTestCase
{
    private ProductRFPAvailabilityProvider&MockObject $productAvailabilityProvider;
    private Environment&MockObject $twig;
    private FlashBagInterface&MockObject $flashBag;
    private RFPRequest $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->productAvailabilityProvider = $this->createMock(ProductRFPAvailabilityProvider::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->entity = new RFPRequest();

        parent::setUp();

        $this->initEntityMetadata([
            RequestProductKitItemLineItem::class => [
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
    protected function getExtension(): RequestDataStorageExtension
    {
        $session = $this->createMock(Session::class);
        $session->expects(self::any())
            ->method('getFlashBag')
            ->willReturn($this->flashBag);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);
        $requestStack->expects(self::any())
            ->method('getSession')
            ->willReturn($session);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn ($key) => $key . '_translated');

        return new RequestDataStorageExtension(
            $requestStack,
            $this->storage,
            PropertyAccess::createPropertyAccessor(),
            $this->doctrine,
            $this->logger,
            $this->productAvailabilityProvider,
            $translator,
            $this->twig
        );
    }

    #[\Override]
    protected function getTargetEntity(): RFPRequest
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
                ],
            ],
        ];

        $productUnit = $this->getProductUnit('item');
        $product = $this->getProduct($sku, $productUnit);

        $this->productAvailabilityProvider->expects(self::once())
            ->method('isProductAllowedForRFP')
            ->with($product)
            ->willReturn(true);

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsFindProduct($productId, $product);

        $this->getExtension()->buildForm($this->getFormBuilder(), []);

        self::assertCount(1, $this->entity->getRequestProducts());
        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->entity->getRequestProducts()->first();

        self::assertEquals($product, $requestProduct->getProduct());
        self::assertEquals($product->getSku(), $requestProduct->getProductSku());

        self::assertCount(1, $requestProduct->getRequestProductItems());
        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $requestProduct->getRequestProductItems()->first();

        self::assertEquals($productUnit, $requestProductItem->getProductUnit());
        self::assertEquals($productUnit->getCode(), $requestProductItem->getProductUnitCode());
        self::assertEquals($qty, $requestProductItem->getQuantity());
    }

    public function testBuildFormNotAllowedForRFPProduct(): void
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
                ],
            ],
        ];

        $productUnit = $this->getProductUnit('item');
        $product = $this->getProduct($sku, $productUnit);

        $this->productAvailabilityProvider->expects(self::once())
            ->method('isProductAllowedForRFP')
            ->with($product)
            ->willReturn(false);

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsWarningFlashMessage([$product]);
        $this->expectsFindProduct($productId, $product);

        $this->getExtension()->buildForm($this->getFormBuilder(), []);

        self::assertEmpty($this->entity->getRequestProducts());
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
                ],
            ],
        ];

        $product = $this->getProduct($sku);

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);
        $this->expectsFindProduct($productId, $product);

        $this->getExtension()->buildForm($this->getFormBuilder(), []);

        self::assertEmpty($this->entity->getRequestProducts());
    }

    private function expectsWarningFlashMessage(array $canNotBeAddedToRFQProducts): void
    {
        $warningRenderedMessage = 'warning message';
        $this->twig->expects(self::once())
            ->method('render')
            ->with(
                '@OroRFP/Form/FlashBag/warning.html.twig',
                [
                    'message' => 'oro.frontend.rfp.data_storage.cannot_be_added_to_rfq_translated',
                    'products' => $canNotBeAddedToRFQProducts,
                ]
            )
            ->willReturn($warningRenderedMessage);

        $this->flashBag->expects(self::once())
            ->method('add')
            ->with('warning', $warningRenderedMessage);
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([RequestType::class], RequestDataStorageExtension::getExtendedTypes());
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
        /** @var ProductStub $product1 */
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

        $this->productAvailabilityProvider->expects(self::once())
            ->method('isProductAllowedForRFP')
            ->with($product)
            ->willReturn(true);

        $this->getExtension()->buildForm($this->getFormBuilder(), []);

        self::assertCount(1, $this->entity->getRequestProducts());
        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->entity->getRequestProducts()->first();

        self::assertEquals($product, $requestProduct->getProduct());
        self::assertEquals($product->getSku(), $requestProduct->getProductSku());

        self::assertCount(1, $requestProduct->getKitItemLineItems());
        /** @var RequestProductKitItemLineItem $requestProductKitItemLineItem */
        $requestProductKitItemLineItem = $requestProduct->getKitItemLineItems()->first();

        self::assertEquals($product1, $requestProductKitItemLineItem->getProduct());
        self::assertEquals($product1->getSku(), $requestProductKitItemLineItem->getProductSku());
        self::assertEquals($product1->getDenormalizedDefaultName(), $requestProductKitItemLineItem->getProductName());
        self::assertEquals($kitItem, $requestProductKitItemLineItem->getKitItem());
        self::assertEquals($kitItem->getDefaultLabel(), $requestProductKitItemLineItem->getKitItemLabel());
        self::assertEquals($kitItem->isOptional(), $requestProductKitItemLineItem->isOptional());
        self::assertEquals($kitItem->getMinimumQuantity(), $requestProductKitItemLineItem->getMinimumQuantity());
        self::assertEquals($kitItem->getMaximumQuantity(), $requestProductKitItemLineItem->getMaximumQuantity());
        self::assertEquals($productUnit, $requestProductKitItemLineItem->getProductUnit());
        self::assertEquals($productUnit->getCode(), $requestProductKitItemLineItem->getProductUnitCode());
        self::assertEquals(
            $productUnit->getDefaultPrecision(),
            $requestProductKitItemLineItem->getProductUnitPrecision()
        );
        self::assertEquals($kitItemLineItem1Quantity, $requestProductKitItemLineItem->getQuantity());
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

        $this->productAvailabilityProvider->expects(self::once())
            ->method('isProductAllowedForRFP')
            ->with($product)
            ->willReturn(true);

        $this->getExtension()->buildForm($this->getFormBuilder(), []);

        self::assertCount(1, $this->entity->getRequestProducts());
        /** @var RequestProduct $requestProduct */
        $requestProduct = $this->entity->getRequestProducts()->first();

        self::assertEquals($product, $requestProduct->getProduct());
        self::assertEquals($product->getSku(), $requestProduct->getProductSku());
        self::assertEmpty($requestProduct->getKitItemLineItems());
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
}
