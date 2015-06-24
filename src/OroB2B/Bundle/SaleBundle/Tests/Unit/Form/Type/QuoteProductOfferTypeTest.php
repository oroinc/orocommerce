<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductOfferType;

class QuoteProductOfferTypeTest extends FormIntegrationTestCase
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
        /* @var $configManager \PHPUnit_Framework_MockObject_MockBuilder|ConfigManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_currency.allowed_currencies')
            ->will($this->returnValue(['USD', 'EUR']));

        /* @var $localeSettings \PHPUnit_Framework_MockObject_MockBuilder|LocaleSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    PriceType::NAME                 => new PriceType(),
                    CurrencySelectionType::NAME     => new CurrencySelectionType($configManager, $localeSettings),
                    ProductUnitSelectionType::NAME  => new ProductUnitSelectionType(),
                    'entity'                        => new EntityType([]),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    public function testBuildForm()
    {
        /* @var $builder \PHPUnit_Framework_MockObject_MockBuilder|FormBuilder */
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $builder->expects($this->at(0))
            ->method('add')
            ->with('quantity', 'integer', [
                'required'  => true,
                'label'     => 'orob2b.sale.quoteproductitem.quantity.label',
            ])
            ->will($this->returnSelf())
        ;

        $builder->expects($this->at(1))
            ->method('add')
            ->with('price', PriceType::NAME, [
                'required'  => true,
                'label'     => 'orob2b.sale.quoteproductitem.price.label',
            ])
            ->will($this->returnSelf())
        ;

        $this->formType->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer',
                'intention'     => 'sale_quote_product_offer',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_sale_quote_product_offer', $this->formType->getName());
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
        $this->assertEquals(false, $options['compact']);
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

        $this->assertEquals(false, $options['compact']);
        $this->assertEquals(false, $options['disabled']);
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
                'choices'       => null,
            ],
            'set data existed item' => [
                'inputData'     => $item,
                'expectedData'  => $item,
                'choices'       => $choices,
            ],
        ];
    }
}
