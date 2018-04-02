<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Form\Type\Frontend;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\QuantityTypeTrait;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\ProductUnitSelectionTypeStub;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductItemCollectionType;
use Oro\Bundle\RFPBundle\Form\Type\Frontend\RequestProductType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductType as BaseRequestProductType;
use Oro\Bundle\RFPBundle\Tests\Unit\Form\Type\AbstractTest;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class RequestProductTypeTest extends AbstractTest
{
    use QuantityTypeTrait;

    /**
     * @var RequestProductType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator   = $this->createMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formType     = new RequestProductType($this->translator);
        $this->formType->setDataClass('Oro\Bundle\RFPBundle\Entity\RequestProduct');

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with($this->callback(function (array $options) {
                $this->assertArrayHasKey('data_class', $options);
                $this->assertArrayHasKey('csrf_token_id', $options);

                return true;
            }));

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        static::assertEquals(RequestProductType::NAME, $this->formType->getName());
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
            'empty items' => [
                'isValid'       => false,
                'submittedData' => [
                    'product' => 1,
                ],
                'expectedData'  => $this->getRequestProduct(1),
                'defaultData'   => $this->getRequestProduct(1),
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
        /* @var $productUnitLabelFormatter ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
        $productUnitLabelFormatter = $this->getMockBuilder(
            'Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductSelectType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $requestProductItemType     = $this->prepareRequestProductItemType();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        $requestProductType = new BaseRequestProductType($productUnitLabelFormatter);
        $requestProductType->setDataClass('Oro\Bundle\RFPBundle\Entity\RequestProduct');

        return [
            new PreloadedExtension(
                [

                    CollectionType::NAME                   => new CollectionType(),
                    RequestProductItemCollectionType::NAME => new RequestProductItemCollectionType(),
                    ProductUnitSelectionType::NAME         => new ProductUnitSelectionTypeStub(),
                    $priceType->getName()                  => $priceType,
                    $entityType->getName()                 => $entityType,
                    $requestProductType->getName()         => $requestProductType,
                    $requestProductItemType->getName()     => $requestProductItemType,
                    CurrencySelectionType::class           => $currencySelectionType,
                    $productUnitSelectionType->getName()   => $productUnitSelectionType,
                    QuantityTypeTrait::$name               => $this->getQuantityType(),
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
