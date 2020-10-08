<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\ProductName;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ConfigurableProductProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Stub\ProductImageStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\FrontendLineItemsGridEventListener;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Event\LineItemDataBuildEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FrontendLineItemsGridEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ShoppingListRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject */
    private $query;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var AttachmentManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attachmentManager;

    /** @var ConfigurableProductProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configurableProductProvider;

    /** @var FrontendLineItemsGridEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LineItemRepository::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->with(LineItem::class)
            ->willReturn($this->repository);

        $this->query = $this->createMock(AbstractQuery::class);
        $this->query
            ->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->any())
            ->method('generate')
            ->willReturnCallback(
                static fn (string $name, array $parameters) => $name
                    . '_' . implode('_', array_merge(array_keys($parameters), array_values($parameters)))
            );


        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $numberFormatter = $this->createMock(NumberFormatter::class);
        $numberFormatter
            ->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(static fn ($value, $currency) => $value . $currency);

        $this->attachmentManager = $this->createMock(AttachmentManager::class);
        $this->configurableProductProvider = $this->createMock(ConfigurableProductProvider::class);

        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper
            ->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn (Collection $collection) => $collection->first()->getString());

        $this->listener = new FrontendLineItemsGridEventListener(
            $urlGenerator,
            $this->eventDispatcher,
            $numberFormatter,
            $this->attachmentManager,
            $this->configurableProductProvider,
            $localizationHelper
        );
    }

    public function testOnResultAfterWhenNoRecords(): void
    {
        $this->repository
            ->expects($this->never())
            ->method('findIndexedByIds');

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->listener->onResultAfter(
            new OrmResultAfter(
                new Datagrid(
                    'test-grid',
                    DatagridConfiguration::create([]),
                    new ParameterBag()
                ),
                [],
                $this->query
            )
        );
    }

    public function testOnResultAfterWhenNoLineItemsIds(): void
    {
        $this->repository
            ->expects($this->never())
            ->method('findIndexedByIds');

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->listener->onResultAfter(
            new OrmResultAfter(
                new Datagrid(
                    'test-grid',
                    DatagridConfiguration::create([]),
                    new ParameterBag()
                ),
                [new ResultRecord([]), new ResultRecord(['allLineItemsIds' => '']), new ResultRecord(['id' => ''])],
                $this->query
            )
        );
    }

    public function testOnResultAfterWhenNoLineItems(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('findIndexedByIds')
            ->with([1001, 2002, 3003])
            ->willReturn([]);

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->listener->onResultAfter(
            new OrmResultAfter(
                new Datagrid(
                    'test-grid',
                    DatagridConfiguration::create([]),
                    new ParameterBag()
                ),
                [new ResultRecord(['allLineItemsIds' => '1001,2002']), new ResultRecord(['id' => '3003'])],
                $this->query
            )
        );
    }

    public function testOnResultAfterWhenSimple(): void
    {
        $product1 = $this->getProduct(101);
        $product2 = $this->getProduct(202);

        $lineItem1 = $this->getLineItem(1001, $product1);
        $lineItem2 = $this->getLineItem(2002, $product2);

        $lineItems = [1001 => $lineItem1, 2002 => $lineItem2];
        $this->repository
            ->expects($this->once())
            ->method('findIndexedByIds')
            ->with([1001, 2002])
            ->willReturn($lineItems);

        $productImage1 = new ProductImageStub();
        $productImage1->setImage(new File());
        $productImage1->addType('listing');
        $product1->addImage($productImage1);

        $productImage2 = new ProductImageStub();
        $productImage2->setImage(new File());
        $productImage2->addType('main');
        $product2->addImage($productImage2);

        $productImage1Url = '/image1/url';
        $this->attachmentManager
            ->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($productImage1->getImage(), 'product_small')
            ->willReturn($productImage1Url);

        $datagrid = new Datagrid('test-grid', DatagridConfiguration::create([]), new ParameterBag());

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function ($event) use ($lineItems, $lineItem1, $datagrid) {
                    $this->assertInstanceOf(LineItemDataBuildEvent::class, $event);
                    $this->assertEquals($lineItems, $event->getLineItems());
                    $this->assertEquals(['datagrid' => $datagrid], $event->getContext());

                    $event->addDataForLineItem($lineItem1->getId(), 'subtotal', 4200);
                }
            );

        $record1 = new ResultRecord(['id' => $lineItem1->getId()]);
        $record2 = new ResultRecord(['allLineItemsIds' => $lineItem2->getId()]);

        $this->listener->onResultAfter(new OrmResultAfter($datagrid, [$record1, $record2], $this->query));

        $this->assertEquals(1001, $record1->getValue('id'));
        $this->assertEquals(101, $record1->getValue('productId'));
        $this->assertEquals('sku101', $record1->getValue('sku'));
        $this->assertEquals(10010, $record1->getValue('quantity'));
        $this->assertEquals('unit1001', $record1->getValue('unit'));
        $this->assertEquals('notes1001', $record1->getValue('notes'));
        $this->assertEquals('name101', $record1->getValue('name'));
        $this->assertEquals(4200, $record1->getValue('subtotal'));
        $this->assertEquals('oro_product_frontend_product_view_id_101', $record1->getValue('link'));
        $this->assertEquals($productImage1Url, $record1->getValue('image'));

        $this->assertEquals(2002, $record2->getValue('id'));
        $this->assertEquals(202, $record2->getValue('productId'));
        $this->assertEquals('sku202', $record2->getValue('sku'));
        $this->assertEquals(20020, $record2->getValue('quantity'));
        $this->assertEquals('unit2002', $record2->getValue('unit'));
        $this->assertEquals('notes2002', $record2->getValue('notes'));
        $this->assertEquals('name202', $record2->getValue('name'));
        $this->assertEquals('oro_product_frontend_product_view_id_202', $record2->getValue('link'));
        $this->assertEquals('', $record2->getValue('image'));
    }

    public function testOnResultAfterWhenConfigurableSingle(): void
    {
        $parentProduct1 = $this->getProduct(1);
        $product1 = $this->getProduct(101);

        $lineItem1 = $this->getLineItem(1001, $product1);
        $lineItem1->setParentProduct($parentProduct1);

        $lineItems = [1001 => $lineItem1];
        $this->repository
            ->expects($this->once())
            ->method('findIndexedByIds')
            ->with([1001])
            ->willReturn($lineItems);

        $productImage1 = new ProductImageStub();
        $productImage1->setImage(new File());
        $productImage1->addType('listing');
        $product1->addImage($productImage1);

        $productImage1Url = '/image1/url';
        $this->attachmentManager
            ->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($productImage1->getImage(), 'product_small')
            ->willReturn($productImage1Url);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch');

        $record1 = new ResultRecord(['allLineItemsIds' => $lineItem1->getId(), 'isConfigurable' => true]);

        $this->listener->onResultAfter(
            new OrmResultAfter(
                new Datagrid('test-grid', DatagridConfiguration::create([]), new ParameterBag()),
                [$record1],
                $this->query
            )
        );

        $this->assertEquals(false, $record1->getValue('isConfigurable'));
        $this->assertEquals(1001, $record1->getValue('id'));
        $this->assertEquals(101, $record1->getValue('productId'));
        $this->assertEquals('sku101', $record1->getValue('sku'));
        $this->assertEquals(10010, $record1->getValue('quantity'));
        $this->assertEquals('unit1001', $record1->getValue('unit'));
        $this->assertEquals('notes1001', $record1->getValue('notes'));
        $this->assertEquals('name1', $record1->getValue('name'));
        $this->assertEquals('oro_product_frontend_product_view_id_variantProductId_1_101', $record1->getValue('link'));
        $this->assertEquals($productImage1Url, $record1->getValue('image'));
    }

    public function testOnResultAfterWhenConfigurableSingleAndEmptyLineItem(): void
    {
        $parentProduct1 = $this->getProduct(1);
        $lineItem1 = $this->getLineItem(1001, $parentProduct1);

        $this->repository
            ->expects($this->once())
            ->method('findIndexedByIds')
            ->with([1001])
            ->willReturn([1001 => $lineItem1]);

        $productImage1 = new ProductImageStub();
        $productImage1->setImage(new File());
        $productImage1->addType('listing');
        $parentProduct1->addImage($productImage1);

        $productImage1Url = '/image1/url';
        $this->attachmentManager
            ->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($productImage1->getImage(), 'product_small')
            ->willReturn($productImage1Url);

        $record1 = new ResultRecord(['allLineItemsIds' => $lineItem1->getId(), 'isConfigurable' => true]);

        $this->listener->onResultAfter(
            new OrmResultAfter(
                new Datagrid('test-grid', DatagridConfiguration::create([]), new ParameterBag()),
                [$record1],
                $this->query
            )
        );

        $this->assertEquals(false, $record1->getValue('isConfigurable'));
        $this->assertEquals(1001, $record1->getValue('id'));
        $this->assertEquals(1, $record1->getValue('productId'));
        $this->assertEquals('sku1', $record1->getValue('sku'));
        $this->assertEquals(10010, $record1->getValue('quantity'));
        $this->assertEquals('unit1001', $record1->getValue('unit'));
        $this->assertEquals('notes1001', $record1->getValue('notes'));
        $this->assertEquals('name1', $record1->getValue('name'));
        $this->assertEquals('oro_product_frontend_product_view_id_variantProductId_1_1', $record1->getValue('link'));
        $this->assertEquals($productImage1Url, $record1->getValue('image'));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testOnResultAfterWhenConfigurable(): void
    {
        $parentProduct1 = $this->getProduct(11);
        $parentProduct2 = $this->getProduct(22);
        $product1 = $this->getProduct(101);
        $product2 = $this->getProduct(202);
        $product3 = $this->getProduct(303);

        $lineItem1 = $this->getLineItem(1001, $product1);
        $lineItem1->setParentProduct($parentProduct1);
        $lineItem2 = $this->getLineItem(2002, $product2);
        $lineItem2->setParentProduct($parentProduct2);
        $lineItem3 = $this->getLineItem(3003, $product3);

        $lineItems = [1001 => $lineItem1, 2002 => $lineItem2, 3003 => $lineItem3];
        $this->repository
            ->expects($this->once())
            ->method('findIndexedByIds')
            ->with([1001, 2002, 3003])
            ->willReturn($lineItems);

        $parentProductImage2 = new ProductImageStub();
        $parentProductImage2->setImage(new File());
        $parentProductImage2->addType('listing');
        $parentProduct2->addImage($parentProductImage2);

        $productImage2 = new ProductImageStub();
        $productImage2->setImage(new File());
        $productImage2->addType('listing');
        $product2->addImage($productImage2);

        $productImage2Url = '/image1/url';
        $parentProductImage2Url = '/image2/url';
        $this->attachmentManager
            ->expects($this->exactly(2))
            ->method('getFilteredImageUrl')
            ->withConsecutive(
                [$productImage2->getImage(), 'product_small'],
                [$parentProductImage2->getImage(), 'product_small']
            )
            ->willReturn($productImage2Url, $parentProductImage2Url);

        $this->configurableProductProvider
            ->expects($this->exactly(3))
            ->method('getVariantFieldsValuesForLineItem')
            ->willReturnCallback(
                static function (LineItem $lineItem) {
                    $id = $lineItem->getProduct()->getId();

                    return [$id => ['sample_field' => 'sample_value' . $id]];
                }
            );

        $datagrid = new Datagrid('test-grid', DatagridConfiguration::create([]), new ParameterBag());

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(
                function ($event) use ($lineItems, $lineItem1, $lineItem2, $lineItem3, $datagrid) {
                    $this->assertInstanceOf(LineItemDataBuildEvent::class, $event);
                    $this->assertEquals($lineItems, $event->getLineItems());
                    $this->assertEquals(['datagrid' => $datagrid], $event->getContext());

                    $event->addDataForLineItem($lineItem1->getId(), 'subtotal', '4000USD');
                    $event->addDataForLineItem($lineItem2->getId(), 'subtotalValue', 1000);
                    $event->addDataForLineItem($lineItem2->getId(), 'discountValue', 400);
                    $event->addDataForLineItem($lineItem2->getId(), 'currency', 'USD');
                    $event->addDataForLineItem($lineItem3->getId(), 'subtotalValue', 500);
                    $event->addDataForLineItem($lineItem3->getId(), 'currency', 'USD');
                }
            );

        $record1 = new ResultRecord(
            ['id' => $lineItem1->getId(), 'displayedLineItemsIds' => $lineItem1->getId(), 'isConfigurable' => true]
        );
        $record2 = new ResultRecord(
            [
                'allLineItemsIds' => $lineItem2->getId() . ',' . $lineItem3->getId(),
                'displayedLineItemsIds' => $lineItem2->getId() . ',' . $lineItem3->getId(),
                'isConfigurable' => true,
            ]
        );

        $this->listener->onResultAfter(new OrmResultAfter($datagrid, [$record1, $record2], $this->query));

        $this->assertEquals(false, $record1->getValue('isConfigurable'));
        $this->assertEquals(1001, $record1->getValue('id'));
        $this->assertEquals(101, $record1->getValue('productId'));
        $this->assertEquals('sku101', $record1->getValue('sku'));
        $this->assertEquals(10010, $record1->getValue('quantity'));
        $this->assertEquals('unit1001', $record1->getValue('unit'));
        $this->assertEquals('notes1001', $record1->getValue('notes'));
        $this->assertEquals('name11', $record1->getValue('name'));
        $this->assertEquals('4000USD', $record1->getValue('subtotal'));
        $this->assertEquals('oro_product_frontend_product_view_id_variantProductId_11_101', $record1->getValue('link'));
        $this->assertEquals('', $record1->getValue('image'));

        $this->assertEquals(true, $record2->getValue('isConfigurable'));
        $this->assertEquals('22_unit2002', $record2->getValue('id'));
        $this->assertEquals(22, $record2->getValue('productId'));
        $this->assertNull($record2->getValue('sku'));
        $this->assertEquals(50050, $record2->getValue('quantity'));
        $this->assertEquals('unit2002', $record2->getValue('unit'));
        $this->assertEquals('name22', $record2->getValue('name'));
        $this->assertEquals('1900USD', $record2->getValue('initialSubtotal'));
        $this->assertEquals('1500USD', $record2->getValue('subtotal'));
        $this->assertEquals('400USD', $record2->getValue('discount'));
        $this->assertEquals('oro_product_frontend_product_view_id_22', $record2->getValue('link'));
        $this->assertEquals($parentProductImage2Url, $record2->getValue('image'));
        $this->assertEquals(
            [
                [
                    'id' => 2002,
                    'productId' => 202,
                    'sku' => 'sku202',
                    'quantity' => 20020,
                    'unit' => 'unit2002',
                    'notes' => 'notes2002',
                    'image' => $productImage2Url,
                    'name' => 'name22',
                    'productConfiguration' => [
                        'sample_field' => 'sample_value202',
                    ],
                    'filteredOut' => false,
                    'action_configuration' => [
                        'add_notes' => false,
                        'edit_notes' => false,
                        'update_configurable' => false,
                    ],
                    'subtotalValue' => 1000,
                    'discountValue' => 400,
                    'currency' => 'USD',
                ],
                [
                    'id' => 3003,
                    'productId' => 303,
                    'sku' => 'sku303',
                    'quantity' => 30030,
                    'unit' => 'unit3003',
                    'notes' => 'notes3003',
                    'image' => '',
                    'name' => 'name22',
                    'productConfiguration' => [
                        'sample_field' => 'sample_value303',
                    ],
                    'filteredOut' => false,
                    'action_configuration' => [
                        'add_notes' => false,
                        'edit_notes' => false,
                        'update_configurable' => false,
                    ],
                    'subtotalValue' => 500,
                    'currency' => 'USD',
                ],
            ],
            $record2->getValue('subData')
        );
    }

    /**
     * @param int $id
     * @param Product $product
     * @return LineItem
     */
    private function getLineItem(int $id, Product $product): LineItem
    {
        return $this->getEntity(
            LineItem::class,
            [
                'id' => $id,
                'product' => $product,
                'unit' => $this->getProductUnit('unit' . $id),
                'quantity' => $id * 10,
                'notes' => 'notes' . $id,
            ]
        );
    }

    /**
     * @param int $id
     * @return Product
     */
    private function getProduct(int $id): Product
    {
        return $this->getEntity(
            Product::class,
            [
                'id' => $id,
                'sku' => 'sku' . $id,
                'names' => new ArrayCollection([(new ProductName())->setString('name' . $id)]),
            ]
        );
    }

    /**
     * @param string $code
     * @return ProductUnit
     */
    private function getProductUnit(string $code): ProductUnit
    {
        return $this->getEntity(ProductUnit::class, ['code' => $code, 'defaultPrecision' => 0]);
    }
}
