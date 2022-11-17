<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;

class SubtotalTest extends \PHPUnit\Framework\TestCase
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
        $this->assertEquals(false, $subtotal->setRemovable(false)->isRemovable());
        $this->assertEquals(['some value'], $subtotal->setData(['some value'])->getData());
        $this->assertEquals(987, $subtotal->setSortOrder(987)->getSortOrder());
        $this->assertEquals(
            'test',
            $subtotal->setPriceList((new CombinedPriceList())->setName('test'))
                ->getPriceList()->getName()
        );
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
                'data' => $subtotal->getData(),
                'signedAmount' => $subtotal->getSignedAmount(),
            ],
            $subtotal->toArray()
        );
    }

    public function testGetSignedAmount()
    {
        $subtotal = new Subtotal();
        $this->assertEquals(0.0, $subtotal->getAmount());
        $subtotal->setAmount(10);

        $this->assertEquals(10, $subtotal->getSignedAmount());

        $subtotal->setOperation(Subtotal::OPERATION_SUBTRACTION);

        $this->assertEquals(-10, $subtotal->getSignedAmount());
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
