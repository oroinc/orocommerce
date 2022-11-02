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
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductShippingOptionsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ProductShippingOptionsType */
    private $formType;

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
            $productShippingOptions->setProductUnit(
                $this->getEntity(
                    ProductUnit::class,
                    [
                        'code' => $unitCode,
                        'defaultPrecision' => 1,
                    ]
                )
            );
        }

        if ($weight) {
            $productShippingOptions->setWeight(
                $this->getEntity(
                    Weight::class,
                    [
                        'value' => $weight[0],
                        'unit' => $this->getEntity(
                            WeightUnit::class,
                            [
                                'code' => $weight[1]
                            ]
                        ),
                    ]
                )
            );
        }

        if ($dimensions) {
            $productShippingOptions->setDimensions(
                $this->getEntity(
                    Dimensions::class,
                    [
                        'value' => $this->getEntity(
                            DimensionsValue::class,
                            [
                                'length' => $dimensions[0],
                                'width' => $dimensions[1],
                                'height' => $dimensions[2]
                            ]
                        ),
                        'unit' => $this->getEntity(
                            LengthUnit::class,
                            [
                                'code' => $dimensions[3]
                            ]
                        ),
                    ]
                )
            );
        }

        if ($freightClass) {
            $productShippingOptions->setFreightClass(
                $this->getEntity(
                    FreightClass::class,
                    [
                        'code' => $freightClass,
                    ]
                )
            );
        }

        return $productShippingOptions;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $productUnitSelectionType = new EntityType(
            [
                'each' => $this->getEntity(
                    ProductUnit::class,
                    [
                        'code' => 'each',
                        'defaultPrecision' => 1,
                    ]
                ),
                'item' => $this->getEntity(
                    ProductUnit::class,
                    [
                        'code' => 'item',
                        'defaultPrecision' => 1
                    ]
                ),
            ],
            ProductUnitSelectionType::NAME
        );

        $weightType = new WeightType();
        $weightType->setDataClass(Weight::class);

        $dimensionsType = new DimensionsType();
        $dimensionsType->setDataClass(Dimensions::class);

        $dimensionsValueType = new DimensionsValueType();
        $dimensionsValueType->setDataClass(DimensionsValue::class);

        return [
            new PreloadedExtension(
                [
                    ProductShippingOptionsType::class => $this->formType,
                    WeightType::class => $weightType,
                    DimensionsType::class => $dimensionsType,
                    DimensionsValueType::class => $dimensionsValueType,
                    FreightClassSelectType::class => new EntityType(
                        [
                            'pl' => $this->getEntity(
                                FreightClass::class,
                                ['code' => 'pl']
                            )
                        ],
                        FreightClassSelectType::NAME
                    ),
                    WeightUnitSelectType::class => new EntityType(
                        [
                            'mg' => $this->getEntity(
                                WeightUnit::class,
                                ['code' => 'mg']
                            ),
                            'kg' => $this->getEntity(
                                WeightUnit::class,
                                ['code' => 'kg']
                            )
                        ],
                        WeightUnitSelectType::NAME
                    ),
                    LengthUnitSelectType::class => new EntityType(
                        [
                            'mm' => $this->getEntity(
                                LengthUnit::class,
                                ['code' => 'mm']
                            ),
                            'cm' => $this->getEntity(
                                LengthUnit::class,
                                ['code' => 'cm']
                            )
                        ],
                        LengthUnitSelectType::NAME
                    ),
                    ProductUnitSelectionType::class => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
