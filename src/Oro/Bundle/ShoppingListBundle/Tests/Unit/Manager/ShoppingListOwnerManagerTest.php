<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Owner\OwnerChecker;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListOwnerManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ShoppingListOwnerManagerTest extends TestCase
{
    protected AclHelper|MockObject $aclHelper;
    protected ManagerRegistry|MockObject $registry;
    protected OwnerChecker|MockObject $ownerChecker;

    protected ShoppingListOwnerManager $manager;

    protected function setUp(): void
    {
        $this->aclHelper = $this->createMock(AclHelper::class);
        $configProvider = $this->createMock(ConfigProvider::class);
        $entityConfig = $this->createMock(ConfigInterface::class);

        $configProvider->method('getConfig')->with(ShoppingList::class)->willReturn($entityConfig);
        $entityConfig->method('get')->willReturnMap([
            ['frontend_owner_field_name', false, null, 'customerUser'],
            ['organization_field_name', false, null, 'organisation'],
        ]);

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->ownerChecker = $this->createMock(OwnerChecker::class);

        $this->manager = new ShoppingListOwnerManager(
            $this->aclHelper,
            $this->registry,
            $configProvider
        );
        $this->manager->setOwnerChecker($this->ownerChecker);
    }

    public function testSetOwner()
    {
        $repo = $this->createMock(EntityRepository::class);
        $this->registry->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);

        $user = new CustomerUser();
        $repo->method('find')->with(1)->willReturn($user);
        $shoppingList = new ShoppingList();
        $lineItem1 = new LineItem();
        $lineItem2 = new LineItem();
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);

        $this->ownerChecker->expects($this->once())
            ->method('isOwnerCanBeSet')
            ->with($shoppingList)
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->with(ShoppingList::class)->willReturn($em);
        $em->expects($this->once())->method('flush');

        $this->manager->setOwner(1, $shoppingList);
        $this->assertSame($user, $shoppingList->getCustomerUser());
        $this->assertSame($user, $lineItem1->getCustomerUser());
        $this->assertSame($user, $lineItem1->getCustomerUser());
    }

    public function testSetSameOwner()
    {
        $repo = $this->createMock(EntityRepository::class);
        $this->registry->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);

        $user = new CustomerUser();
        $repo->method('find')->with(1)->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->with(ShoppingList::class)->willReturn($em);

        // if new owner is same as current owner don't run flush
        $em->expects($this->never())->method('flush');

        $this->ownerChecker->expects($this->never())
            ->method('isOwnerCanBeSet');

        $shoppingList = new ShoppingList();
        $shoppingList->setCustomerUser($user);
        $this->manager->setOwner(1, $shoppingList);
        $this->assertSame($user, $shoppingList->getCustomerUser());
    }

    public function testSetOwnerUserNotExists()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User with id=1 not exists');

        $repo = $this->createMock(EntityRepository::class);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);
        // user with requested id not exists
        $repo->method('find')->with(1)->willReturn(null);
        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->with(ShoppingList::class)->willReturn($em);

        // flush should not be called
        $em->expects($this->never())->method('flush');
        $this->ownerChecker->expects($this->never())
            ->method('isOwnerCanBeSet');

        $this->manager->setOwner(1, new ShoppingList());
    }

    public function testSetOwnerPermissionDenied()
    {
        $shoppingList = new ShoppingList();
        $repo = $this->createMock(EntityRepository::class);
        $this->registry->method('getRepository')
            ->with(CustomerUser::class)
            ->willReturn($repo);

        $user = new CustomerUser();
        $repo->method('find')->with(1)->willReturn($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->with(ShoppingList::class)->willReturn($em);

        $this->ownerChecker->expects($this->once())
            ->method('isOwnerCanBeSet')
            ->with($shoppingList)
            ->willReturn(false);

        // flush should not be called
        $em->expects($this->never())->method('flush');
        $this->expectException(AccessDeniedException::class);
        $this->manager->setOwner(1, $shoppingList);
    }
}
