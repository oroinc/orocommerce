<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\CurrencyBundle\Model\Price;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\CurrencySelectionTypeStub;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;

/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class QuoteProductOfferTypeTest extends AbstractTest
{
    /**
     * @var QuoteProductOfferType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        /* @var $translator \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->formType = new QuoteProductOfferType($translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $priceType                  = $this->preparePriceType();
        $currencySelectionType      = new CurrencySelectionTypeStub();
        $productUnitSelectionType   = $this->prepareProductUnitSelectionType();

        return [
            new PreloadedExtension(
                [
                    $priceType->getName()                   => $priceType,
                    $currencySelectionType->getName()       => $currencySelectionType,
                    $productUnitSelectionType->getName()    => $productUnitSelectionType,
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer',
                'intention'     => 'sale_quote_product_item',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_sale_quote_product_item', $this->formType->getName());
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

        $this->assertEquals($choices, $options['choices']);
        $this->assertEquals(false, $options['disabled']);
        $this->assertEquals(true, $options['required']);
        $this->assertEquals('orob2b.product.productunit.entity_label', $options['label']);
    }

    public function testPreSubmit()
    {
        $form = $this->factory->create($this->formType, null, []);

        $this->formType->preSubmit(new FormEvent($form, null));

        $this->assertTrue($form->has('productUnit'));

        $config = $form->get('productUnit')->getConfig();

        $this->assertEquals(ProductUnitSelectionType::NAME, $config->getType()->getName());
        $options = $config->getOptions();

        $this->assertEquals(false, $options['disabled']);
        $this->assertEquals('orob2b.product.productunit.entity_label', $options['label']);
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'empty price' => [
                'isValid'       => true,
                'submittedData' => [
                    'quantity'      => 10,
                    'productUnit'   => 'kg',
                ],
                'expectedData'  => $this->getQuoteProductOffer(10, 'kg'),
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
                'expectedData'  => $this->getQuoteProductOffer(10, 'kg', Price::create(20, 'USD')),
            ],
        ];
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

        /* @var $item \PHPUnit_Framework_MockObject_MockObject|QuoteProductOffer */
        $item = $this->getMock('OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer');
        $item
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(123))
        ;
        $item
            ->expects($this->any())
            ->method('getQuoteProduct')
            ->will($this->returnValue((new QuoteProduct())->setProduct($product)))
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
}
