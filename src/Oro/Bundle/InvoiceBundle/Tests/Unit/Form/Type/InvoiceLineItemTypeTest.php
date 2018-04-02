<?php

namespace Oro\Bundle\InvoiceBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use Oro\Bundle\InvoiceBundle\Form\Type\InvoiceLineItemType;
use Oro\Bundle\PricingBundle\Form\Type\PriceTypeSelectorType;
use Oro\Bundle\PricingBundle\Rounding\PriceRoundingService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectEntityTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;

class InvoiceLineItemTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait, EntityTrait;

    /**
     * @var InvoiceLineItemType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitsProvider
     */
    protected $productUnitsProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /** @var PriceRoundingService $roundingService */
        $roundingService = $this->getMockBuilder('Oro\Bundle\PricingBundle\Rounding\PriceRoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $this->productUnitsProvider->expects($this->any())
            ->method('getAvailableProductUnitsWithPrecision')
            ->willReturn([
                'item' => 0,
                'kg' => 3,
            ]);

        $this->formType = new InvoiceLineItemType($roundingService, $this->productUnitsProvider);
        $this->formType->setDataClass('Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem');
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $productSelectType = new ProductSelectEntityTypeStub(
            [
                1 => $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 1]),
                2 => $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 2]),
            ]
        );

        $unitSelectType = new EntityType(
            [
                'kg' => $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'kg']),
                'item' => $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'item']),
            ],
            ProductUnitSelectionType::NAME
        );

        $priceType = PriceTypeGenerator::createPriceType($this);

        $orderPriceType = new PriceTypeSelectorType();
        $dateType = new OroDateType();

        return [
            new PreloadedExtension(
                [
                    $productSelectType->getName() => $productSelectType,
                    $unitSelectType->getName() => $unitSelectType,
                    $priceType->getName() => $priceType,
                    $orderPriceType->getName() => $orderPriceType,
                    $dateType->getName() => $dateType,
                    QuantityTypeTrait::$name => $this->getQuantityType(),
                ],
                []
            ),
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $options
     * @param array $submittedData
     * @param InvoiceLineItem $expectedData
     * @param InvoiceLineItem|null $data
     */
    public function testSubmit(
        array $options,
        array $submittedData,
        InvoiceLineItem $expectedData,
        InvoiceLineItem $data = null
    ) {
        if (!$data) {
            $data = new InvoiceLineItem();
        }
        $form = $this->factory->create($this->formType, $data, $options);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $data = $form->getData();
        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        /** @var Product $product */
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 1]);

        return [
            'default' => [
                'options' => [
                    'currency' => 'USD',
                ],
                'submittedData' => [
                    'productSku' => '',
                    'product' => '1',
                    'freeFormProduct' => '',
                    'quantity' => '10',
                    'productUnit' => 'item',
                    'price' => [
                        'value' => '5',
                        'currency' => 'USD',
                    ],
                    'priceType' => InvoiceLineItem::PRICE_TYPE_BUNDLED,
                ],
                'expectedData' => (new InvoiceLineItem())
                    ->setProduct($product)
                    ->setQuantity(10)
                    ->setProductUnit(
                        $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'item'])
                    )
                    ->setPrice(Price::create(5, 'USD'))
                    ->setPriceType(InvoiceLineItem::PRICE_TYPE_BUNDLED)
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
                    'priceType' => InvoiceLineItem::PRICE_TYPE_UNIT,
                ],
                'expectedData' => (new InvoiceLineItem())
                    ->setQuantity(1)
                    ->setFreeFormProduct('Service')
                    ->setProductSku('SKU02')
                    ->setProductUnit(
                        $this->getEntity('Oro\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'item'])
                    )
                    ->setPrice(Price::create(5, 'USD'))
                    ->setPriceType(InvoiceLineItem::PRICE_TYPE_UNIT)
            ],
        ];
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider finishViewProvider
     */
    public function testFinishView(array $inputData, array $expectedData)
    {
        $view = new FormView();

        $view->vars = $inputData['vars'];

        /* @var $form FormInterface|\PHPUnit_Framework_MockObject_MockObject */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $this->formType->finishView($view, $form, []);

        $this->assertEquals($expectedData, $view->vars);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function finishViewProvider()
    {
        return [
            'empty request product' => [
                'input' => [
                    'vars' => [
                        'value' => null,
                    ],
                ],
                'expected' => [
                    'value' => null,
                ],
            ],
            'empty product' => [
                'input' => [
                    'vars' => [
                        'value' => new InvoiceLineItem(),
                    ],
                ],
                'expected' => [
                    'value' => new InvoiceLineItem(),
                ],
            ],
            'existing product' => [
                'input' => [
                    'vars' => [
                        'value' => (new InvoiceLineItem())
                            ->setProduct($this->createProduct([
                                'kg' => 3,
                                'each' => 0,
                                'item' => 1,
                            ]))
                    ],
                ],
                'expected' => [
                    'value' => (new InvoiceLineItem())
                        ->setProduct($this->createProduct([
                            'kg' => 3,
                            'each' => 0,
                            'item' => 1,
                        ])),
                    'page_component_options' => [
                        'modelAttr' => [
                            'product_units' => [
                                'kg' => 3,
                                'each' => 0,
                                'item' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $units
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProduct(array $units = [])
    {
        $product = $this->createMock(Product::class);
        $product->expects($this->once())
            ->method('getAvailableUnitsPrecision')
            ->willReturn($units);

        return $product;
    }
}
