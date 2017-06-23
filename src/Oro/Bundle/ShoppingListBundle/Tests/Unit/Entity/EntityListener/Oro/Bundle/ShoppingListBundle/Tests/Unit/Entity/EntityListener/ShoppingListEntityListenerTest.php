<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ShoppingListBundle\Entity\EntityListener\ShoppingListEntityListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\UserBundle\Entity\User;

class ShoppingListEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShoppingListEntityListener */
    private $shoppingListEntityListener;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var GuestShoppingListManager|\PHPUnit_Framework_MockObject_MockObject */
    private $guestShoppingListManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->guestShoppingListManager = $this->getMockBuilder(GuestShoppingListManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shoppingListEntityListener = new ShoppingListEntityListener(
            $this->configManager,
            $this->guestShoppingListManager
        );
    }

    public function testPrePersist()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.default_guest_shopping_list_owner')
            ->willReturn(1);

        $assignedOwner = new User();

        $this->guestShoppingListManager->expects($this->once())
            ->method('getDefaultUser')
            ->with(1)
            ->willReturn($assignedOwner);

        $shoppingList = new ShoppingList();

        $this->shoppingListEntityListener->prePersist($shoppingList);

        $this->assertSame($assignedOwner, $shoppingList->getOwner());
    }

    public function testPrePersistWithOwnerAssigned()
    {
        $this->configManager->expects($this->never())->method('get');

        $this->guestShoppingListManager->expects($this->never())->method('getDefaultUser');

        $assignedOwner = new User();
        $shoppingList = (new ShoppingList())->setOwner($assignedOwner);

        $this->shoppingListEntityListener->prePersist($shoppingList);

        $this->assertSame($assignedOwner, $shoppingList->getOwner());
    }
}
