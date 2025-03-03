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

class ShoppingListTest extends \PHPUnit\Framework\TestCase
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
            ['lineItems', new LineItem()]
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
}
