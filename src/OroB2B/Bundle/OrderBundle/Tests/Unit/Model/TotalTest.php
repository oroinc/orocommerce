<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Model;

use OroB2B\Bundle\OrderBundle\Model\Total;

class TotalTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $total = new Total();

        $this->assertEquals('subtotal', $total->setName('subtotal')->getName());
        $this->assertEquals('Subtotal', $total->setLabel('Subtotal')->getLabel());
        $this->assertEquals('USD', $total->setCurrency('USD')->getCurrency());
        $this->assertEquals(999.99, $total->setAmount(999.99)->getAmount());
    }
}
