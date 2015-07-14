<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCase;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListTest extends EntityTestCase
{
    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['label', 'label-test-123'],
            ['notes', 'notes-test-123'],
            ['owner', new User()],
            ['organization', new Organization()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        $this->assertPropertyAccessors(new ShoppingList(), $properties);
        $this->assertPropertyCollections(new ShoppingList(), [
            ['lineItems', new LineItem()]
        ]);
    }

    public function testPrePersist()
    {
        $shoppingList = new ShoppingList();
        $shoppingList->prePersist();
        $this->assertInstanceOf('\DateTime', $shoppingList->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $shoppingList->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $shoppingList = new ShoppingList();
        $shoppingList->preUpdate();
        $this->assertInstanceOf('\DateTime', $shoppingList->getUpdatedAt());
    }
}
