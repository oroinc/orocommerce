<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

use OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductItemType;

class QuoteProductItemTypeTest extends FormIntegrationTestCase
{
    /**
     * @var QuoteProductItemType
     */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new QuoteProductItemType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_currency.allowed_currencies')
            ->will($this->returnValue(['USD', 'EUR']));

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
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $builder->expects($this->at(0))
            ->method('add')
            ->with('quantity', null, [
                'required'  => true,
                'label'     => 'orob2b.sale.quote.quoteproduct.quoteproductitem.quantity.label',
            ])
            ->will($this->returnSelf())
        ;

        $builder->expects($this->at(1))
            ->method('add')
            ->with('price', PriceType::NAME, [
                'required'  => true,
                'label'     => 'orob2b.sale.quote.quoteproduct.quoteproductitem.price.label',
            ])
            ->will($this->returnSelf())
        ;

        $this->formType->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem',
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
     * @param $inputData
     * @param $expectedData
     * @dataProvider preSetDataProvider
     */
    public function testPreSetData($inputData, $expectedData)
    {
        $form = $this->factory->create($this->formType);

        $event = new FormEvent($form, $inputData);
        $this->formType->preSetData($event);
        $this->assertEquals($expectedData, $event->getData());

        $this->assertTrue($form->has('productUnit'));

        $config = $form->get('productUnit')->getConfig();

        $this->assertEquals(ProductUnitSelectionType::NAME, $config->getType()->getName());

        $options = $config->getOptions();

        $this->assertEquals(null, $options['choices']);
        $this->assertEquals(false, $options['compact']);
        $this->assertEquals(false, $options['disabled']);
        $this->assertEquals(true, $options['required']);
        $this->assertEquals('orob2b.product.productunit.entity_label', $options['label']);
    }

    /**
     * @param $inputData
     * @param $expectedData
     * @dataProvider preSubmitProvider
     */
    public function testPreSubmit($inputData, $expectedData)
    {
        $form = $this->factory->create($this->formType, null, []);

        $event = new FormEvent($form, $inputData);
        $this->formType->preSubmit($event);
        $this->assertEquals($expectedData, $event->getData());

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
        return [
            'set data new item' => [
                'inputData'     => null,
                'expectedData'  => null,
            ],
        ];
    }

    /**
     * @return array
     */
    public function preSubmitProvider()
    {
        return [
            'submit data new item' => [
                'inputData'     => null,
                'expectedData'  => null,
            ],
        ];
    }
}
