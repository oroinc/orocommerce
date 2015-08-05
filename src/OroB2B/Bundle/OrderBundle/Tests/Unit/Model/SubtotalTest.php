<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Model;

use OroB2B\Bundle\OrderBundle\Model\Subtotal;

class SubtotalTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $subtotal = new Subtotal();

        $this->assertEquals('subtotal', $subtotal->setType('subtotal')->getType());
        $this->assertEquals('Subtotal', $subtotal->setLabel('Subtotal')->getLabel());
        $this->assertEquals('USD', $subtotal->setCurrency('USD')->getCurrency());
        $this->assertEquals(999.99, $subtotal->setAmount(999.99)->getAmount());
    }
}
