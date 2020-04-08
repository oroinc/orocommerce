<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CheckoutSubtotalTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $checkout = new Checkout();
        $entity = new CheckoutSubtotal($checkout, 'USD');
        $this->assertPropertyAccessors($entity, [
            ['id', 1],
            ['valid', true]
        ]);

        $this->assertSame($checkout, $entity->getCheckout());
        $this->assertSame('USD', $entity->getCurrency());

        $combinedPriceList = new CombinedPriceList();
        $combinedPriceList->setName('test price list');

        $subtotal = (new Subtotal())->setCurrency('USD')->setAmount(123);
        $subtotal->setCombinedPriceList($combinedPriceList);
        $entity->setSubtotal($subtotal);
        $this->assertSame('USD', $entity->getSubtotal()->getCurrency());
        $this->assertSame(123.0, $entity->getSubtotal()->getAmount());
        $this->assertSame('test price list', $entity->getSubtotal()->getCombinedPriceList()->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage  Invalid currency for Checkout Subtotal
     */
    public function testExceptionWhenDifferentSubtotalValue()
    {
        $checkout = new Checkout();
        $entity = new CheckoutSubtotal($checkout, 'USD');
        $subtotal = (new Subtotal())->setCurrency('EUR')->setAmount(123);
        $entity->setSubtotal($subtotal);
    }
}
