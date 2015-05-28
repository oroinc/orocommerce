<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

use OroB2B\Bundle\SaleBundle\Form\Type\QuoteProductItemType;

class QuoteProductItemTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteProductItemType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new QuoteProductItemType();
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
            ->with('productUnit', ProductUnitSelectionType::NAME, [
                'compact'   => false,
                'disabled'  => false,
                'label'     => 'orob2b.product.productunit.entity_label',
            ])
            ->will($this->returnSelf())
        ;

        $builder->expects($this->at(2))
            ->method('add')
            ->with('price', PriceType::NAME, [
                'required'  => true,
                'label'     => 'orob2b.sale.quote.quoteproduct.quoteproductitem.price.label',
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
            ->with([
                'data_class'    => 'OroB2B\Bundle\SaleBundle\Entity\QuoteProductItem',
                'intention'     => 'sale_quote_product_item',
                'extra_fields_message'  => 'This form should not contain extra fields: "{{ extra_fields }}"',
            ])
        ;

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_sale_quote_product_item', $this->type->getName());
    }
}
