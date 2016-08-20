<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;

use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemCollectionType;

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
    protected function setUp()
    {
        /* @var $productUnitLabelFormatter ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
        $productUnitLabelFormatter = $this->getMockBuilder(
            'Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $productUnitLabelFormatter->expects($this->any())
            ->method('format')
            ->will($this->returnCallback(function ($unitCode, $short) {
                return $unitCode . '-formatted-' . ($short ? 'short' : 'full');
            }))
        ;


        $this->formType     = new RequestProductType($productUnitLabelFormatter);
        $this->formType->setDataClass('Oro\Bundle\RFPBundle\Entity\RequestProduct');

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->callback(function (array $options) {
                $this->assertArrayHasKey('data_class', $options);
                $this->assertArrayHasKey('compact_units', $options);
                $this->assertArrayHasKey('intention', $options);
                $this->assertArrayHasKey('extra_fields_message', $options);
                $this->assertArrayHasKey('page_component', $options);
                $this->assertArrayHasKey('page_component_options', $options);

                return true;
            }));
        ;

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(RequestProductType::NAME, $this->formType->getName());
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider buildViewProvider
     */
    public function testBuildView(array $inputData, array $expectedData)
    {
        $view = new FormView();

        $view->vars = $inputData['vars'];

        /* @var $form FormInterface|\PHPUnit_Framework_MockObject_MockObject */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->formType->buildView($view, $form, $inputData['options']);

        $this->assertSame($expectedData, $view->vars);
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
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

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
                        ->setProduct($this->createProduct(1, ['unit1', 'unit2']))
                    ],
                    'options' => [
                        'compact_units' => false,
                    ],
                ],
                'expected'  => [
                    'value' => (new RequestProduct())
                        ->setProduct($this->createProduct(1, ['unit1', 'unit2'])),
                    'componentOptions' => [
                        'units' => [
                            1 => [
                                'unit1' => 'unit1-formatted-full',
                                'unit2' => 'unit2-formatted-full',
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
                        ->setProduct($this->createProduct(2, ['unit3', 'unit4']))
                    ],
                    'options' => [
                        'compact_units' => true,
                    ],
                ],
                'expected'  => [
                    'value' => (new RequestProduct())
                        ->setProduct($this->createProduct(2, ['unit3', 'unit4'])),
                    'componentOptions' => [
                        'units' => [
                            2 => [
                                'unit3' => 'unit3-formatted-short',
                                'unit4' => 'unit4-formatted-short',
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
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProduct($id, array $units = [])
    {
        $product = $this->getMockEntity(
            'Oro\Bundle\ProductBundle\Entity\Product',
            [
                'getId' => $id,
                'getAvailableUnitCodes' => $units,
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
                'expectedData'  => $this->getRequestProduct(2, 'comment', [$requestProductItem])->setRequest(null),
                'defaultData'   => $this->getRequestProduct(2, 'comment', [$requestProductItem])->setRequest(null),
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
                'expectedData'  => $this->getRequestProduct(2, 'comment', [$requestProductItem]),
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
                    CollectionType::NAME                    => new CollectionType(),
                    RequestProductItemCollectionType::NAME  => new RequestProductItemCollectionType(),
                    ProductUnitSelectionType::NAME          => new ProductUnitSelectionTypeStub(),
                    $priceType->getName()                   => $priceType,
                    $productSelectType->getName()           => $productSelectType,
                    $requestProductItemType->getName()      => $requestProductItemType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                    QuantityTypeTrait::$name                => $this->getQuantityType(),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
