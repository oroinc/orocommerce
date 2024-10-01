<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\DataGrid\EventListener\FrontendLineItemsGrid;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid\LineItemsDataOnResultAfterListener;
use Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid\LineItemsGroupedOnResultAfterListener;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductImageStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\VisibilityBundle\Provider\ResolvedProductVisibilityProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LineItemsGroupedOnResultAfterListenerTest extends TestCase
{
    private AttachmentManager|MockObject $attachmentManager;

    private NumberFormatter|MockObject $numberFormatter;

    private ResolvedProductVisibilityProvider|MockObject $resolvedProductVisibilityProvider;

    private LineItemsGroupedOnResultAfterListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->resolvedProductVisibilityProvider = $this->createMock(ResolvedProductVisibilityProvider::class);

        $this->listener = new LineItemsGroupedOnResultAfterListener(
            $this->attachmentManager,
            $this->numberFormatter,
            $this->resolvedProductVisibilityProvider
        );

        $this->numberFormatter
            ->expects(self::any())
            ->method('formatCurrency')
            ->willReturnCallback(static fn ($value, $currency) => $value . $currency);
    }

    /**
     * @dataProvider onResultAfterWhenNotGroupedDataProvider
     */
    public function testOnResultAfterWhenNotGrouped(array $parameters): void
    {
        $event = $this->createMock(OrmResultAfter::class);
        $event
            ->expects(self::once())
            ->method('getDatagrid')
            ->willReturn($this->getDatagrid($parameters));

        $event
            ->expects(self::never())
            ->method('getRecords');

        $this->listener->onResultAfter($event);
    }

    public function onResultAfterWhenNotGroupedDataProvider(): array
    {
        return [
            'group is false' => ['parameters' => ['_parameters' => ['group' => false]]],
            'group is 0' => ['parameters' => ['_parameters' => ['group' => 0]]],
            'empty parameters' => ['parameters' => ['_parameters' => []]],
            'no parameters' => ['parameters' => []],
        ];
    }

    public function testOnResultAfterWhenNoRecords(): void
    {
        $event = new OrmResultAfter($this->getDatagrid(), [], $this->createMock(AbstractQuery::class));
        $this->listener->onResultAfter($event);

        self::assertCount(0, $event->getRecords());
    }

    /**
     * @dataProvider onResultAfterWhenSimpleRowDataProvider
     */
    public function testOnResultAfterWhenSimpleRow(?array $lineItemsByIds): void
    {
        $resultRecord = $this->createMock(ResultRecordInterface::class);
        $resultRecord
            ->expects(self::once())
            ->method('getValue')
            ->with(LineItemsDataOnResultAfterListener::LINE_ITEMS)
            ->willReturn($lineItemsByIds);

        $resultRecord
            ->expects(self::never())
            ->method('setValue');

        $event = new OrmResultAfter(
            $this->getDatagrid(['_parameters' => ['group' => true]]),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );
        $this->listener->onResultAfter($event);
    }

    public function onResultAfterWhenSimpleRowDataProvider(): array
    {
        return [
            [LineItemsDataOnResultAfterListener::LINE_ITEMS => null],
            [LineItemsDataOnResultAfterListener::LINE_ITEMS => []],
            [LineItemsDataOnResultAfterListener::LINE_ITEMS => [new ProductLineItemStub(10)]],
        ];
    }

    public function testOnResultAfterWhenProductKitRow(): void
    {
        $productKit = (new ProductStub())->setId(11)->setType(ProductStub::TYPE_KIT);
        $lineItem = (new ProductLineItemStub(10))
            ->setProduct($productKit);

        $resultRecord = $this->createMock(ResultRecordInterface::class);
        $resultRecord
            ->expects(self::once())
            ->method('getValue')
            ->with(LineItemsDataOnResultAfterListener::LINE_ITEMS)
            ->willReturn([111 => $lineItem, 222 => $lineItem]);

        $resultRecord
            ->expects(self::never())
            ->method('setValue');

        $event = new OrmResultAfter(
            $this->getDatagrid(['_parameters' => ['group' => true]]),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );
        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterWhenNotLineItem(): void
    {
        $resultRecord = $this->createMock(ResultRecordInterface::class);
        $resultRecord
            ->expects(self::once())
            ->method('getValue')
            ->with(LineItemsDataOnResultAfterListener::LINE_ITEMS)
            ->willReturn([new \stdClass(), new \stdClass()]);

        $resultRecord
            ->expects(self::never())
            ->method('setValue');

        $event = new OrmResultAfter(
            $this->getDatagrid(['_parameters' => ['group' => true]]),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );
        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterWhenNoParentProduct(): void
    {
        $resultRecord = $this->createMock(ResultRecordInterface::class);
        $resultRecord
            ->expects(self::once())
            ->method('getValue')
            ->with(LineItemsDataOnResultAfterListener::LINE_ITEMS)
            ->willReturn([new ProductLineItemStub(10), new ProductLineItemStub(20)]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Property parentProduct was expected to be not null');

        $event = new OrmResultAfter(
            $this->getDatagrid(['_parameters' => ['group' => true]]),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );
        $this->listener->onResultAfter($event);

        $resultRecord
            ->expects(self::never())
            ->method('setValue');
    }

    /**
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(array $recordData, array $expectedRecordData): void
    {
        $this->resolvedProductVisibilityProvider
            ->method('isVisible')
            ->willReturnMap([
                [11, true],
                [111, true],
                [1111, false],
            ]);

        $this->attachmentManager
            ->expects(self::any())
            ->method('getFilteredImageUrl')
            ->willReturnCallback(
                static fn (File $file, string $filterName) => $file->getFilename() . '_' . $filterName
            );

        $resultRecord = new ResultRecord($recordData);
        $event = new OrmResultAfter(
            $this->getDatagrid(['_parameters' => ['group' => true]]),
            [$resultRecord],
            $this->createMock(AbstractQuery::class)
        );
        $this->listener->onResultAfter($event);

        self::assertEquals(new ResultRecord($expectedRecordData), $resultRecord);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function onResultAfterDataProvider(): array
    {
        $productUnit = (new ProductUnit())->setCode('sample_unit');
        $parentProduct = (new ProductStub())->setId(11);
        $lineItem = (new ProductLineItemStub(10))
            ->setParentProduct($parentProduct)
            ->setUnit($productUnit);

        $parentProductWithImage = (new ProductStub())->setId(111);
        $lineItemWithImage = (new ProductLineItemStub(100))
            ->setParentProduct($parentProductWithImage)
            ->setUnit($productUnit);

        $productImage = new ProductImageStub();
        $productImage->setImage((new File())->setFilename('sample_filename'));
        $productImage->addType('listing');
        $parentProductWithImage->addImage($productImage);

        $parentProductInvisible = (new ProductStub())->setId(1111);
        $lineItemWithInvisibleParentProduct = (new ProductLineItemStub(1000))
            ->setParentProduct($parentProductInvisible)
            ->setUnit($productUnit);

        return [
            'empty line items data' => [
                'recordData' => [
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [111 => $lineItem, 222 => $lineItem],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [],
                ],
                'expectedRecordData' => [
                    'isConfigurable' => true,
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [111 => $lineItem, 222 => $lineItem],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [],
                    'id' => $parentProduct->getId() . '_' . $productUnit->getCode(),
                    'productId' => $parentProduct->getId(),
                    'sku' => null,
                    'image' => '',
                    'name' => '',
                    'isVisible' => true,
                    'quantity' => 0,
                    'unit' => $productUnit->getCode(),
                    'currency' => '',
                    'subtotalValue' => 0,
                    'discountValue' => 0,
                    'subData' => [],
                ],
            ],
            'with quantity and subtotals' => [
                'recordData' => [
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [111 => $lineItem, 222 => $lineItem],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [
                        111 => [
                            'name' => 'sample_name',
                            'id' => '111',
                            'quantity' => 10,
                            'subtotalValue' => 100,
                            'currency' => 'USD',
                        ],
                        222 => ['id' => '222', 'subtotalValue' => 200, 'quantity' => 20, 'currency' => 'USD'],
                    ],
                ],
                'expectedRecordData' => [
                    'isConfigurable' => true,
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [111 => $lineItem, 222 => $lineItem],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [
                        111 => [
                            'name' => 'sample_name',
                            'id' => '111',
                            'quantity' => 10,
                            'subtotalValue' => 100,
                            'currency' => 'USD',
                        ],
                        222 => ['subtotalValue' => 200, 'id' => '222', 'quantity' => 20, 'currency' => 'USD'],
                    ],
                    'id' => $parentProduct->getId() . '_' . $productUnit->getCode(),
                    'productId' => $parentProduct->getId(),
                    'sku' => null,
                    'image' => '',
                    'name' => 'sample_name',
                    'isVisible' => true,
                    'quantity' => 30,
                    'unit' => $productUnit->getCode(),
                    'currency' => 'USD',
                    'subtotalValue' => 300,
                    'discountValue' => 0,
                    'subData' => [
                        [
                            'name' => 'sample_name',
                            'quantity' => 10,
                            'subtotalValue' => 100,
                            'currency' => 'USD',
                            'filteredOut' => true,
                            'id' => '111',
                        ],
                        [
                            'subtotalValue' => 200,
                            'quantity' => 20,
                            'currency' => 'USD',
                            'filteredOut' => true,
                            'id' => '222',
                        ],
                    ],
                    'subtotal' => '300USD',
                ],
            ],
            'with discount' => [
                'recordData' => [
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [
                        111 => $lineItem,
                        222 => $lineItem,
                        333 => $lineItem,
                    ],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [
                        111 => [
                            'name' => 'sample_name',
                            'id' => '111',
                            'quantity' => 10,
                            'subtotalValue' => 100,
                            'currency' => 'USD',
                            'discountValue' => 10.5,
                            'initialSubtotal' => 20.5,
                        ],
                        222 => ['subtotalValue' => 200, 'quantity' => 20, 'currency' => 'USD', 'id' => '222',],
                        333 => [
                            'id' => '333',
                            'subtotalValue' => 300,
                            'quantity' => 30,
                            'currency' => 'USD',
                            'discountValue' => 30.5,
                            'initialSubtotal' => 330.5,
                        ],
                    ],
                ],
                'expectedRecordData' => [
                    'isConfigurable' => true,
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [
                        111 => $lineItem,
                        222 => $lineItem,
                        333 => $lineItem,
                    ],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [
                        111 => [
                            'name' => 'sample_name',
                            'id' => '111',
                            'quantity' => 10,
                            'subtotalValue' => 100,
                            'currency' => 'USD',
                            'discountValue' => 10.5,
                            'initialSubtotal' => 20.5,
                        ],
                        222 => ['subtotalValue' => 200, 'quantity' => 20, 'currency' => 'USD', 'id' => '222',],
                        333 => [
                            'id' => '333',
                            'subtotalValue' => 300,
                            'quantity' => 30,
                            'currency' => 'USD',
                            'discountValue' => 30.5,
                            'initialSubtotal' => 330.5,
                        ],
                    ],
                    'id' => $parentProduct->getId() . '_' . $productUnit->getCode(),
                    'productId' => $parentProduct->getId(),
                    'sku' => null,
                    'image' => '',
                    'name' => 'sample_name',
                    'isVisible' => true,
                    'quantity' => 60,
                    'unit' => $productUnit->getCode(),
                    'currency' => 'USD',
                    'subtotalValue' => 600.0,
                    'discountValue' => 41.0,
                    'subData' => [
                        [
                            'name' => 'sample_name',
                            'quantity' => 10,
                            'subtotalValue' => 100,
                            'currency' => 'USD',
                            'discountValue' => 10.5,
                            'initialSubtotal' => 20.5,
                            'filteredOut' => true,
                            'id' => '111',
                        ],
                        [
                            'subtotalValue' => 200,
                            'quantity' => 20,
                            'currency' => 'USD',
                            'filteredOut' => true,
                            'id' => '222',
                        ],
                        [
                            'subtotalValue' => 300,
                            'quantity' => 30,
                            'currency' => 'USD',
                            'discountValue' => 30.5,
                            'initialSubtotal' => 330.5,
                            'filteredOut' => true,
                            'id' => '333',
                        ],
                    ],
                    'subtotal' => '600USD',
                    'discount' => '41USD',
                    'initialSubtotal' => '641USD',
                ],
            ],
            'with image' => [
                'recordData' => [
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [111 => $lineItemWithImage, 222 => $lineItem],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [],
                ],
                'expectedRecordData' => [
                    'isConfigurable' => true,
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [111 => $lineItemWithImage, 222 => $lineItem],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [],
                    'id' => $parentProductWithImage->getId() . '_' . $productUnit->getCode(),
                    'productId' => $parentProductWithImage->getId(),
                    'sku' => null,
                    'image' => 'sample_filename_product_small',
                    'name' => '',
                    'isVisible' => true,
                    'quantity' => 0,
                    'unit' => $productUnit->getCode(),
                    'currency' => '',
                    'subtotalValue' => 0,
                    'discountValue' => 0,
                    'subData' => [],
                ],
            ],
            'with filteredOut' => [
                'recordData' => [
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [111 => $lineItemWithImage, 222 => $lineItem],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [111 => ['id' => 111], 222 => ['id' => 222]],
                    'displayedLineItemsIds' => '111',
                ],
                'expectedRecordData' => [
                    'isConfigurable' => true,
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [111 => $lineItemWithImage, 222 => $lineItem],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [111 => ['id' => 111], 222 => ['id' => 222]],
                    'displayedLineItemsIds' => '111',
                    'id' => $parentProductWithImage->getId() . '_' . $productUnit->getCode(),
                    'productId' => $parentProductWithImage->getId(),
                    'sku' => null,
                    'image' => 'sample_filename_product_small',
                    'name' => '',
                    'isVisible' => true,
                    'quantity' => 0,
                    'unit' => $productUnit->getCode(),
                    'currency' => '',
                    'subtotalValue' => null,
                    'discountValue' => null,
                    'subData' => [
                        ['id' => 111, 'filteredOut' => false],
                        ['id' => 222, 'filteredOut' => true],
                    ],
                ],
            ],
            'with invisible parent product' => [
                'recordData' => [
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [
                        1111 => $lineItemWithInvisibleParentProduct,
                        222 => $lineItem,
                    ],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [],
                ],
                'expectedRecordData' => [
                    'isConfigurable' => true,
                    LineItemsDataOnResultAfterListener::LINE_ITEMS => [
                        1111 => $lineItemWithInvisibleParentProduct,
                        222 => $lineItem,
                    ],
                    LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA => [],
                    'id' => $parentProductInvisible->getId() . '_' . $productUnit->getCode(),
                    'productId' => $parentProductInvisible->getId(),
                    'sku' => null,
                    'image' => '',
                    'name' => '',
                    'isVisible' => false,
                    'quantity' => 0,
                    'unit' => $productUnit->getCode(),
                    'currency' => '',
                    'subtotalValue' => 0,
                    'discountValue' => 0,
                    'subData' => [],
                ],
            ],
        ];
    }

    private function getDatagrid(array $parameters = []): Datagrid
    {
        return new Datagrid(
            'test-grid',
            DatagridConfiguration::create([]),
            new ParameterBag($parameters)
        );
    }
}
