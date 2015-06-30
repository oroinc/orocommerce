<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\ProductSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;

use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductType;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductItemType;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductItemCollectionType;

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

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $entityType                 = $this->prepareProductEntityType();
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
                    $productSelectType->getName()           => $productSelectType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'    => 'OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct',
                'intention'     => 'rfp_admin_request_product',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"'
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
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
        $requestProductItem = $this->getRequestProductItem(2, 10, 'kg', Price::create(20, 'USD'));
        $requestProduct     = $this->getRequestProduct(2, [$requestProductItem]);
        $requestProduct->setComment('comment');

        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => new RequestProduct(),
            ],
            'invalid product and empty items' => [
                'isValid'       => false,
                'submittedData' => [
                    'product' => 333,
                ],
                'expectedData'  => new RequestProduct(),
            ],
            'empty items' => [
                'isValid'       => false,
                'submittedData' => [
                    'product' => 2,
                ],
                'expectedData'  => $this->getRequestProduct(2),
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
                'expectedData'  => $requestProduct,
            ],
        ];
    }
}
