<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;

class OrderTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new OrderType();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => 'Order',
                ]
            );

        $this->type->setDataClass('Order');
        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('orob2b_order_type', $this->type->getName());
    }
}
