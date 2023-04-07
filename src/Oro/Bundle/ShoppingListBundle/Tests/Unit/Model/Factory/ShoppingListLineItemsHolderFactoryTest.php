<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Model\Factory;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductLineItemStub;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Model\Factory\ShoppingListLineItemsHolderFactory;
use Oro\Bundle\ShoppingListBundle\Model\ShoppingListLineItemsHolder;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Stub\LineItemStub;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use PHPUnit\Framework\TestCase;

class ShoppingListLineItemsHolderFactoryTest extends TestCase
{
    private ShoppingListLineItemsHolderFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ShoppingListLineItemsHolderFactory();
    }

    public function testCreateFromLineItemsWhenNoLineItemsAndIsArray(): void
    {
        self::assertEquals(
            new ShoppingListLineItemsHolder(new ArrayCollection()),
            $this->factory->createFromLineItems([])
        );
    }

    public function testCreateFromLineItemsWhenNoLineItems(): void
    {
        self::assertEquals(
            new ShoppingListLineItemsHolder(new ArrayCollection()),
            $this->factory->createFromLineItems(new ArrayCollection())
        );
    }

    public function testCreateFromLineItemsWhenHasLineItems(): void
    {
        $lineItem1 = new ProductLineItemStub(10);
        $lineItem2 = new ProductLineItemStub(20);

        self::assertEquals(
            new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2])),
            $this->factory->createFromLineItems(new ArrayCollection([$lineItem1, $lineItem2]))
        );
    }

    public function testCreateFromLineItemsWhenHasHolderAwareLineItems(): void
    {
        $shoppingList = new ShoppingList();
        $lineItem1 = (new LineItemStub())
            ->setShoppingList($shoppingList);
        $lineItem2 = new LineItemStub();

        self::assertEquals(
            new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2])),
            $this->factory->createFromLineItems(new ArrayCollection([$lineItem1, $lineItem2]))
        );
    }

    public function testCreateFromLineItemsWhenHasHolderAwareLineItemsWithWebsite(): void
    {
        $shoppingList = (new ShoppingList())
            ->setWebsite(new Website());
        $lineItem1 = (new LineItemStub())
            ->setShoppingList($shoppingList);
        $lineItem2 = new LineItemStub();

        self::assertEquals(
            new ShoppingListLineItemsHolder(new ArrayCollection([$lineItem1, $lineItem2]), $shoppingList->getWebsite()),
            $this->factory->createFromLineItems(new ArrayCollection([$lineItem1, $lineItem2]))
        );
    }

    public function testCreateFromLineItemsWhenHasHolderAwareLineItemsWithCustomerUser(): void
    {
        $shoppingList = (new ShoppingList())
            ->setWebsite(new Website())
            ->setCustomer(new Customer())
            ->setCustomerUser(new CustomerUser());

        $lineItem1 = (new LineItemStub())
            ->setShoppingList($shoppingList);
        $lineItem2 = (new LineItemStub())
            ->setShoppingList($shoppingList);

        self::assertEquals(
            new ShoppingListLineItemsHolder(
                new ArrayCollection([$lineItem1, $lineItem2]),
                $shoppingList->getWebsite(),
                $shoppingList->getCustomer(),
                $shoppingList->getCustomerUser()
            ),
            $this->factory->createFromLineItems(new ArrayCollection([$lineItem1, $lineItem2]))
        );
    }
}
