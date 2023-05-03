<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ShippingBundle\Entity\FreightClass;
use Oro\Bundle\ShippingBundle\Entity\LengthUnit;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Entity\WeightUnit;
use Oro\Bundle\ShippingBundle\Form\Type\DimensionsType;
use Oro\Bundle\ShippingBundle\Form\Type\DimensionsValueType;
use Oro\Bundle\ShippingBundle\Form\Type\FreightClassSelectType;
use Oro\Bundle\ShippingBundle\Form\Type\LengthUnitSelectType;
use Oro\Bundle\ShippingBundle\Form\Type\ProductShippingOptionsType;
use Oro\Bundle\ShippingBundle\Form\Type\WeightType;
use Oro\Bundle\ShippingBundle\Form\Type\WeightUnitSelectType;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\DimensionsValue;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Bundle\ShippingBundle\Validator\Constraints\UniqueProductUnitShippingOptionsValidator;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityTypeStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductShippingOptionsTypeTest extends FormIntegrationTestCase
{
    private ProductShippingOptionsType $formType;

    protected function setUp(): void
    {
        $this->formType = new ProductShippingOptionsType();
        $this->formType->setDataClass(ProductShippingOptions::class);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getValidators(): array
    {
        return [
            UniqueProductUnitShippingOptionsValidator::class => new UniqueProductUnitShippingOptionsValidator(),
            'doctrine.orm.validator.unique' => $this->createMock(UniqueEntityValidator::class)
        ];
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['product' => null, 'data_class' => ProductShippingOptions::class]);

        $this->formType->configureOptions($resolver);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ProductShippingOptionsType::NAME, $this->formType->getBlockPrefix());
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        bool $isValid,
        array $submittedData,
        ProductShippingOptions $expectedData,
        ProductShippingOptions $defaultData = null,
        array $options = []
    ) {
        $form = $this->factory->create(ProductShippingOptionsType::class, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        $this->assertEquals($isValid, $form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitProvider(): array
    {
        return [
            'empty form' => [
                'isValid' => false,
                'submittedData' => [],
                'expectedData' => $this->getProductShippingOptions(),
                'defaultData' => $this->getProductShippingOptions(),
            ],
            'empty product' => [
                'isValid' => false,
                'submittedData' => [
                    'productUnit' => 'item',
                    'weight' => [
                        'value' => 1,
                        'unit' => 'kg',
                    ],
                    'dimensions' => [
                        'value' => [
                            'length' => 2,
                            'width' => 3,
                            'height' => 4
                        ],
                        'unit' => 'mm',
                    ],
                    'freightClass' => 'pl',
                ],
                'expectedData' => $this->getProductShippingOptions('item', [1, 'kg'], [2, 3, 4, 'mm'], 'pl')
                    ->setProduct(null),
                'defaultData' => $this->getProductShippingOptions()
                    ->setProduct(null),
            ],
            'empty unit' => [
                'isValid' => false,
                'submittedData' => [
                    'weight' => [
                        'value' => 1,
                        'unit' => 'kg',
                    ],
                    'dimensions' => [
                        'value' => [
                            'length' => 2,
                            'width' => 3,
                            'height' => 4
                        ],
                        'unit' => 'mm',
                    ],
                    'freightClass' => 'pl',
                ],
                'expectedData' => $this->getProductShippingOptions(null, [1, 'kg'], [2, 3, 4, 'mm'], 'pl'),
                'defaultData' => $this->getProductShippingOptions(),
            ],
            'empty weight' => [
                'isValid' => true,
                'submittedData' => [
                    'productUnit' => 'item',
                    'dimensions' => [
                        'value' => [
                            'length' => 2,
                            'width' => 3,
                            'height' => 4
                        ],
                        'unit' => 'mm',
                    ],
                    'freightClass' => 'pl',
                ],
                'expectedData' => $this->getProductShippingOptions('item', null, [2, 3, 4, 'mm'], 'pl'),
                'defaultData' => $this->getProductShippingOptions(),
            ],
            'empty dimensions' => [
                'isValid' => true,
                'submittedData' => [
                    'productUnit' => 'item',
                    'weight' => [
                        'value' => 1,
                        'unit' => 'kg',
                    ],
                    'freightClass' => 'pl',
                ],
                'expectedData' => $this->getProductShippingOptions('item', [1, 'kg'], null, 'pl'),
                'defaultData' => $this->getProductShippingOptions(),
            ],
            'empty freightClass' => [
                'isValid' => true,
                'submittedData' => [
                    'productUnit' => 'item',
                    'weight' => [
                        'value' => 1,
                        'unit' => 'kg',
                    ],
                    'dimensions' => [
                        'value' => [
                            'length' => 2,
                            'width' => 3,
                            'height' => 4
                        ],
                        'unit' => 'mm',
                    ],
                ],
                'expectedData' => $this->getProductShippingOptions('item', [1, 'kg'], [2, 3, 4, 'mm'], null),
                'defaultData' => $this->getProductShippingOptions(),
            ],
            'valid form' => [
                'isValid' => true,
                'submittedData' => [
                    'productUnit' => 'item',
                    'weight' => [
                        'value' => 1,
                        'unit' => 'kg',
                    ],
                    'dimensions' => [
                        'value' => [
                            'length' => 2,
                            'width' => 3,
                            'height' => 4
                        ],
                        'unit' => 'mm',
                    ],
                    'freightClass' => 'pl',
                ],
                'expectedData' => $this->getProductShippingOptions('item', [1, 'kg'], [2, 3, 4, 'mm'], 'pl'),
                'defaultData' => $this->getProductShippingOptions(),
            ],
        ];
    }

    private function getProductShippingOptions(
        string $unitCode = null,
        array $weight = null,
        array $dimensions = null,
        string $freightClass = null
    ): ProductShippingOptions {
        $productShippingOptions = new ProductShippingOptions();
        $productShippingOptions->setProduct(new Product());

        if ($unitCode) {
            $productShippingOptions->setProductUnit($this->getProductUnit($unitCode, 1));
        }

        if ($weight) {
            $productShippingOptions->setWeight($this->getWeight($weight[0], $this->getWeightUnit($weight[1])));
        }

        if ($dimensions) {
            $productShippingOptions->setDimensions(
                $this->getDimensions(
                    $this->getDimensionsValue($dimensions[0], $dimensions[1], $dimensions[2]),
                    $this->getLengthUnit($dimensions[3])
                )
            );
        }

        if ($freightClass) {
            $productShippingOptions->setFreightClass($this->getFreightClass($freightClass));
        }

        return $productShippingOptions;
    }

    private function getProductUnit(string $code, int $defaultPrecision): ProductUnit
    {
        $unit = new ProductUnit();
        $unit->setCode($code);
        $unit->setDefaultPrecision($defaultPrecision);

        return $unit;
    }

    private function getWeightUnit(string $code): WeightUnit
    {
        $unit = new WeightUnit();
        $unit->setCode($code);

        return $unit;
    }

    private function getLengthUnit(string $code): LengthUnit
    {
        $unit = new LengthUnit();
        $unit->setCode($code);

        return $unit;
    }

    private function getFreightClass(string $code): FreightClass
    {
        $freightClass = new FreightClass();
        $freightClass->setCode($code);

        return $freightClass;
    }

    private function getWeight(float $value, WeightUnit $unit): Weight
    {
        $weight = new Weight();
        $weight->setValue($value);
        $weight->setUnit($unit);

        return $weight;
    }

    private function getDimensions(DimensionsValue $value, LengthUnit $unit): Dimensions
    {
        $dimensions = new Dimensions();
        $dimensions->setValue($value);
        $dimensions->setUnit($unit);

        return $dimensions;
    }

    private function getDimensionsValue(float $length, float $width, float $height): DimensionsValue
    {
        $value = new DimensionsValue();
        $value->setLength($length);
        $value->setWidth($width);
        $value->setHeight($height);

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $weightType = new WeightType();
        $weightType->setDataClass(Weight::class);

        $dimensionsType = new DimensionsType();
        $dimensionsType->setDataClass(Dimensions::class);

        $dimensionsValueType = new DimensionsValueType();
        $dimensionsValueType->setDataClass(DimensionsValue::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    $weightType,
                    $dimensionsType,
                    $dimensionsValueType,
                    FreightClassSelectType::class => new EntityTypeStub([
                        'pl' => $this->getFreightClass('pl')
                    ]),
                    WeightUnitSelectType::class => new EntityTypeStub([
                        'mg' => $this->getWeightUnit('mg'),
                        'kg' => $this->getWeightUnit('kg')
                    ]),
                    LengthUnitSelectType::class => new EntityTypeStub([
                        'mm' => $this->getLengthUnit('mm'),
                        'cm' => $this->getLengthUnit('cm')
                    ]),
                    ProductUnitSelectionType::class => new EntityTypeStub([
                        'each' => $this->getProductUnit('each', 1),
                        'item' => $this->getProductUnit('item', 1),
                    ]),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
