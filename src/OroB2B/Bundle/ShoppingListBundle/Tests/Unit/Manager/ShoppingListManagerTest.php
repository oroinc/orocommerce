<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class ShoppingListManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ShoppingList */
    protected $shoppingListOne;
    /** @var  ShoppingList */
    protected $shoppingListTwo;
    /** @var  ShoppingListManager */
    protected $manager;
    /** @var  array */
    protected $shoppingLists = [];

    protected function setUp()
    {
        $this->shoppingListOne = new ShoppingList();
        $this->shoppingListOne->setCurrent(true);

        $this->shoppingListTwo = new ShoppingList();
        $this->shoppingListTwo->setCurrent(false);

        $entityRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepository->expects($this->once())
            ->method('findCurrentForAccountUser')
            ->willReturnCallback(function (AccountUser $accountUser) {
                return $accountUser->getFirstName() === null ? $this->shoppingListOne : null;
            });

        $entityRepository->expects($this->once())
            ->method('findCurrentForAccountUser')
            ->willReturn($this->shoppingListOne);

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BShoppingListBundle:ShoppingList')
            ->will($this->returnValue($entityRepository));
        $entityManager->expects($this->any())
            ->method('persist')
            ->willReturnCallback(function (ShoppingList $obj) {
                $this->shoppingLists[] = $obj;
            });

        $managerRegistry = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList')
            ->willReturn($entityManager);

        $this->manager = new ShoppingListManager($managerRegistry);
    }

    public function testCreateCurrent()
    {
        $this->manager->setCurrent(new AccountUser(), $this->shoppingListTwo);
        $this->assertTrue($this->shoppingListTwo->isCurrent());
        $this->assertFalse($this->shoppingListOne->isCurrent());
    }

    public function testSetCurrent()
    {
        $this->assertEmpty($this->shoppingLists);
        $accountUser = new AccountUser();
        $accountUser->setCustomer(new Customer());
        $accountUser->setFirstName('First');
        $this->manager->createCurrent($accountUser);
        $this->assertCount(1, $this->shoppingLists);
        /** @var ShoppingList $list */
        $list = array_shift($this->shoppingLists);
        $this->assertTrue($list->isCurrent());
        $this->assertEquals($accountUser, $list->getAccountUser());
    }
}
