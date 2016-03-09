<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Model;

use OroB2B\Bundle\OrderBundle\Model\Subtotal;
use OroB2B\Bundle\OrderBundle\Provider\SubtotalLineItemProvider;

class SubtotalTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $subtotal = new Subtotal();

        $this->assertEquals(
            SubtotalLineItemProvider::TYPE,
            $subtotal->setType(SubtotalLineItemProvider::TYPE)->getType()
        );
        $this->assertEquals('Subtotal', $subtotal->setLabel('Subtotal')->getLabel());
        $this->assertEquals('USD', $subtotal->setCurrency('USD')->getCurrency());
        $this->assertEquals(999.99, $subtotal->setAmount(999.99)->getAmount());
        $this->assertEquals(true, $subtotal->setVisible(true)->isVisible());
    }

    public function testToArray()
    {
        $subtotal = new Subtotal();

        $this->assertEquals(
            [
                'type' => $subtotal->getType(),
                'label' => $subtotal->getLabel(),
                'amount' => $subtotal->getAmount(),
                'currency' => $subtotal->getCurrency(),
                'visible' => $subtotal->isVisible(),
            ],
            $subtotal->toArray()
        );
    }
}
