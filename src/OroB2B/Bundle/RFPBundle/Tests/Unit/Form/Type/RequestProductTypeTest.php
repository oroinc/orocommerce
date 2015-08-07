<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductRemovedSelectType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitRemovedSelectionType;
use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductItemCollectionType;

class RequestProductTypeTest extends AbstractTest
{
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
        $this->translator   = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formType     = new RequestProductType($this->translator);
        $this->formType->setDataClass('OroB2B\Bundle\RFPBundle\Entity\RequestProduct');

        parent::setUp();
    }

    public function testConfigureOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => 'OroB2B\Bundle\RFPBundle\Entity\RequestProduct',
                'intention'  => 'rfp_request_product',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
            ])
        ;

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(RequestProductType::NAME, $this->formType->getName());
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
     * @param int $id
     * @param RequestProduct $product
     * @param string $productSku
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestProduct
     */
    protected function createRequestProduct($id, $product, $productSku)
    {
        /* @var $requestProduct \PHPUnit_Framework_MockObject_MockObject|RequestProduct */
        $requestProduct = $this->getMock('OroB2B\Bundle\RFPBundle\Entity\RequestProduct');
        $requestProduct
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id))
        ;
        $requestProduct
            ->expects($this->any())
            ->method('getProduct')
            ->will($this->returnValue($product))
        ;
        $requestProduct
            ->expects($this->any())
            ->method('getProductSku')
            ->will($this->returnValue($productSku))
        ;

        return $requestProduct;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductEntityType();
        $optionalPriceType          = $this->prepareOptionalPriceType();
        $productRemovedSelectType   = new StubProductRemovedSelectType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $requestProductItemType     = $this->prepareRequestProductItemType($this->translator);
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME                    => new CollectionType(),
                    RequestProductItemCollectionType::NAME  => new RequestProductItemCollectionType(),
                    ProductUnitRemovedSelectionType::NAME   => new StubProductUnitRemovedSelectionType(),
                    $priceType->getName()                   => $priceType,
                    $entityType->getName()                  => $entityType,
                    $optionalPriceType->getName()           => $optionalPriceType,
                    $productRemovedSelectType->getName()    => $productRemovedSelectType,
                    $requestProductItemType->getName()      => $requestProductItemType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
