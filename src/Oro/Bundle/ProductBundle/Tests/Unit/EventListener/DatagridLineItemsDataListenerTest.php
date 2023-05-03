<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ProductBundle\EventListener\DatagridLineItemsDataListener;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductImageStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;

class DatagridLineItemsDataListenerTest extends \PHPUnit\Framework\TestCase
{
    private ConfigurableProductProvider|MockObject $configurableProductProvider;

    private DatagridLineItemsDataListener $listener;

    protected function setUp(): void
    {
        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper
            ->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn (Collection $values) => $values->first());

        $attachmentManager = $this->createMock(AttachmentManager::class);
        $attachmentManager
            ->expects($this->any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(
                static fn (File $file, string $filterName) => $file->getFilename() . '_' . $filterName
            );

        $this->configurableProductProvider = $this->createMock(ConfigurableProductProvider::class);

        $this->listener = new DatagridLineItemsDataListener(
            $this->configurableProductProvider,
            $localizationHelper,
            $attachmentManager
        );
    }

    public function testOnLineItemDataWhenNoLineItems(): void
    {
        $event = $this->createMock(DatagridLineItemsDataEvent::class);

        $event
            ->expects($this->once())
            ->method('getLineItems')
            ->willReturn([]);

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $event
            ->expects($this->never())
            ->method('addDataForLineItem');

        $this->listener->onLineItemData($event);
    }

