<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;
use OroB2B\Bundle\RFPAdminBundle\Form\Type\RequestProductItemType;

class RequestProductItemTypeTest extends AbstractTest
{
    /**
     * @var RequestProductItemType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /* @var $translator \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formType = new RequestProductItemType($translator);
    }

    public function testGetName()
    {
        $this->assertEquals(RequestProductItemType::NAME, $this->formType->getName());
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => 'OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem',
                'intention'  => 'rfp_admin_request_product_item',
                'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
    }

    public function testPreSubmit()
    {
        $form = $this->factory->create($this->formType, null, []);

        $this->formType->preSubmit(new FormEvent($form, null));

        $this->assertTrue($form->has('productUnit'));

        $config = $form->get('productUnit')->getConfig();

        $this->assertEquals(ProductUnitSelectionType::NAME, $config->getType()->getName());
        $options = $config->getOptions();

        $this->assertFalse($options['disabled']);
        $this->assertEquals('orob2b.product.productunit.entity_label', $options['label']);
    }

    /**
     * @param mixed $inputData
     * @param mixed $expectedData
     * @param mixed $choices
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData($inputData, $expectedData, $choices)
    {
        $form = $this->factory->create($this->formType);

        $event = new FormEvent($form, $inputData);
        $this->formType->preSetData($event);
        $this->assertEquals($expectedData, $event->getData());

        $this->assertTrue($form->has('productUnit'));

        $config = $form->get('productUnit')->getConfig();

        $this->assertEquals(ProductUnitSelectionType::NAME, $config->getType()->getName());

        $options = $config->getOptions();

        $this->assertFalse($options['disabled']);
        $this->assertTrue($options['required']);
        $this->assertEquals($choices, $options['choices']);
        $this->assertEquals('orob2b.product.productunit.entity_label', $options['label']);
    }

    /**
     * @return array
     */
    public function preSetDataProvider()
    {
        $choices = [
            (new ProductUnit())->setCode('unit1'),
            (new ProductUnit())->setCode('unit2'),
            (new ProductUnit())->setCode('unit3'),
        ];

        $product = new Product();
        foreach ($choices as $unit) {
            $product->addUnitPrecision((new ProductUnitPrecision())->setUnit($unit));
        }

        /* @var $item \PHPUnit_Framework_MockObject_MockObject|RequestProductItem */
        $item = $this->getMock('OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem');
        $item
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(123))
        ;
        $item
            ->expects($this->any())
            ->method('getRequestProduct')
            ->will($this->returnValue((new RequestProduct())->setProduct($product)))
        ;

        return [
            'set data new item' => [
                'inputData'     => null,
                'expectedData'  => null,
                'choices'       => [],
            ],
            'set data existed item' => [
                'inputData'     => $item,
                'expectedData'  => $item,
                'choices'       => $choices,
            ],
        ];
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty form' => [
                'isValid'       => false,
                'submittedData' => [],
                'expectedData'  => $this->getRequestProductItem(1),
                'defaultData'   => $this->getRequestProductItem(1),
            ],
            'empty quantity' => [
                'isValid'       => true,
                'submittedData' => [
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(2, null, 'kg', $this->createPrice(20, 'USD')),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty product unit' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 10,
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(3, 10, null, $this->createPrice(20, 'USD')),
                'defaultData'   => $this->getRequestProductItem(3),
            ],
            'empty price' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                ],
                'expectedData'  => $this->getRequestProductItem(2, 10, 'kg'),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty price value' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price' => [
                        'currency' => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(2, 10, 'kg'),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty price currency' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price' => [
                        'value' => 10,
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(2, 10, 'kg', $this->createPrice(10, null)),
                'defaultData'   => $this->getRequestProductItem(2),
            ],
            'empty request product' => [
                'isValid'       => false,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(5, 10, 'kg', $this->createPrice(20, 'USD'))
                    ->setRequestProduct(null),
                'defaultData'   => $this->getRequestProductItem(5)
                    ->setRequestProduct(null),
            ],
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                    'price'         => [
                        'value'     => 20,
                        'currency'  => 'USD',
                    ],
                ],
                'expectedData'  => $this->getRequestProductItem(5, 10, 'kg', $this->createPrice(20, 'USD')),
                'defaultData'   => $this->getRequestProductItem(5),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $optionalPriceType          = $this->prepareOptionalPriceType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        return [
            new PreloadedExtension(
                [
                    $priceType->getName()                   => $priceType,
                    $optionalPriceType->getName()           => $optionalPriceType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
