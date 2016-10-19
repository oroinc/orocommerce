<?php

namespace Oro\Bundle\InvoiceBundle\Tests\Unit\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type\PriceTypeGenerator;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Rounding\PriceRoundingService;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use Oro\Bundle\InvoiceBundle\Form\Type\InvoiceLineItemType;
use Oro\Bundle\PricingBundle\Form\Type\PriceTypeSelectorType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductSelectEntityTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class InvoiceLineItemTypeTest extends FormIntegrationTestCase
{
    use QuantityTypeTrait, EntityTrait;

    const PRODUCT_UNIT_CLASS = 'Oro\Bundle\ProductBundle\Entity\ProductUnit';

    /**
     * @var InvoiceLineItemType
     */
    protected $formType;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductUnitLabelFormatter
     */
    protected $productUnitLabelFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->productUnitLabelFormatter = $this->getMockBuilder(
            'Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->any())
            ->method('findBy')
            ->will(
                $this->returnValue(
                    [
                        'item' => 'item',
                        'kg' => 'kilogram',
                    ]
                )
            );

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with(self::PRODUCT_UNIT_CLASS)
            ->will($this->returnValue($repository));

        /** @var PriceRoundingService $roundingService */
        $roundingService = $this->getMockBuilder('Oro\Bundle\PricingBundle\Rounding\PriceRoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new InvoiceLineItemType($this->registry, $this->productUnitLabelFormatter, $roundingService);
        $this->formType->setDataClass('Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem');
        $this->formType->setProductUnitClass(self::PRODUCT_UNIT_CLASS);
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

        $priceType = PriceTypeGenerator::createPriceType();

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
}