    /**
     * @dataProvider onLineItemDataDataProvider
     */
    public function testOnLineItemData(
        ProductLineItemStub $lineItem,
        array $productConfiguration,
        array $expectedData
    ): void {
        $this->configurableProductProvider
            ->expects($this->any())
            ->method('getVariantFieldsValuesForLineItem')
            ->with($lineItem, true)
            ->willReturn($productConfiguration);

        $event = new DatagridLineItemsDataEvent(
            [$lineItem->getEntityIdentifier() => $lineItem],
            [],
            $this->createMock(DatagridInterface::class),
            []
        );

        $this->listener->onLineItemData($event);

        $this->assertEquals($expectedData, $event->getDataForLineItem($lineItem->getEntityIdentifier()));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onLineItemDataDataProvider(): array
    {
        // Variables for cases 'without parent product'
        $product1 = (new ProductStub())
            ->setId(1)
            ->setSku('p1')
            ->setNames([(new ProductName())->setString($product1Name = 'Product1')]);
        $productUnit1 = (new ProductUnit())->setCode('sample_unit1');
        $lineItemWithoutParent = (new ProductLineItemStub(10))
            ->setProduct($product1)
            ->setQuantity(123)
            ->setUnit($productUnit1);

        // Variables for case 'with parent product'
        $parentProduct = (new ProductStub())
            ->setId(2)
            ->setNames([(new ProductName())->setString($parentProductName = 'Product2')]);
        $product2 = (new ProductStub())
            ->setId(2)
            ->setSku('p2');
        $productUnit2 = (new ProductUnit())->setCode('sample_unit2');
        $lineItemWithParent = (new ProductLineItemStub(20))
            ->setParentProduct($parentProduct)
            ->setProduct($product2)
            ->setQuantity(456)
            ->setUnit($productUnit2);

        // Variables for case 'with configurable product'
        $configurableProduct1Name = 'ConfigurableProduct1';
        $configurableProduct1 = (new ProductStub())
            ->setId(2)
            ->setNames([(new ProductName())->setString($configurableProduct1Name)])
            ->setType(Product::TYPE_CONFIGURABLE);
        $configurableProduct1Unit = (new ProductUnit())->setCode('sample_unit_of_configurable_product');
        $lineItemWithConfigurableProduct = (new ProductLineItemStub(20))
            ->setProduct($configurableProduct1)
            ->setQuantity(1)
            ->setUnit($configurableProduct1Unit);

        // Variables for case 'with image'
        $productWithImage = (new ProductStub())
            ->setId(3)
            ->setSku('p3')
            ->setNames([(new ProductName())->setString($product3Name = 'Product3')]);
        $productImage = new ProductImageStub();
        $productImage->setImage((new File())->setFilename('sample_filename4'));
        $productImage->addType('listing');
        $productWithImage->addImage($productImage);

        $productUnit3 = (new ProductUnit())->setCode('sample_unit3');
        $lineItemWithImage = (new ProductLineItemStub(30))
            ->setProduct($productWithImage)
            ->setQuantity(789)
            ->setUnit($productUnit3);

        // Variables for cases 'with unit precision'
        $productUnit4 = (new ProductUnit())->setCode('sample_unit4');
        $productUnitPrecision = (new ProductUnitPrecision())
            ->setUnit($productUnit4)
            ->setPrecision(3);
        $productWithUnitPrecision = (new ProductStub())
            ->setId(4)
            ->setSku('p4')
            ->setNames([(new ProductName())->setString($product4Name = 'Product4')])
            ->addUnitPrecision($productUnitPrecision);
        $lineItemWithUnitPrecision = (new ProductLineItemStub(10))
            ->setProduct($productWithUnitPrecision)
            ->setQuantity(1234)
            ->setUnit($productUnit4);

        // Variables for cases 'without unit precision'
        $productUnit5 = (new ProductUnit())->setCode('sample_unit5');
        $disabledProductUnitPrecision = (new ProductUnitPrecision())
            ->setUnit($productUnit4)
            ->setPrecision(3)
            ->setSell(false);
        $productWithDisabledUnitPrecision = (new ProductStub())
            ->setId(5)
            ->setSku('p5')
            ->setNames([(new ProductName())->setString($product5Name = 'Product5')])
            ->addUnitPrecision($disabledProductUnitPrecision);
        $lineItemWithDisabledUnitPrecision = (new ProductLineItemStub(10))
            ->setProduct($productWithDisabledUnitPrecision)
            ->setQuantity(5678)
            ->setUnit($productUnit5);

        return [
            'without parent product' => [
                'lineItem' => $lineItemWithoutParent,
                'productConfiguration' => [],
                'expectedData' => [
                    'id' => $lineItemWithoutParent->getEntityIdentifier(),
                    'productId' => $product1->getId(),
                    'sku' => $product1->getSku(),
                    'image' => '',
                    'name' => $product1Name,
                    'quantity' => $lineItemWithoutParent->getQuantity(),
                    'unit' => $productUnit1->getCode(),
                    'isConfigurable' => false,
                ],
            ],
            'with parent product' => [
                'lineItem' => $lineItemWithParent,
                'productConfiguration' => [$product2->getId() => ['sample_field' => 'sample_value']],
                'expectedData' => [
                    'id' => $lineItemWithParent->getEntityIdentifier(),
                    'productId' => $parentProduct->getId(),
                    'variantId' => $product2->getId(),
                    'sku' => $product2->getSku(),
                    'image' => '',
                    'name' => $parentProductName,
                    'quantity' => $lineItemWithParent->getQuantity(),
                    'unit' => $productUnit2->getCode(),
                    'productConfiguration' => ['sample_field' => 'sample_value'],
                    'isConfigurable' => false,
                ],
            ],
            'with configurable product' => [
                'lineItem' => $lineItemWithConfigurableProduct,
                'productConfiguration' => [],
                'expectedData' => [
                    'id' => $lineItemWithConfigurableProduct->getEntityIdentifier(),
                    'productId' => $parentProduct->getId(),
                    'sku' => $configurableProduct1->getSku(),
                    'image' => '',
                    'name' => $configurableProduct1Name,
                    'quantity' => $lineItemWithConfigurableProduct->getQuantity(),
                    'unit' => $configurableProduct1Unit->getCode(),
                    'isConfigurable' => true,
                ],
            ],
            'with image' => [
                'lineItem' => $lineItemWithImage,
                'productConfiguration' => [],
                'expectedData' => [
                    'id' => $lineItemWithImage->getEntityIdentifier(),
                    'productId' => $productWithImage->getId(),
                    'sku' => $productWithImage->getSku(),
                    'image' => 'sample_filename4_product_small',
                    'name' => $product3Name,
                    'quantity' => $lineItemWithImage->getQuantity(),
                    'unit' => $productUnit3->getCode(),
                    'isConfigurable' => false,
                ],
            ],
            'with unit precision' => [
                'lineItem' => $lineItemWithUnitPrecision,
                'productConfiguration' => [],
                'expectedData' => [
                    'id' => $lineItemWithUnitPrecision->getEntityIdentifier(),
                    'productId' => $productWithUnitPrecision->getId(),
                    'sku' => $productWithUnitPrecision->getSku(),
                    'image' => '',
                    'name' => $product4Name,
                    'quantity' => $lineItemWithUnitPrecision->getQuantity(),
                    'unit' => $productUnit4->getCode(),
                    'units' => [
                        $productUnit4->getCode() => ['precision' => $productUnitPrecision->getPrecision()],
                    ],
                    'isConfigurable' => false,
                ],
            ],
            'with disabled unit precision' => [
                'lineItem' => $lineItemWithDisabledUnitPrecision,
                'productConfiguration' => [],
                'expectedData' => [
                    'id' => $lineItemWithDisabledUnitPrecision->getEntityIdentifier(),
                    'productId' => $productWithDisabledUnitPrecision->getId(),
                    'sku' => $productWithDisabledUnitPrecision->getSku(),
                    'image' => '',
                    'name' => $product5Name,
                    'quantity' => $lineItemWithDisabledUnitPrecision->getQuantity(),
                    'unit' => $productUnit5->getCode(),
                    'isConfigurable' => false,
                ],
            ],
            'without product' => [
                'lineItem' => (new ProductLineItemStub(10))
                    ->setQuantity(123)
                    ->setUnit($productUnit1),
                'productConfiguration' => [],
                'expectedData' => [
                    'id' => 10,
                    'sku' => null,
                    'name' => '',
                    'quantity' => 123,
                    'unit' => $productUnit1->getCode(),
                ],
            ],
        ];
    }
}
