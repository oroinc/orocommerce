<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteType;
use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductCollectionType;

class QuoteTypeTest extends FormIntegrationTestCase
{
    /**
     * @var QuoteType
     */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->type = new QuoteType();
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
            ->with('qid', 'hidden')
            ->will($this->returnSelf())
        ;

        $builder->expects($this->at(1))
            ->method('add')
            ->with('owner', 'oro_user_select', [
                'required'  => true,
                'label'     => 'orob2b.sale.quote.owner.label',
            ])
            ->will($this->returnSelf())
        ;

        $builder->expects($this->at(2))
            ->method('add')
            ->with('validUntil', OroDateTimeType::NAME, [
                'required'  => false,
                'label'     => 'orob2b.sale.quote.valid_until.label',
            ])
            ->will($this->returnSelf())
        ;

        $builder->expects($this->at(3))
            ->method('add')
            ->with('quoteProducts', QuoteProductCollectionType::NAME, [
                'add_label' => 'orob2b.sale.quoteproduct.add_label',
                'required'  => false,
            ])
            ->will($this->returnSelf())
        ;

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\Quote',
                    'intention'     => 'sale_quote',
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
