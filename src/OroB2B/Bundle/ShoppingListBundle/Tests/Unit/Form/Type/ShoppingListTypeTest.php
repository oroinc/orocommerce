<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;

class ShoppingListTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShoppingListType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new ShoppingListType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'label',
                'text',
                ['required' => true, 'label' => 'orob2b.shoppinglist.label.label']
            )
            ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['data_class' => 'ShoppingList']);

        $this->type->setDataClass('ShoppingList');
        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_shopping_list_type', $this->type->getName());
    }
}
