<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\Owner\OwnerChecker;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListOwnerManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ShoppingListOwnerManagerTest extends TestCase
{
    protected ManagerRegistry|MockObject $doctrine;
    protected OwnerChecker|MockObject $ownerChecker;

    private ShoppingListOwnerManager $manager;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->ownerChecker = $this->createMock(OwnerChecker::class);

        $this->manager = new ShoppingListOwnerManager(
            $this->ownerChecker,
            $this->doctrine
        );
    }

    public function testSetOwner(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);

        $user = new CustomerUser();
        $repo->method('find')->with(1)->willReturn($user);
        $shoppingList = new ShoppingList();
        $lineItem1 = new LineItem();
        $lineItem2 = new LineItem();
        $lineItem3 = new LineItem();
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);
        $shoppingList->addSavedForLaterLineItem($lineItem3);

        $this->ownerChecker->expects($this->once())
            ->method('isOwnerCanBeSet')
            ->with($shoppingList)
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('flush');

        $this->manager->setOwner(1, $shoppingList);
        self::assertSame($user, $shoppingList->getCustomerUser());
        self::assertSame($user, $lineItem1->getCustomerUser());
        self::assertSame($user, $lineItem2->getCustomerUser());
        self::assertSame($user, $lineItem3->getCustomerUser());
    }

    public function testSetSameOwner(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);

        $user = new CustomerUser();
        $repo->expects(self::any())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        // if new owner is same as current owner don't run flush
        $em->expects(self::never())
            ->method('flush');

        $this->ownerChecker->expects($this->never())
            ->method('isOwnerCanBeSet');

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($user);
        $this->manager->setOwner(1, $shoppingList);
        self::assertSame($user, $shoppingList->getCustomerUser());
    }

    public function testSetOwnerUserNotExists(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User with id=1 not exists');

        $repo = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);
        // user with requested id not exists
        $repo->expects(self::any())
            ->method('find')
            ->with(1)
            ->willReturn(null);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        // flush should not be called
        $em->expects(self::never())
            ->method('flush');
        $this->ownerChecker->expects($this->never())
            ->method('isOwnerCanBeSet');
        $this->manager->setOwner(1, new ShoppingList());
    }

    public function testSetOwnerPermissionDenied(): void
    {
        $shoppingList = new ShoppingList();
        $repo = $this->createMock(EntityRepository::class);
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);

        $user = new CustomerUser();
        $repo->expects(self::any())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        $this->ownerChecker->expects($this->once())
            ->method('isOwnerCanBeSet')
            ->with($shoppingList)
            ->willReturn(false);

        // flush should not be called
        $em->expects(self::never())
            ->method('flush');
        $this->expectException(AccessDeniedException::class);
        $this->manager->setOwner(1, $shoppingList);
    }
}
