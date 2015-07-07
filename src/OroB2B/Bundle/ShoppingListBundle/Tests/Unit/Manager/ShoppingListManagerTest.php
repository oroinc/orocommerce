<?php
namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class ShoppingListManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;
    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;
    /** @var  ShoppingList */
    protected $shoppingListOne;
    /** @var  ShoppingList */
    protected $shoppingListTwo;
    /** @var  array */
    protected $shoppingLists = [];

    protected function setUp()
    {
        $this->shoppingListOne = new ShoppingList();
        $this->shoppingListOne->setCurrent(true);

        $this->shoppingListTwo = new ShoppingList();
        $this->shoppingListTwo->setCurrent(false);

        $this->entityRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityRepository->expects($this->once())
            ->method('findCurrentForAccountUser')
            ->willReturnCallback(function (AccountUser $accountUser) {
                return $accountUser->getFirstName() === null ? $this->shoppingListOne : null;
            });

        $this->entityRepository->expects($this->once())
            ->method('findCurrentForAccountUser')
            ->willReturn($this->shoppingListOne);

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BShoppingListBundle:ShoppingList')
            ->will($this->returnValue($this->entityRepository));
        $this->entityManager->expects($this->any())
            ->method('persist')
            ->willReturnCallback(function (ShoppingList $obj) {
                $this->shoppingLists[] = $obj;
            });
    }

    public function testCreateCurrent()
    {
        $manager = new ShoppingListManager($this->entityManager);
        $manager->setCurrent(new AccountUser(), $this->shoppingListTwo);
        $this->assertTrue($this->shoppingListTwo->isCurrent());
        $this->assertFalse($this->shoppingListOne->isCurrent());
    }

    public function testSetCurrent()
    {
        $this->assertEmpty($this->shoppingLists);
        $accountUser = new AccountUser();
        $accountUser->setCustomer(new Customer());
        $accountUser->setFirstName('First');
        $manager = new ShoppingListManager($this->entityManager);
        $manager->createCurrent($accountUser);
        $this->assertCount(1, $this->shoppingLists);
        /** @var ShoppingList $list */
        $list = array_shift($this->shoppingLists);
        $this->assertTrue($list->isCurrent());
        $this->assertEquals($accountUser, $list->getAccountUser());
    }
}
