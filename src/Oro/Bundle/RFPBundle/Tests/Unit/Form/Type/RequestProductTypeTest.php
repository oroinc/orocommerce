<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\StripTagsExtensionStub;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
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

    /** @var RequestProductType */
    protected $formType;

    protected function setUp(): void
    {
        $this->formType = new RequestProductType();
        $this->formType->setDataClass(RequestProduct::class);

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
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

        $this->formType->configureOptions($resolver);
    }

    /**
     * @dataProvider buildViewProvider
     */
    public function testBuildView(array $inputData, array $expectedData)
    {
        $view = new FormView();

        $view->vars = $inputData['vars'];

        $form = $this->createMock(FormInterface::class);

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

        $form = $this->createMock(FormInterface::class);

        $this->formType->finishView($view, $form, $inputData['options']);

        $this->assertEquals($expectedData, $view->vars);
    }

    public function buildViewProvider(): array
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function finishViewProvider(): array
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

    private function createProduct(int $id, array $units = []): Product
    {
        $product = $this->createMock(Product::class);
        $product->expects(self::any())
            ->method('getId')
            ->willReturn($id);
        $product->expects(self::any())
            ->method('getAvailableUnitsPrecision')
            ->willReturn($units);

        return $product;
    }

    /**
     * {@inheritDoc}
     */
    public function submitProvider(): array
    {
        $requestProductItem = $this->getRequestProductItem(2, 10, 'kg', Price::create(20, 'USD'));

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
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    $this->preparePriceType(),
                    ProductSelectType::class => $this->prepareProductSelectType(),
                    RequestProductItemType::class => $this->prepareRequestProductItemType(),
                    CurrencySelectionType::class => new CurrencySelectionTypeStub(),
                    ProductUnitSelectionType::class => $this->prepareProductUnitSelectionType(),
                    $this->getQuantityType()
                ],
                [FormType::class => [new StripTagsExtensionStub($this)]]
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getValidators(): array
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($quantity) {
                return (float)$quantity;
            });

        return [
            'oro_product_quantity_unit_precision' => new QuantityUnitPrecisionValidator($roundingService),
        ];
    }
}
