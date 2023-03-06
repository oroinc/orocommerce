<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Form\Type\OrderLineItemType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectEntityTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\FormTypeInterface;

class OrderLineItemTypeTest extends AbstractOrderLineItemTypeTest
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductUnitsProvider */
    private $productUnitsProvider;

    protected function setUp(): void
    {
        $this->productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $this->productUnitsProvider->expects($this->any())
            ->method('getAvailableProductUnitsWithPrecision')
            ->willReturn([
                'item' => 0,
                'kg' => 3,
            ]);

        $this->formType = $this->getFormType();
        parent::setUp();
        $this->formType->setDataClass(OrderLineItem::class);
        $this->formType->setSectionProvider($this->sectionProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), [
            new PreloadedExtension(
                [
                    $this->formType,
                    ProductSelectType::class => new ProductSelectEntityTypeStub([
                        1 => $this->getEntity(Product::class, ['id' => 1]),
                        2 => $this->getEntity(Product::class, ['id' => 2]),
                    ])
                ],
                []
            )]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormType(): FormTypeInterface
    {
        return new OrderLineItemType($this->productUnitsProvider);
    }

    /**
     * {@inheritdoc}
     */
    public function submitDataProvider(): array
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
            ->with(OrderLineItemType::class, $this->isType('array'))
            ->willReturn($this->getExpectedSections());

        $this->assertDefaultBuildViewCalled();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExpectedSections(): ArrayCollection
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
    public function getExpectedOptions(): array
    {
        return [
            'currency' => null,
            'data_class' => OrderLineItem::class,
            'csrf_token_id' => 'order_line_item',
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
