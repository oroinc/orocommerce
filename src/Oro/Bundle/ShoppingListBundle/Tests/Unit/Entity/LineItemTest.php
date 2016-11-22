<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LineItemTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['product', new Product()],
            ['shoppingList', new ShoppingList()],
            ['organization', new Organization()],
            ['notes', 'notes-test-123'],
            ['unit', new ProductUnit()],
            ['quantity', 12.5],
            ['accountUser', new AccountUser()],
            ['organization', new Organization()],
            ['owner', new User()],
        ];

        $this->assertPropertyAccessors(new LineItem(), $properties);
    }
}
