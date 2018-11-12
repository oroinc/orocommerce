<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class LineItemTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['product', new Product()],
            ['parentProduct', new Product()],
            ['shoppingList', new ShoppingList()],
            ['organization', new Organization()],
            ['notes', 'notes-test-123'],
            ['unit', new ProductUnit()],
            ['quantity', 12.5],
            ['customerUser', new CustomerUser()],
            ['organization', new Organization()],
            ['owner', new User()],
        ];

        $this->assertPropertyAccessors(new LineItem(), $properties);
    }

    public function testVisitor()
    {
        $visitor = new CustomerVisitor();
        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor($visitor);
        $lineItem = new LineItem();
        $lineItem->setShoppingList($shoppingList);

        $this->assertSame($visitor, $lineItem->getVisitor());
    }
}
