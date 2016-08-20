<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;

class ShoppingListTotalTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $shoppingList = new ShoppingList();
        $instance = new ShoppingListTotal($shoppingList, 'USD');
        $this->assertPropertyAccessors($instance, [
            ['id', 1],
            ['valid', true]
        ]);

        $this->assertSame($shoppingList, $instance->getShoppingList());
        $this->assertSame('USD', $instance->getCurrency());

        $subtotal = (new Subtotal())->setCurrency('USD')->setAmount(125);
        $instance->setSubtotal($subtotal);
        $this->assertSame('USD', $instance->getSubtotal()->getCurrency());
        $this->assertSame(125, $instance->getSubtotal()->getAmount());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testExceptionWhenDifferentSubtotalValue()
    {
        $shoppingList = new ShoppingList();
        $instance = new ShoppingListTotal($shoppingList, 'USD');
        $subtotal = (new Subtotal())->setCurrency('EUR')->setAmount(125);
        $instance->setSubtotal($subtotal);
    }
}
