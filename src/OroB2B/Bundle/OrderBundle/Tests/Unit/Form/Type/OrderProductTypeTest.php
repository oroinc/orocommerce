<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductRemovedSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitRemovedSelectionType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductRemovedSelectType;

use OroB2B\Bundle\OrderBundle\Entity\OrderProduct;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductType;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderProductItemCollectionType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class OrderProductTypeTest extends AbstractTest
{
    /**
     * @var OrderProductType
     */
    protected $formType;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        /* @var $productUnitLabelFormatter ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
        $productUnitLabelFormatter = $this->getMockBuilder(
            'OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $productUnitLabelFormatter->expects($this->any())
            ->method('format')
            ->will($this->returnCallback(function ($unitCode) {
                return $unitCode . '-formatted';
            }))
        ;

        parent::setUp();

        $this->formType = new OrderProductType(
            $productUnitLabelFormatter,
            $this->orderProductFormatter
        );
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderProduct');
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'    => 'OroB2B\Bundle\OrderBundle\Entity\OrderProduct',
                'intention'     => 'order_order_product',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"'
            ])
        ;

        $this->formType->configureOptions($resolver);
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

        $view->vars = $inputData;

        /* @var $form FormInterface|\PHPUnit_Framework_MockObject_MockObject */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->formType->finishView($view, $form, []);

        $this->assertEquals($expectedData, $view->vars);
    }

    /**
     * @return array
     */
    public function finishViewProvider()
    {
        return [
            'empty order product' => [
                'input'     => [
                    'value' => null,
                ],
                'expected'  => [
                    'value' => null,
                    'componentOptions' => [
                        'units' => [],
                    ],
                ],
            ],
            'empty product' => [
                'input'     => [
                    'value' => new OrderProduct(),
                ],
                'expected'  => [
                    'value' => new OrderProduct(),
                    'componentOptions' => [
                        'units' => [],
                    ],
                ],
            ],
            'existing product' => [
                'input'     => [
                    'value' => (new OrderProduct())
                        ->setProduct($this->createProduct(1, ['unit1', 'unit2'])),
                ],
                'expected'  => [
                    'value' => (new OrderProduct())
                        ->setProduct($this->createProduct(1, ['unit1', 'unit2'])),
                    'componentOptions' => [
                        'units' => [
                            1 => [
                                'unit1' => 'unit1-formatted',
                                'unit2' => 'unit2-formatted',
                            ],
                        ],
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
        /* @var $product Product|\PHPUnit_Framework_MockObject_MockObject */
        $product = $this->getMockEntity(
            'OroB2B\Bundle\ProductBundle\Entity\Product',
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
        $orderProductItem = $this->getOrderProductItem(2, 10, 'kg', self::OPI_PRICE_TYPE1, Price::create(20, 'USD'));

        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [
                ],
                'expectedData'  => $this->getOrderProduct(2)->setProduct(null),
                'inputData'     => $this->getOrderProduct(2)->setProduct(null),
            ],
            'empty order' => [
                'isValid'       => false,
                'submittedData' => [
                    'product'   => 2,
                    'comment'   => 'comment1',
                    'orderProductItems' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'priceType'     => self::OPI_PRICE_TYPE1,
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData' => $this->getOrderProduct(
                    2,
                    'comment1',
                    [
                        clone $orderProductItem,
                    ]
                )->setOrder(null),
                'inputData' => $this->getOrderProduct(2)->setOrder(null)->setProduct(null),
            ],
            'empty product' => [
                'isValid'       => false,
                'submittedData' => [
                    'comment'   => 'comment1',
                    'orderProductItems' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'priceType'     => self::OPI_PRICE_TYPE1,
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData' => $this->getOrderProduct(
                    2,
                    'comment1',
                    [
                        clone $orderProductItem,
                    ]
                )->setProduct(null),
                'inputData' => $this->getOrderProduct(2)->setProduct(null),
            ],
            'empty offers' => [
                'isValid'       => false,
                'submittedData' => [
                    'product'   => 2,
                    'comment'   => 'comment1',
                ],
                'expectedData'  => $this->getOrderProduct(2, 'comment1', []),
                'inputData'     => $this->getOrderProduct(2)->setProduct(null),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'product'   => 2,
                    'comment'   => 'comment1',
                    'orderProductItems' => [
                        [
                            'quantity'      => 10,
                            'productUnit'   => 'kg',
                            'priceType'     => self::OPI_PRICE_TYPE1,
                            'price'         => [
                                'value'     => 20,
                                'currency'  => 'USD',
                            ],
                        ],
                    ],
                ],
                'expectedData' => $this->getOrderProduct(
                    2,
                    'comment1',
                    [
                        clone $orderProductItem,
                    ]
                ),
                'inputData' => $this->getOrderProduct(2)->setProduct(null),
            ],
        ];
    }

    /**
     * @param int $id
     * @param Product $product
     * @param string $productSku
     * @return OrderProduct|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOrderProduct($id, Product $product = null, $productSku = null)
    {
        /* @var $orderProduct OrderProduct|\PHPUnit_Framework_MockObject_MockObject */
        $orderProduct = $this->getMock('OroB2B\Bundle\OrderBundle\Entity\OrderProduct');
        $orderProduct
            ->expects($this->any())
            ->method('getId')
            ->willReturn($id)
        ;
        $orderProduct
            ->expects($this->any())
            ->method('getProduct')
            ->willReturn($product)
        ;
        $orderProduct
            ->expects($this->any())
            ->method('getProductSku')
            ->willReturn($productSku)
        ;

        return $orderProduct;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductEntityType();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();
        $orderProductItemType       = $this->prepareOrderProductItemType($this->translator);

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME                        => new CollectionType(),
                    OrderProductItemCollectionType::NAME        => new OrderProductItemCollectionType(),
                    ProductRemovedSelectType::NAME              => new StubProductRemovedSelectType(),
                    ProductUnitRemovedSelectionType::NAME       => new StubProductUnitRemovedSelectionType(),
                    ProductSelectType::NAME                     => new ProductSelectTypeStub(),
                    CurrencySelectionType::NAME                 => new CurrencySelectionTypeStub(),
                    $priceType->getName()                       => $priceType,
                    $entityType->getName()                      => $entityType,
                    $orderProductItemType->getName()            => $orderProductItemType,
                    $productUnitSelectionType->getName()        => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
