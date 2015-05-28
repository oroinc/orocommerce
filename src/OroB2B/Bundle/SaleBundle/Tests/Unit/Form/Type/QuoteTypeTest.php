<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductType;

class QuoteTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new QuoteType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $builder->expects($this->at(0))
            ->method('add')
            ->with('qid', 'hidden')
            ->will($this->returnSelf())
        ;

        $builder->expects($this->at(1))
            ->method('add')
            ->with('owner', null, ['required' => true, 'label' => 'orob2b.sale.quote.owner.label'])
            ->will($this->returnSelf())
        ;

        $builder->expects($this->at(2))
            ->method('add')
            ->with('validUntil', null, ['required' => false, 'label' => 'orob2b.sale.quote.valid_until.label'])
            ->will($this->returnSelf())
        ;

        $builder->expects($this->at(3))
            ->method('add')
            ->with('quoteProducts', 'oro_collection', [
                'label'     => 'orob2b.sale.quote.quoteproduct.entity_plural_label',
                'required'  => false,
                'type'      => QuoteProductType::NAME,
                'show_form_when_empty' => false
            ])
            ->will($this->returnSelf())
        ;

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'OroB2B\Bundle\SaleBundle\Entity\Quote',
                    'intention' => 'sale_quote',
                    'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"'
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_sale_quote', $this->type->getName());
    }
}
