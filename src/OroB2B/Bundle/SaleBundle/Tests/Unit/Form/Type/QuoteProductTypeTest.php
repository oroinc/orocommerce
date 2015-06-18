<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductSelectType;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductItemCollectionType;

class QuoteProductTypeTest extends FormIntegrationTestCase
{
    /**
     * @var QuoteProductType
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

        $this->formType = new QuoteProductType($translator);
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
            ->with('product', ProductSelectType::NAME, [
                'required'  => true,
                'label'     => 'orob2b.product.entity_label',
                'create_enabled' => false,
            ])
            ->will($this->returnSelf())
        ;

        $builder->expects($this->at(1))
            ->method('add')
            ->with('quoteProductItems', QuoteProductItemCollectionType::NAME, [
                'add_label' => 'orob2b.sale.quoteproductitem.add_label',
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
                'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProduct',
                'intention'     => 'sale_quote_product',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"'
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
    }
}
