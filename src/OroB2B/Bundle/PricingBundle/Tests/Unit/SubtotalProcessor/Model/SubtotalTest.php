<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;

class SubtotalTest extends \PHPUnit_Framework_TestCase
{
    public function testProperties()
    {
        $subtotal = new Subtotal();

        $this->assertEquals(
            LineItemSubtotalProvider::TYPE,
            $subtotal->setType(LineItemSubtotalProvider::TYPE)->getType()
        );
        $this->assertEquals('Subtotal', $subtotal->setLabel('Subtotal')->getLabel());
        $this->assertEquals('USD', $subtotal->setCurrency('USD')->getCurrency());
        $this->assertEquals(999.99, $subtotal->setAmount(999.99)->getAmount());
        $this->assertEquals(true, $subtotal->setVisible(true)->isVisible());
        $this->assertEquals(['some value'], $subtotal->setData(['some value'])->getData());
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
                'data' => $subtotal->getData()
            ],
            $subtotal->toArray()
        );
    }

    public function testGetTotalPrice()
    {
        $subtotal = new Subtotal();
        $subtotal->setCurrency('USD')
            ->setAmount(10);

        $expected = (new Price())->setValue(10)->setCurrency('USD');
        $this->assertEquals($expected, $subtotal->getTotalPrice());
    }
}
