<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\LineItem\Factory;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\LineItem\Factory\ConfigurableProductLineItemFactory;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class ConfigurableProductLineItemFactoryTest extends TestCase
{
    public function testCreate()
    {
        $customerUser = new CustomerUser();
        $organization = $this->createMock(OrganizationInterface::class);
        $owner = new User();
        $unit = new ProductUnit();

        $shoppingList = new ShoppingList();
        $shoppingList
            ->setCustomerUser($customerUser)
            ->setOrganization($organization)
            ->setOwner($owner);

        $product = new Product();
        $product->setPrimaryUnitPrecision((new ProductUnitPrecision())->setUnit($unit));

        $lineItem = new LineItem();
        $lineItem
            ->setProduct($product)
            ->setQuantity(0)
            ->setShoppingList($shoppingList)
            ->setCustomerUser($customerUser)
            ->setOrganization($organization)
            ->setUnit($unit);

        static::assertEquals(
            $lineItem,
            (new ConfigurableProductLineItemFactory())->create($shoppingList, $product)
        );
    }
}
