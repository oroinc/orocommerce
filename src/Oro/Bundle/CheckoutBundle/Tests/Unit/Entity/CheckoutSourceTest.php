<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Entity;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CheckoutSourceTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['deleted', true]
        ];

        $entity = new CheckoutSource();
        $this->assertPropertyAccessors($entity, $properties);
    }

    public function testGetEntity(): void
    {
        $checkoutSource = new CheckoutSourceStub();

        $this->assertNull($checkoutSource->getEntity());

        $checkoutSource->setShoppingList(new ShoppingList());

        $this->assertInstanceOf(ShoppingList::class, $checkoutSource->getEntity());
    }

    public function testClear(): void
    {
        $checkoutSource = new CheckoutSourceStub();
        $checkoutSource->setShoppingList(new ShoppingList());

        $this->assertInstanceOf(ShoppingList::class, $checkoutSource->getShoppingList());

        $checkoutSource->clear();

        $this->assertNull($checkoutSource->getShoppingList());
        $this->assertNull($checkoutSource->getEntity());
    }
}
