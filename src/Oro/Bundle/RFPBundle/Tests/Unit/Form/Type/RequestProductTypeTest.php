<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Form\Type\QuantityType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecision;
use Oro\Bundle\ProductBundle\Validator\Constraints\QuantityUnitPrecisionValidator;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestProductTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /**
     * @var RequestProductType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->formType = new RequestProductType();
        $this->formType->setDataClass('Oro\Bundle\RFPBundle\Entity\RequestProduct');

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit\Framework\MockObject\MockObject|OptionsResolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $options) {
                $this->assertArrayHasKey('data_class', $options);
                $this->assertArrayHasKey('compact_units', $options);
                $this->assertArrayHasKey('csrf_token_id', $options);
                $this->assertArrayHasKey('page_component', $options);
                $this->assertArrayHasKey('page_component_options', $options);

                return true;
            }));
        ;

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider buildViewProvider
     */
    public function testBuildView(array $inputData, array $expectedData)
    {
        $view = new FormView();

        $view->vars = $inputData['vars'];

        /* @var $form FormInterface|\PHPUnit\Framework\MockObject\MockObject */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $this->formType->buildView($view, $form, $inputData['options']);

        $this->assertSame($expectedData, $view->vars);
    }

    /**
     * @dataProvider finishViewProvider
     */
    public function testFinishView(array $inputData, array $expectedData)
    {
        $view = new FormView();

        $view->vars = $inputData['vars'];

        /* @var $form FormInterface|\PHPUnit\Framework\MockObject\MockObject */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $this->formType->finishView($view, $form, $inputData['options']);

        $this->assertEquals($expectedData, $view->vars);
    }

    /**
     * @return array
     */
    public function buildViewProvider()
    {
        return [
            'test options' => [
                'input' => [
                    'vars' => [],
                    'options' => [
                        'page_component' => 'component',
                        'page_component_options' => 'options',
                    ],
                ],
                'expected' => [
                    'page_component' => 'component',
                    'page_component_options' => 'options',
                ],
            ],
        ];
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
                'input'     => [
                    'vars' => [
                        'value' => null,
                    ],
                    'options' => [
                        'compact_units' => false,
                    ],
                ],
                'expected'  => [
                    'value' => null,
                    'componentOptions' => [
                        'units' => [],
                        'compactUnits' => false,
                    ],
                ],
            ],
            'empty product' => [
                'input'     => [
                    'vars' => [
                        'value' => new RequestProduct(),
                    ],
                    'options' => [
                        'compact_units' => false,
                    ],
                ],
                'expected'  => [
                    'value' => new RequestProduct(),
                    'componentOptions' => [
                        'units' => [],
                        'compactUnits' => false,
                    ],
                ],
            ],
            'existing product' => [
                'input'     => [
                    'vars' => [
                        'value' => (new RequestProduct())
                        ->setProduct($this->createProduct(1, ['unit1' => 0, 'unit2' => 2]))
                    ],
                    'options' => [
                        'compact_units' => false,
                    ],
                ],
                'expected'  => [
                    'value' => (new RequestProduct())
                        ->setProduct($this->createProduct(1, ['unit1' => 0, 'unit2' => 2])),
                    'componentOptions' => [
                        'units' => [
                            1 => [
                                'unit1' => 0,
                                'unit2' => 2,
                            ],
                        ],
                        'compactUnits' => false,
                    ],
                ],
            ],
            'existing product and compact units' => [
                'input'     => [
                    'vars' => [
                        'value' => (new RequestProduct())
                        ->setProduct($this->createProduct(2, ['unit3' => 3, 'unit4' => 0]))
                    ],
                    'options' => [
                        'compact_units' => true,
                    ],
                ],
                'expected'  => [
                    'value' => (new RequestProduct())
                        ->setProduct($this->createProduct(2, ['unit3' => 3, 'unit4' => 0])),
                    'componentOptions' => [
                        'units' => [
                            2 => [
                                'unit3' => 3,
                                'unit4' => 0,
                            ],
                        ],
                        'compactUnits' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param int $id
     * @param array $units
     * @return Product|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createProduct($id, array $units = [])
    {
        $product = $this->getMockEntity(
            'Oro\Bundle\ProductBundle\Entity\Product',
            [
                'getId' => $id,
                'getAvailableUnitsPrecision' => $units,
            ]
        );

        return $product;
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $requestProductItem = $this->getRequestProductItem(2, 10, 'kg', $this->createPrice(20, 'USD'));

        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequestProduct(),
                'defaultData'   => $this->getRequestProduct(),
            ],
            'invalid product and empty items' => [
                'isValid'       => false,
                'submittedData' => [
                    'product' => 333,
                ],
                'expectedData'  => $this->getRequestProduct(),
                'defaultData'   => $this->getRequestProduct(),
            ],
            'empty items' => [
                'isValid'       => false,
                'submittedData' => [
                    'product' => 1,
                ],
                'expectedData'  => $this->getRequestProduct(1),
                'defaultData'   => $this->getRequestProduct(1),
            ],
            'empty request' => [
                'isValid'       => false,
                'submittedData' => [
                    'product'   => 2,
                    'comment'   => 'comment',
                    'requestProductItems' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData'  => $this->getRequestProduct(2, 'comment_stripped', [$requestProductItem])->setRequest(),
                'defaultData'   => $this->getRequestProduct(2, 'comment', [$requestProductItem])->setRequest(),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'product'   => 2,
                    'comment'   => 'comment',
                    'requestProductItems' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData'  => $this->getRequestProduct(2, 'comment_stripped', [$requestProductItem]),
                'defaultData'   => $this->getRequestProduct(2, 'comment', [$requestProductItem]),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $productSelectType          = $this->prepareProductSelectType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $requestProductItemType     = $this->prepareRequestProductItemType();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    PriceType::class                => $priceType,
                    ProductSelectType::class        => $productSelectType,
                    RequestProductItemType::class   => $requestProductItemType,
                    CurrencySelectionType::class    => $currencySelectionType,
                    ProductUnitSelectionType::class => $productUnitSelectionType,
                    QuantityType::class             => $this->getQuantityType()
                ],
                [FormType::class => [new StripTagsExtensionStub($this)]]
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @return array
     */
    protected function getValidators()
    {
        $quantityUnitPrecision = new QuantityUnitPrecision();
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($quantity) {
                return (float)$quantity;
            });
        $quantityUnitPrecisionValidator = new QuantityUnitPrecisionValidator($roundingService);

        return [
            $quantityUnitPrecision->validatedBy() => $quantityUnitPrecisionValidator,
        ];
    }
}
