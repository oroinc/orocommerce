<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\FormBundle\Form\Type\Select2EntityType;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\OrderBundle\Form\Section\SectionProvider;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\PricingBundle\Form\Type\PriceTypeSelectorType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductKitItemProduct;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectEntityTypeStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraints\Range;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class OrderLineItemTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait;
    use OrderLineItemTypeTrait;
    use OrderProductKitItemLineItemTypeTrait;

    private SectionProvider|MockObject $sectionProvider;

    private Product $productSimple1;

    private Product $productSimple2;

    private Product $productKit1;

    private Product $productKit2;

    private Product $kitItemProduct1;

    private Product $kitItemProduct2;

    private Product $kitItemProduct3;

    private Product $kitItemProduct4;

    private ProductKitItem $kitItem1;

    private ProductKitItem $kitItem2;

    private ProductKitItem $kitItem3;

    private ProductKitItem $kitItem4;

    private ProductUnit $productUnitItem;

    private ProductUnit $productUnitKg;

    private OrderLineItemType $formType;

    private array $sectionsConfig = [
        'quantity' => ['data' => ['quantity' => [], 'productUnit' => []], 'order' => 10],
        'price' => [
            'data' => [
                'price' => [
                    'page_component' => 'oroui/js/app/components/view-component',
                    'page_component_options' => [
                        'view' => 'oropricing/js/app/views/line-item-product-prices-view',
                    ],
                ],
                'priceType' => [],
            ],
            'order' => 20,
        ],
        'ship_by' => ['data' => ['shipBy' => []], 'order' => 30],
        'comment' => [
            'data' => ['comment' => ['page_component' => 'oroorder/js/app/components/notes-component']],
            'order' => 40,
        ],
    ];

    protected function setUp(): void
    {
        $this->productUnitItem = (new ProductUnit())->setCode('item');
        $this->productUnitKg = (new ProductUnit())->setCode('kg');
        $this->productSimple1 = (new ProductStub())
            ->setId(100)
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($this->productUnitItem)->setPrecision(0));
        $this->productSimple2 = (new ProductStub())
            ->setId(101)
            ->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($this->productUnitKg)->setPrecision(3));
        $this->kitItemProduct1 = (new ProductStub())->setId(300);
        $this->kitItemProduct2 = (new ProductStub())->setId(301);
        $this->kitItemProduct3 = (new ProductStub())->setId(302);
        $this->kitItemProduct4 = (new ProductStub())->setId(303);
        $this->kitItem1 = (new ProductKitItemStub(400))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct1))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct2));
        $this->kitItem2 = (new ProductKitItemStub(401))
            ->setOptional(true)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct3));
        $this->kitItem3 = (new ProductKitItemStub(402))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct4))
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct3));
        $this->kitItem4 = (new ProductKitItemStub(403))
            ->setOptional(true)
            ->addKitItemProduct((new ProductKitItemProduct())->setProduct($this->kitItemProduct2));
        $this->productKit1 = (new ProductStub())
            ->setId(200)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($this->kitItem1)
            ->addKitItem($this->kitItem2);
        $this->productKit2 = (new ProductStub())
            ->setId(201)
            ->setType(Product::TYPE_KIT)
            ->addKitItem($this->kitItem3)
            ->addKitItem($this->kitItem4);

        $productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $productUnitsProvider
            ->method('getAvailableProductUnitsWithPrecision')
            ->willReturn([
                $this->productUnitItem->getCode() => 0,
                $this->productUnitKg->getCode() => 3,
            ]);
        $this->sectionProvider = $this->createMock(SectionProvider::class);

        $this->formType = $this->createOrderLineItemType(
            $this,
            [
                $this->productUnitItem->getCode() => 0,
                $this->productUnitKg->getCode() => 3,
            ]
        );
        $this->formType->setSectionProvider($this->sectionProvider);

        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $priceType = new PriceType();
        $priceType->setDataClass(Price::class);

        $kitItemProducts = [
            $this->kitItemProduct1->getId() => $this->kitItemProduct1,
            $this->kitItemProduct2->getId() => $this->kitItemProduct2,
            $this->kitItemProduct3->getId() => $this->kitItemProduct3,
            $this->kitItemProduct4->getId() => $this->kitItemProduct4,
        ];

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        $this->formType,
                        ProductSelectType::class => new ProductSelectEntityTypeStub([
                            $this->productSimple1->getId() => $this->productSimple1,
                            $this->productSimple2->getId() => $this->productSimple2,
                            $this->productKit1->getId() => $this->productKit1,
                            $this->productKit2->getId() => $this->productKit2,
                        ]),
                        ProductUnitSelectionType::class => new EntityTypeStub([
                            $this->productUnitKg->getCode() => $this->productUnitKg,
                            $this->productUnitItem->getCode() => $this->productUnitItem,
                        ]),
                        $priceType,
                        PriceTypeSelectorType::class => new PriceTypeSelectorType(),
                        $this->getQuantityType(),
                        Select2EntityType::class => new EntityTypeStub($kitItemProducts),
                        $this->createOrderProductKitItemLineItemType($this, $kitItemProducts),
                    ],
                    []
                ),
            ]
        );
    }

    public function testBuildFormWhenNoLineItem(): void
    {
        $form = $this->factory->create(OrderLineItemType::class, null);

        $this->assertFormOptionEqual(OrderLineItem::class, 'data_class', $form);
        $this->assertFormOptionEqual('order_line_item', 'csrf_token_id', $form);
        $this->assertFormOptionEqual('oroui/js/app/components/view-component', 'page_component', $form);
        $this->assertFormOptionEqual([
            'view' => 'oroorder/js/app/views/line-item-view',
            'freeFormUnits' => [
                $this->productUnitItem->getCode() => 0,
                $this->productUnitKg->getCode() => 3,
            ],
        ], 'page_component_options', $form);
        $this->assertFormOptionEqual(null, 'currency', $form);

        $this->assertFormContainsField('product', $form);
        $this->assertFormOptionEqual(
            'oro_order_product_visibility_limited',
            'autocomplete_alias',
            $form->get('product')
        );
        $this->assertFormOptionEqual(
            'products-select-grid',
            'grid_name',
            $form->get('product')
        );
        $this->assertFormOptionEqual(
            ['types' => [Product::TYPE_SIMPLE, Product::TYPE_KIT]],
            'grid_parameters',
            $form->get('product')
        );

        $this->assertFormContainsField('productUnit', $form);
        $this->assertFormContainsField('productSku', $form);
        $this->assertFormContainsField('freeFormProduct', $form);
        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual([new Range(['min' => 0]), new Decimal()], 'constraints', $form->get('quantity'));
        $this->assertFormContainsField('kitItemLineItems', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(null, 'product', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual(null, 'currency', $form->get('kitItemLineItems'));
        $this->assertFormContainsField('comment', $form);
        $this->assertFormContainsField('shipBy', $form);
        $this->assertFormContainsField('price', $form);
        $this->assertFormContainsField('priceType', $form);

        self::assertNull($form->getData());
        self::assertNull($form->get('product')->getData());
        self::assertNull($form->get('productUnit')->getData());
        self::assertNull($form->get('productSku')->getData());
        self::assertNull($form->get('freeFormProduct')->getData());
        self::assertEquals(1, $form->get('quantity')->getData());
        self::assertNull($form->get('comment')->getData());
        self::assertNull($form->get('shipBy')->getData());
        self::assertNull($form->get('kitItemLineItems')->getData());
        self::assertNull($form->get('price')->getData());
        self::assertEquals(PriceTypeAwareInterface::PRICE_TYPE_UNIT, $form->get('priceType')->getData());

        $this->sectionProvider
            ->expects(self::once())
            ->method('addSections')
            ->with(OrderLineItemType::class, $this->sectionsConfig);

        $formView = $form->createView();

        self::assertArrayHasKey('page_component', $formView->vars);
        self::assertArrayHasKey('page_component_options', $formView->vars);
        self::assertEquals('oroui/js/app/components/view-component', $formView->vars['page_component']);
        self::assertEquals(
            [
                'view' => 'oroorder/js/app/views/line-item-view',
                'freeFormUnits' => [
                    $this->productUnitItem->getCode() => 0,
                    $this->productUnitKg->getCode() => 3,
                ],
                'currency' => null,
                'fullName' => 'oro_order_line_item',
            ],
            $formView->vars['page_component_options']
        );
    }

    public function testBuildFormWhenHasLineItem(): void
    {
        $orderLineItem = (new OrderLineItem())
            ->setProduct($this->productSimple1)
            ->setQuantity(12.3456)
            ->setProductUnit($this->productUnitItem)
            ->setComment('sample comment')
            ->setShipBy(new \DateTime())
            ->setPrice(Price::create(34.5678, 'USD'));

        $form = $this->factory->create(OrderLineItemType::class, $orderLineItem, ['currency' => 'USD']);

        $this->assertFormOptionEqual(OrderLineItem::class, 'data_class', $form);
        $this->assertFormOptionEqual('order_line_item', 'csrf_token_id', $form);
        $this->assertFormOptionEqual('oroui/js/app/components/view-component', 'page_component', $form);
        $this->assertFormOptionEqual([
            'view' => 'oroorder/js/app/views/line-item-view',
            'freeFormUnits' => [
                $this->productUnitItem->getCode() => 0,
                $this->productUnitKg->getCode() => 3,
            ],
        ], 'page_component_options', $form);
        $this->assertFormOptionEqual('USD', 'currency', $form);

        $this->assertFormContainsField('product', $form);
        $this->assertFormOptionEqual(
            'oro_order_product_visibility_limited',
            'autocomplete_alias',
            $form->get('product')
        );
        $this->assertFormOptionEqual(
            'products-select-grid',
            'grid_name',
            $form->get('product')
        );
        $this->assertFormOptionEqual(
            ['types' => [Product::TYPE_SIMPLE, Product::TYPE_KIT]],
            'grid_parameters',
            $form->get('product')
        );

        $this->assertFormContainsField('productUnit', $form);
        $this->assertFormContainsField('productSku', $form);
        $this->assertFormContainsField('freeFormProduct', $form);
        $this->assertFormContainsField('quantity', $form);
        $this->assertFormOptionEqual([new Range(['min' => 0]), new Decimal()], 'constraints', $form->get('quantity'));
        $this->assertFormContainsField('kitItemLineItems', $form);
        $this->assertFormOptionEqual(false, 'required', $form->get('kitItemLineItems'));
        $this->assertFormOptionEqual('USD', 'currency', $form->get('kitItemLineItems'));
        $this->assertFormContainsField('comment', $form);
        $this->assertFormContainsField('shipBy', $form);

        self::assertSame($orderLineItem, $form->getData());
        self::assertSame($orderLineItem->getProduct(), $form->get('product')->getData());
        self::assertSame($orderLineItem->getProductUnit(), $form->get('productUnit')->getData());
        self::assertNull($form->get('productSku')->getData());
        self::assertNull($form->get('freeFormProduct')->getData());
        self::assertEquals($orderLineItem->getQuantity(), $form->get('quantity')->getData());
        self::assertEquals($orderLineItem->getComment(), $form->get('comment')->getData());
        self::assertEquals($orderLineItem->getShipBy(), $form->get('shipBy')->getData());
        self::assertEquals($orderLineItem->getKitItemLineItems(), $form->get('kitItemLineItems')->getData());
        self::assertEquals($orderLineItem->getPrice(), $form->get('price')->getData());
        self::assertEquals(PriceTypeAwareInterface::PRICE_TYPE_UNIT, $form->get('priceType')->getData());

        $this->sectionProvider
            ->expects(self::once())
            ->method('addSections')
            ->with(OrderLineItemType::class, $this->sectionsConfig);

        $formView = $form->createView();

        self::assertArrayHasKey('page_component', $formView->vars);
        self::assertArrayHasKey('page_component_options', $formView->vars);
        self::assertEquals('oroui/js/app/components/view-component', $formView->vars['page_component']);
        self::assertEquals(
            [
                'view' => 'oroorder/js/app/views/line-item-view',
                'freeFormUnits' => [
                    $this->productUnitItem->getCode() => 0,
                    $this->productUnitKg->getCode() => 3,
                ],
                'currency' => 'USD',
                'modelAttr' => ['product_units' => [$this->productUnitItem->getCode() => 0], 'checksum' => ''],
                'fullName' => 'oro_order_line_item',
            ],
            $formView->vars['page_component_options']
        );
    }

    public function testSubmitSimpleProductWhenNoLineItem(): void
    {
        $form = $this->factory->create(OrderLineItemType::class);
        $shipBy = new \DateTime('today +1 day');
        $price = Price::create(456.7891, 'USD');
        $data = [
            'product' => $this->productSimple1->getId(),
            'productSku' => '',
            'freeFormProduct' => '',
            'quantity' => 123.4567,
            'productUnit' => $this->productUnitItem->getCode(),
            'price' => $price->jsonSerialize(),
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_BUNDLED,
            'shipBy' => $shipBy->format('Y-m-d'),
            'comment' => 'Sample comment',
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            (new OrderLineItem())
                ->setProduct($this->productSimple1)
                ->setProductUnit($this->productUnitItem)
                ->setQuantity($data['quantity'])
                ->setComment($data['comment'])
                ->setShipBy($shipBy)
                ->setPrice($price)
                ->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_BUNDLED),
            $form->getData()
        );
    }

    public function testSubmitSimpleProductWhenHasLineItem(): void
    {
        $shipBy = new \DateTime('today +1 day');
        $price = Price::create(4567.8912, 'USD');
        $orderLineItem = (new OrderLineItem())
            ->setProduct($this->productSimple1)
            ->setProductUnit($this->productUnitItem)
            ->setQuantity(123)
            ->setComment('Sample comment')
            ->setShipBy($shipBy)
            ->setPrice($price)
            ->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_BUNDLED)
            ->setCurrency('USD');

        $form = $this->factory->create(
            OrderLineItemType::class,
            $orderLineItem,
            ['currency' => $orderLineItem->getCurrency()]
        );

        $updatedShipBy = (clone $shipBy)->modify('+ 1 day');
        $data = [
            'product' => $this->productSimple2->getId(),
            'productSku' => '',
            'freeFormProduct' => '',
            'quantity' => 1234.5678,
            'productUnit' => $this->productUnitKg->getCode(),
            'price' => $price->jsonSerialize(),
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_BUNDLED,
            'shipBy' => $updatedShipBy->format('Y-m-d'),
            'comment' => 'Updated sample comment',
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            (new OrderLineItem())
                ->setProduct($this->productSimple2)
                ->setProductUnit($this->productUnitKg)
                ->setQuantity($data['quantity'])
                ->setComment($data['comment'])
                ->setShipBy($updatedShipBy)
                ->setPrice($price)
                ->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_BUNDLED),
            $form->getData()
        );
    }

    public function testSubmitWhenFreeFormProduct(): void
    {
        $form = $this->factory->create(OrderLineItemType::class);
        $shipBy = new \DateTime('today +1 day');
        $price = Price::create(456.7891, 'USD');
        $data = [
            'product' => '',
            'productSku' => 'SKU1',
            'freeFormProduct' => 'Sample Product',
            'quantity' => 123.4567,
            'productUnit' => $this->productUnitItem->getCode(),
            'price' => $price->jsonSerialize(),
            'priceType' => PriceTypeAwareInterface::PRICE_TYPE_BUNDLED,
            'shipBy' => $shipBy->format('Y-m-d'),
            'comment' => 'Sample comment',
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            (new OrderLineItem())
                ->setProductSku($data['productSku'])
                ->setFreeFormProduct($data['freeFormProduct'])
                ->setProductUnit($this->productUnitItem)
                ->setQuantity($data['quantity'])
                ->setComment($data['comment'])
                ->setShipBy($shipBy)
                ->setPrice($price)
                ->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_BUNDLED),
            $form->getData()
        );
    }

    public function testSubmitProductKitWhenNoLineItem(): void
    {
        $form = $this->factory->create(OrderLineItemType::class, null);

        $shipBy = new \DateTime('today +1 day');
        $price = Price::create(456.7891, 'USD');
        $data = [
            'product' => $this->productKit1->getId(),
            'productSku' => '',
            'freeFormProduct' => '',
            'quantity' => 123.4567,
            'productUnit' => $this->productUnitItem->getCode(),
            'price' => $price->jsonSerialize(),
            'priceType' => (string)PriceTypeAwareInterface::PRICE_TYPE_BUNDLED,
            'shipBy' => $shipBy->format('Y-m-d'),
            'comment' => 'Sample comment',
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            (new OrderLineItem())
                ->setProduct($this->productKit1)
                ->setProductUnit($this->productUnitItem)
                ->setQuantity($data['quantity'])
                ->setComment($data['comment'])
                ->setShipBy($shipBy)
                ->setPrice($price)
                ->setPriceType($data['priceType'])
                ->addKitItemLineItem(
                    (new OrderProductKitItemLineItem())
                        ->setKitItem($this->kitItem1)
                        ->setProduct($this->kitItemProduct1),
                )
                ->setChecksum('200|item|123.4567'),
            $form->getData()
        );
    }

    public function testSubmitProductKitWhenHasLineItem(): void
    {
        $shipBy = new \DateTime('today +1 day');
        $price = Price::create(4567.8912, 'USD');

        $orderLineItem = (new OrderLineItem())
            ->setProduct($this->productKit1)
            ->setProductUnit($this->productUnitItem)
            ->setQuantity(123.4567)
            ->setComment('Sample comment')
            ->setShipBy($shipBy)
            ->setPrice($price)
            ->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_BUNDLED)
            ->addKitItemLineItem(
                (new OrderProductKitItemLineItem())->setKitItem($this->kitItem1)->setProduct($this->kitItemProduct1)
            )
            ->setCurrency('USD');

        $form = $this->factory->create(
            OrderLineItemType::class,
            $orderLineItem,
            ['currency' => $orderLineItem->getCurrency()]
        );

        $updatedShipBy = (clone $shipBy)->modify('+ 1 day');
        $data = [
            'product' => $this->productKit2->getId(),
            'productSku' => '',
            'freeFormProduct' => '',
            'quantity' => 1234.5678,
            'productUnit' => $this->productUnitKg->getCode(),
            'price' => $price->jsonSerialize(),
            'priceType' => (string)PriceTypeAwareInterface::PRICE_TYPE_BUNDLED,
            'shipBy' => $updatedShipBy->format('Y-m-d'),
            'comment' => 'Updated sample comment',
            'kitItemLineItems' => [
                $this->kitItem3->getId() => [
                    'product' => $this->kitItemProduct3->getId(),
                    'quantity' => 1.2345,
                ],
                $this->kitItem4->getId() => [
                    'product' => $this->kitItemProduct2->getId(),
                    'quantity' => 2.3456,
                ],
            ],
        ];
        $form->submit($data);

        $this->assertFormIsValid($form);
        self::assertTrue($form->isSynchronized());
        self::assertEquals(
            (new OrderLineItem())
                ->setProduct($this->productKit2)
                ->setProductUnit($this->productUnitKg)
                ->setQuantity($data['quantity'])
                ->setComment($data['comment'])
                ->setShipBy($updatedShipBy)
                ->setPrice($price)
                ->setPriceType($data['priceType'])
                ->addKitItemLineItem(
                    (new OrderProductKitItemLineItem())
                        ->setKitItem($this->kitItem3)
                        ->setProduct($this->kitItemProduct3)
                        ->setQuantity($data['kitItemLineItems'][$this->kitItem3->getId()]['quantity'])
                )
                ->addKitItemLineItem(
                    (new OrderProductKitItemLineItem())
                        ->setKitItem($this->kitItem4)
                        ->setProduct($this->kitItemProduct2)
                        ->setQuantity($data['kitItemLineItems'][$this->kitItem4->getId()]['quantity'])
                )
                ->setChecksum('201|kg|1234.5678')
                ->setCurrency('USD'),
            $form->getData()
        );
    }
}
