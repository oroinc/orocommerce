<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType as OroCollectionType;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as StubEntityIdentifierType;

use OroB2B\Bundle\AttributeBundle\Form\Extension\IntegerExtension;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\FallbackBundle\Form\Type\LocalizedFallbackValueCollectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubLocalizedFallbackValueCollectionType;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductCustomFieldsChoiceType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionCollectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitPrecisionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductCustomFieldsChoiceType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubEnumSelectType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubImageType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct;

class ProductTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\ProductBundle\Entity\Product';

    /**
     * @var ProductType
     */
    protected $type;

    /**
     * @var RoundingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $roundingService;

    /**
     * @var array
     */
    protected $exampleCustomFields = [
        'size'  => 'Size Label',
        'color' => 'Color Label'
    ];

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->roundingService = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Rounding\RoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ProductType($this->roundingService);
        $this->type->setDataClass(self::DATA_CLASS);

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
        $productUnitPrecision = new ProductUnitPrecisionType();
        $productUnitPrecision->setDataClass('OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision');

        $stubEnumSelectType = new StubEnumSelectType();

        return [
            new PreloadedExtension(
                [
                    $stubEnumSelectType->getName() => $stubEnumSelectType,
                    ImageType::NAME => new StubImageType(),
                    OroCollectionType::NAME => new OroCollectionType(),
                    ProductUnitPrecisionType::NAME => $productUnitPrecision,
                    ProductUnitPrecisionCollectionType::NAME => new ProductUnitPrecisionCollectionType(),
                    ProductUnitSelectionType::NAME => new StubProductUnitSelectionType(
                        [
                            'item' => (new ProductUnit())->setCode('item'),
                            'kg' => (new ProductUnit())->setCode('kg')
                        ],
                        ProductUnitSelectionType::NAME
                    ),
                    LocalizedFallbackValueCollectionType::NAME => new StubLocalizedFallbackValueCollectionType(),
                    ProductCustomFieldsChoiceType::NAME => new StubProductCustomFieldsChoiceType($this->exampleCustomFields),
                    EntityIdentifierType::NAME => new StubEntityIdentifierType([])
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

        /** @var Product $data */
        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $defaultProduct = new StubProduct();

        return [
            'simple product' => [
                'defaultData'   => $defaultProduct,
                'submittedData' => [
                    'sku' => 'test sku',
                    'unitPrecisions' => [],
                    'inventoryStatus' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'variants' => true,
                    'variantFields' => array_keys($this->exampleCustomFields)
                ],
                'expectedData'  => $this->createExpectedProductEntity(),
                'rounding' => false
            ],
            'product with unitPrecisions' => [
                'defaultData'   => $defaultProduct,
                'submittedData' => [
                    'sku' => 'test sku',
                    'unitPrecisions' => [
                        [
                            'unit' => 'kg',
                            'precision' => 3
                        ]
                    ],
                    'inventoryStatus' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'variants' => true,
                    'variantFields' => array_keys($this->exampleCustomFields)
                ],
                'expectedData'  => $this->createExpectedProductEntity(true),
                'rounding' => false
            ],
            'product with names and descriptions' => [
                'defaultData'   => $defaultProduct,
                'submittedData' => [
                    'sku' => 'test sku',
                    'unitPrecisions' => [],
                    'inventoryStatus' => Product::INVENTORY_STATUS_IN_STOCK,
                    'visible' => 1,
                    'status' => Product::STATUS_DISABLED,
                    'names' => [
                        ['string' => 'first name'],
                        ['string' => 'second name'],
                    ],
                    'descriptions' => [
                        ['text' => 'first description'],
                        ['text' => 'second description'],
                    ],
                    'variants' => true,
                    'variantFields' => array_keys($this->exampleCustomFields)
                ],
                'expectedData'  => $this->createExpectedProductEntity(false, true),
                'rounding' => false
            ],
        ];
    }

    /**
     * @param boolean $withProductUnitPrecision
     * @param boolean $withNamesAndDescriptions
     * @return Product
     */
    protected function createExpectedProductEntity(
        $withProductUnitPrecision = false,
        $withNamesAndDescriptions = false
    ) {
        $expectedProduct = new StubProduct();
        $expectedProduct->setVariants(true);
        $expectedProduct->setVariantFields(array_keys($this->exampleCustomFields));

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

        if ($withNamesAndDescriptions) {
            $expectedProduct
                ->addName($this->createLocalizedValue('first name'))
                ->addName($this->createLocalizedValue('second name'))
                ->addDescription($this->createLocalizedValue(null, 'first description'))
                ->addDescription($this->createLocalizedValue(null, 'second description'));
        }

        return $expectedProduct->setSku('test sku');
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

        $this->assertTrue($form->has('sku'));
        $this->assertTrue($form->has('unitPrecisions'));
        $this->assertTrue($form->has('appendVariants'));
        $this->assertTrue($form->has('removeVariants'));
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_product', $this->type->getName());
    }

    /**
     * @param string|null $string
     * @param string|null $text
     * @return LocalizedFallbackValue
     */
    protected function createLocalizedValue($string = null, $text = null)
    {
        $value = new LocalizedFallbackValue();
        $value->setString($string)
            ->setText($text);

        return $value;
    }
}
