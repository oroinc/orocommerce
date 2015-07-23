<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

use OroB2B\Bundle\RFPBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductType;
use OroB2B\Bundle\RFPBundle\Form\Type\RequestProductItemType;
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

        parent::setUp();
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => 'OroB2B\Bundle\RFPBundle\Entity\RequestProduct',
                'intention'  => 'rfp_request_product',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(RequestProductType::NAME, $this->formType->getName());
    }

    /**
     * @param RequestProduct $inputData
     * @param array $expectedData
     *
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData(RequestProduct $inputData = null, array $expectedData = [])
    {
        $productSku = $inputData ? $inputData->getProductSku() : '';

        $placeholder = $expectedData['configs']['placeholder'];

        $this->translator
            ->expects($placeholder ? $this->once() : $this->never())
            ->method('trans')
            ->with($placeholder, [
                '{title}' => $productSku,
            ])
            ->will($this->returnValue($placeholder))
        ;

        $form = $this->factory->create($this->formType);

        $this->formType->preSetData(new FormEvent($form, $inputData));

        $this->assertTrue($form->has('product'));

        $config = $form->get('product')->getConfig();

        $this->assertEquals(ProductSelectType::NAME, $config->getType()->getName());

        $options = $form->get('product')->getConfig()->getOptions();

        foreach ($expectedData as $key => $value) {
            $this->assertEquals($value, $options[$key], $key);
        }
    }

    /**
     * @return array
     */
    public function preSetDataProvider()
    {
        return [
            'empty item' => [
                'inputData'     => null,
                'expectedData'  => [
                    'configs'   => [
                        'placeholder'   => null,
                    ],
                    'required'          => true,
                    'create_enabled'    => false,
                    'label'             => 'orob2b.product.entity_label',
                ],
            ],
            'deleted product' => [
                'inputData'     => $this->createRequestProduct(1, null, 'sku'),
                'expectedData'  => [
                    'configs'   => [
                        'placeholder' => 'orob2b.rfp.message.requestproductitem.unit.removed',
                    ],
                    'required'  => true,
                    'label'     => 'orob2b.product.entity_label',
                ],
            ],
        ];
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
        $productSelectType          = new ProductSelectTypeStub();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        return [
            new PreloadedExtension(
                [
                    CollectionType::NAME                    => new CollectionType(),
                    RequestProductItemType::NAME            => new RequestProductItemType($this->translator),
                    RequestProductItemCollectionType::NAME  => new RequestProductItemCollectionType(),
                    $priceType->getName()                   => $priceType,
                    $entityType->getName()                  => $entityType,
                    $optionalPriceType->getName()           => $optionalPriceType,
                    $productSelectType->getName()           => $productSelectType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
