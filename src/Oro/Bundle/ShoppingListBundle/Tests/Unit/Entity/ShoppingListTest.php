<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

final class ShoppingListTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', 123],
            ['label', 'label-test-123'],
            ['notes', 'notes-test-123'],
            ['subtotal', new Subtotal()],
            ['organization', new Organization()],
            ['owner', new User()],
            ['customer', new Customer()],
            ['customerUser', new CustomerUser()],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
        ];

        self::assertPropertyAccessors(new ShoppingList(), $properties);
        self::assertPropertyCollections(new ShoppingList(), [
            ['lineItems', new LineItem()],
            ['savedForLaterLineItems', new LineItem()]
        ]);

        $label = 'label-test-775';
        $shoppingList = new ShoppingList();
        $shoppingList->setLabel($label);
        self::assertEquals($label, $shoppingList);
    }

    public function testPrePersist(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->prePersist();
        self::assertInstanceOf(\DateTime::class, $shoppingList->getCreatedAt());
        self::assertInstanceOf(\DateTime::class, $shoppingList->getUpdatedAt());
    }

    public function testPreUpdate(): void
    {
        $shoppingList = new ShoppingList();
        $shoppingList->preUpdate();
        self::assertInstanceOf(\DateTime::class, $shoppingList->getUpdatedAt());
    }

    public function testSourceDocument(): void
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, 1);
        $shoppingList->setLabel('TEST');

        self::assertSame($shoppingList, $shoppingList->getSourceDocument());
        self::assertEquals('TEST', $shoppingList->getSourceDocumentIdentifier());
    }

    public function testGetVisitor(): void
    {
        $visitor = new CustomerVisitor();

        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor($visitor);

        self::assertSame($visitor, $shoppingList->getVisitor());
    }

    public function testJsonSerialize(): void
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, 1);
        $shoppingList->setLabel('TEST');

        self::assertEquals(
            '{"id":1,"label":"TEST","is_current":false}',
            json_encode($shoppingList, JSON_THROW_ON_ERROR)
        );
    }

    public function testTotalsAccessors(): void
    {
        $shoppingList = new ShoppingList();
        $currency = 'USD';
        $total = new ShoppingListTotal($shoppingList, $currency);

        self::assertCount(0, $shoppingList->getTotals());
        $shoppingList->addTotal($total);
        self::assertSame($total, $shoppingList->getTotals()->get($currency));
        $shoppingList->removeTotal($total);
        self::assertCount(0, $shoppingList->getTotals());
    }

    public function testSetLineItem(): void
    {
        $lineItem = new LineItem();
        $lineItem2 = new LineItem();
        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem($lineItem);

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertTrue($shoppingList->getSavedForLaterLineItems()->isEmpty());

        $shoppingList->addSavedForLaterLineItem($lineItem);

        self::assertTrue($shoppingList->getLineItems()->isEmpty());
        self::assertCount(1, $shoppingList->getSavedForLaterLineItems());

        $shoppingList->addLineItem($lineItem2);

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertCount(1, $shoppingList->getSavedForLaterLineItems());
    }

    public function testAddAndRemoveAssociatedListLineItem(): void
    {
        $lineItem = new LineItem();
        $shoppingList = new ShoppingList();
        $shoppingList->addAssociatedListLineItem($lineItem);

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertTrue($shoppingList->getSavedForLaterLineItems()->isEmpty());

        $lineItem2 = new LineItem();
        $lineItem2->setSavedForLaterList(new ShoppingList());
        $shoppingList->addAssociatedListLineItem($lineItem2);

        self::assertCount(1, $shoppingList->getLineItems());
        self::assertCount(1, $shoppingList->getSavedForLaterLineItems());

        $shoppingList->removeAssociatedListLineItem($lineItem);

        self::assertTrue($shoppingList->getLineItems()->isEmpty());
        self::assertCount(1, $shoppingList->getSavedForLaterLineItems());

        $shoppingList->removeAssociatedListLineItem($lineItem2);

        self::assertTrue($shoppingList->getLineItems()->isEmpty());
        self::assertTrue($shoppingList->getSavedForLaterLineItems()->isEmpty());
    }
}
