<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Helper\ShoppingListLineItemHelper;

class ShoppingListLineItemHelperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const CLASS_NAME = 'OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList';

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var ShoppingListLineItemHelper */
    protected $helper;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new ShoppingListLineItemHelper($this->registry, $this->securityFacade);
        $this->helper->setShoppingListClass(self::CLASS_NAME);
    }

    protected function tearDown()
    {
        unset($this->helper, $this->registry, $this->securityFacade);
    }

    public function testGetShoppingLists()
    {
        $user = new AccountUser();

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $shoppingList1 = $this->createShoppingList();
        $shoppingList2 = $this->createShoppingList();

        /** @var  \PHPUnit_Framework_MockObject_MockObject|ShoppingListRepository $repository */
        $repository = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findAllExceptCurrentForAccountUser')
            ->with($user)
            ->willReturn([$shoppingList1]);
        $repository->expects($this->once())
            ->method('findCurrentForAccountUser')
            ->with($user)
            ->willReturn($shoppingList2);

        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(self::CLASS_NAME)
            ->willReturn($repository);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn(new AccountUser());

        $this->assertEquals(
            [
                'shoppingLists' => [$shoppingList1],
                'currentShoppingList' => $shoppingList2
            ],
            $this->helper->getShoppingLists()
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage AccountUser required.
     */
    public function testGetShoppingListsException()
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn(new User());

        $this->helper->getShoppingLists();
    }

    /**
     * @return ShoppingList
     */
    protected function createShoppingList()
    {
        return $this->getEntity(self::CLASS_NAME, ['id' => mt_rand(1, 1000)]);
    }
}
