<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectEntityTypeStub;
use Symfony\Component\Form\PreloadedExtension;

class OrderLineItemTypeTest extends AbstractOrderLineItemTypeTest
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitsProvider
     */
    protected $productUnitsProvider;

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $productSelectType = new ProductSelectEntityTypeStub(
            [
                1 => $this->getEntity(Product::class, ['id' => 1]),
                2 => $this->getEntity(Product::class, ['id' => 2]),
            ]
        );

        return array_merge(
            parent::getExtensions(),
            [new PreloadedExtension([$productSelectType->getName() => $productSelectType], [])]
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $this->productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $this->productUnitsProvider->expects($this->any())
            ->method('getAvailableProductUnitsWithPrecision')
            ->willReturn([
                'item' => 0,
                'kg' => 3,
            ]);

        $this->formType = $this->getFormType();
        $this->formType->setDataClass(OrderLineItem::class);
        $this->formType->setSectionProvider($this->sectionProvider);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType()
    {
        return new OrderLineItemType($this->productUnitsProvider);
    }

    public function testGetName()
    {
        $this->assertEquals(OrderLineItemType::NAME, $this->formType->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function submitDataProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', '2015-02-03 00:00:00', new \DateTimeZone('UTC'));

        return [
            'default' => [
                'options' => [
                    'currency' => 'USD',
                ],
                'submittedData' => [
                    'product' => 1,
                    'productSku' => '',
                    'freeFormProduct' => '',
                    'quantity' => 10,
                    'productUnit' => 'item',
                    'productUnitCode' => '',
                    'price' => [
                        'value' => '5',
                        'currency' => 'USD',
                    ],
                    'priceType' => OrderLineItem::PRICE_TYPE_BUNDLED,
                    'shipBy' => '2015-02-03',
                    'comment' => 'Comment',
                ],
                'expectedData' => (new OrderLineItem())
                    ->setProduct($product)
                    ->setQuantity(10)
                    ->setProductUnit(
                        $this->getEntity(ProductUnit::class, ['code' => 'item'])
                    )
                    ->setPrice(Price::create(5, 'USD'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_BUNDLED)
                    ->setShipBy($date)
                    ->setComment('Comment'),
            ],
            'free form entry' => [
                'options' => [
                    'currency' => 'USD',
                ],
                'submittedData' => [
                    'product' => null,
                    'productSku' => 'SKU02',
                    'freeFormProduct' => 'Service',
                    'quantity' => 1,
                    'productUnit' => 'item',
                    'price' => [
                        'value' => 5,
                        'currency' => 'USD',
                    ],
                    'priceType' => OrderLineItem::PRICE_TYPE_UNIT,
                    'shipBy' => '2015-02-03',
                    'comment' => 'Comment',
                ],
                'expectedData' => (new OrderLineItem())
                    ->setQuantity(1)
                    ->setFreeFormProduct('Service')
                    ->setProductSku('SKU02')
                    ->setProductUnit(
                        $this->getEntity(ProductUnit::class, ['code' => 'item'])
                    )
                    ->setPrice(Price::create(5, 'USD'))
                    ->setPriceType(OrderLineItem::PRICE_TYPE_UNIT)
                    ->setShipBy($date)
                    ->setComment('Comment'),
            ],
        ];
    }

    public function testBuildView()
    {
        $this->sectionProvider->expects($this->atLeastOnce())->method('addSections')
            ->with($this->formType->getName(), $this->isType('array'))
            ->willReturn($this->getExpectedSections());

        $this->assertDefaultBuildViewCalled();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedSections()
    {
        return new ArrayCollection(
            [
                'quantity' => ['data' => ['quantity' => [], 'productUnit' => []], 'order' => 10],
                'price' => ['data' => ['price' => [], 'priceType' => []], 'order' => 20],
                'ship_by' => ['data' => ['shipBy' => []], 'order' => 30],
                'comment' => [
                    'data' => [
                        'comment' => ['page_component' => 'oroorder/js/app/components/notes-component'],
                    ],
                    'order' => 40,
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExpectedOptions()
    {
        return [
            'currency' => null,
            'data_class' => OrderLineItem::class,
            'intention' => 'order_line_item',
            'page_component' => 'oroui/js/app/components/view-component',
            'page_component_options' => [
                'view' => 'oroorder/js/app/views/line-item-view',
                'freeFormUnits' => [
                    'item' => 0,
                    'kg' => 3,
                ],
            ],
        ];
    }
}
