<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType as StubEntityType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType as OroCollectionType;

use OroB2B\Bundle\AttributeBundle\Form\Extension\IntegerExtension;
use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\ProductPrice;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectType;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceCollectionType;
use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceType;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionCollectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubCurrencySelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubPriceListSelectType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitSelectionType;

class ProductTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductType
     */
    protected $type;

    /**
     * @var RoundingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $roundingService;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ProductType($this->roundingService);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->type, $this->roundingService);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    CategoryTreeType::NAME => new StubEntityType(
                        [
                            1 => (new Category())
                                ->addTitle((new LocalizedFallbackValue())->setString('Test Category First')),
                            2 => (new Category())
                                ->addTitle((new LocalizedFallbackValue())->setString('Test Category Second')),
                            3 => (new Category())
                                ->addTitle((new LocalizedFallbackValue())->setString('Test Category Third')),
                        ],
                        CategoryTreeType::NAME
                    ),
                    OroCollectionType::NAME => new OroCollectionType(),
                    CurrencySelectionType::NAME => new StubCurrencySelectionType(
                        [
                            'USD' => 'USD'
                        ],
                        CurrencySelectionType::NAME
                    ),
                    PriceListSelectType::NAME => new StubPriceListSelectType(
                        [
                            1 => (new PriceList())->setName('Default price list'),
                        ],
                        PriceListSelectType::NAME
                    ),
                    PriceType::NAME => new PriceType(),
                    ProductUnitPrecisionType::NAME => new ProductUnitPrecisionType(),
                    ProductUnitPrecisionCollectionType::NAME => new ProductUnitPrecisionCollectionType(),
                    ProductUnitSelectionType::NAME => new StubProductUnitSelectionType(
                        [
                            'item' => (new ProductUnit())->setCode('item'),
                            'kg' => (new ProductUnit())->setCode('kg'),
                        ],
                        ProductUnitSelectionType::NAME
                    ),
                    ProductPriceCollectionType::NAME => new ProductPriceCollectionType(
                        'OroB2B\Bundle\PricingBundle\Entity\ProductPrice'
                    ),
                    ProductPriceType::NAME => new ProductPriceType(),
                ],
                [
                    'form' => [
                        new TooltipFormExtension(),
                        new IntegerExtension()
                    ]
                ]
            )
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param Product $defaultData
     * @param array $submittedData
     * @param Product $expectedData
     * @param boolean $rounding
     */
    public function testSubmit(Product $defaultData, $submittedData, Product $expectedData, $rounding = false)
    {
        if ($rounding) {
            $this->roundingService->expects($this->once())
                ->method('round')
                ->willReturnCallback(
                    function ($value, $precision) {
                        return round($value, $precision);
                    }
                );
        }

        $form = $this->factory->create($this->type, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $defaultProduct = new Product();

        return [
            'product without unitPrecisions and prices' => [
                'defaultData'   => $defaultProduct,
                'submittedData' => [
                    'sku' => 'test sku',
                    'category' => 2,
                ],
                'expectedData'  => $this->createExpectedProductEntity($defaultProduct),
                'rounding' => false,
            ],
            'product with unitPrecisions and without prices' => [
                'defaultData'   => $defaultProduct,
                'submittedData' => [
                    'sku' => 'test sku',
                    'category' => 2,
                    'unitPrecisions' => [
                        [
                            'unit' => 'kg',
                            'precision' => 3
                        ]
                    ],
                ],
                'expectedData'  => $this->createExpectedProductEntity($defaultProduct, true),
                'rounding' => false,
            ],
            'product with unitPrecisions and prices' => [
                'defaultData'   => $defaultProduct,
                'submittedData' => [
                    'sku' => 'test sku',
                    'category' => 2,
                    'unitPrecisions' => [
                        [
                            'unit' => 'kg',
                            'precision' => 3
                        ]
                    ],
                    'prices' => [
                        [
                            'priceList' => 1,
                            'price' => [
                                'value' => 0.9999,
                                'currency' => 'USD'
                            ],
                            'quantity' => 5.5555,
                            'unit' => 'kg'
                        ]
                    ],
                ],
                'expectedData'  => $this->createExpectedProductEntity($defaultProduct, true, true),
                'rounding' => true,
            ]
        ];
    }

    /**
     * @param Product $product
     * @param boolean $withProductUnitPrecision
     * @param boolean $withPrice
     * @return Product
     */
    protected function createExpectedProductEntity(
        Product $product,
        $withProductUnitPrecision = false,
        $withPrice = false
    ) {
        $expectedProduct = clone $product;

        $title = new LocalizedFallbackValue();
        $title->setString('Test Category Second');

        $category = new Category();
        $category->addTitle($title);

        $productUnit = new ProductUnit();
        $productUnit->setCode('kg');

        if ($withProductUnitPrecision) {
            $productUnitPrecision = new ProductUnitPrecision();
            $productUnitPrecision
                ->setProduct($expectedProduct)
                ->setUnit($productUnit)
                ->setPrecision(3);

            $expectedProduct->addUnitPrecision($productUnitPrecision);
        }

        if ($withPrice) {
            $priceList = (new PriceList())->setName('Default price list');

            $productPrice = new ProductPrice();
            $productPrice
                ->setProduct($expectedProduct)
                ->setUnit($productUnit)
                ->setPrice(Price::create(0.9999, 'USD'))
                ->setPriceList($priceList)
                ->setQuantity(5.556);

            $expectedProduct->addPrice($productPrice);
        }

        return $expectedProduct
            ->setSku('test sku')
            ->setCategory($category);
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

        $this->assertTrue($form->has('sku'));
        $this->assertTrue($form->has('category'));
        $this->assertTrue($form->has('unitPrecisions'));
        $this->assertTrue($form->has('prices'));
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_product', $this->type->getName());
    }
}
