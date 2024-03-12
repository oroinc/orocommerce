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
use PHPUnit\Framework\TestCase;

class LineItemTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123],
            ['product', new Product()],
            ['parentProduct', new Product()],
            ['shoppingList', new ShoppingList()],
            ['organization', new Organization()],
            ['notes', 'notes-test-123'],
            ['unit', new ProductUnit()],
            ['quantity', 12.5],
            ['checksum', sha1('sample-line-item')],
            ['customerUser', new CustomerUser()],
            ['organization', new Organization()],
            ['owner', new User()],
        ];

        self::assertPropertyAccessors(new LineItem(), $properties);
    }

    public function testVisitor(): void
    {
        $visitor = new CustomerVisitor();
        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor($visitor);
        $lineItem = new LineItem();
        $lineItem->setShoppingList($shoppingList);

        self::assertSame($visitor, $lineItem->getVisitor());
    }

    public function testGetLineItemsHolder(): void
    {
        $shoppingList = new ShoppingList();
        $lineItem = (new LineItem())
            ->setShoppingList($shoppingList);

        self::assertSame($shoppingList, $lineItem->getLineItemsHolder());
    }

    public function testSetProductUnit(): void
    {
        $lineItem = new LineItem();

        self::assertNull($lineItem->getProductUnit());

        $unitItem = (new ProductUnit())->setCode('item');
        $lineItem->setProductUnit($unitItem);

        self::assertSame($unitItem, $lineItem->getProductUnit());
        self::assertSame($unitItem->getCode(), $lineItem->getProductUnitCode());
    }
}
