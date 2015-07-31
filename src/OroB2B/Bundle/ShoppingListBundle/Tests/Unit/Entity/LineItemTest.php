<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LineItemTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['product', new Product()],
            ['shoppingList', new ShoppingList()],
            ['notes', 'notes-test-123'],
            ['unit', new ProductUnit()],
            ['quantity', 12.5]
        ];

        $this->assertPropertyAccessors(new LineItem(), $properties);
    }
}
