<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;

class ShoppingListTotalTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new ShoppingListTotal(), [
            ['id', 1],
            ['shoppingList', new ShoppingList()],
            ['currency', 'some string'],
            ['subtotal', 'some string'],
            ['valid', 1]
        ]);
    }
}
