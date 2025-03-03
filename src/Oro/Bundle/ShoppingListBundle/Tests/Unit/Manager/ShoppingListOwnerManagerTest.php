<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListOwnerManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ShoppingListOwnerManagerTest extends TestCase
{
    private AclHelper&MockObject $aclHelper;
    private ManagerRegistry&MockObject $doctrine;
    private ShoppingListOwnerManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->manager = new ShoppingListOwnerManager($this->aclHelper, $this->doctrine);
    }

    private function getQueryBuilder(): QueryBuilder
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::any())
            ->method('select')
            ->willReturn($qb);
        $qb->expects(self::any())
            ->method('where')
            ->willReturn($qb);
        $qb->expects(self::any())
            ->method('setParameter')
            ->willReturn($qb);

        return $qb;
    }

    public function testSetOwner(): void
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

        $qb = $this->getQueryBuilder();
        $repo->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $queryWithCriteria = $this->createMock(AbstractQuery::class);
        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(
                $qb,
                'ASSIGN',
                [
                    'aclDisable' => true,
                    'availableOwnerEnable' => true,
                    'availableOwnerTargetEntityClass' => ShoppingList::class
                ]
            )
            ->willReturn($queryWithCriteria);
        $queryWithCriteria->expects(self::any())
            ->method('getOneOrNullResult')
            ->willReturn(1);

        $shoppingList = new ShoppingList();
        $lineItem1 = new LineItem();
        $lineItem2 = new LineItem();
        $shoppingList->addLineItem($lineItem1);
        $shoppingList->addLineItem($lineItem2);

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
        self::assertSame($user, $lineItem1->getCustomerUser());
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
        $this->manager->setOwner(1, new ShoppingList());
    }

    public function testSetOwnerPermissionDenied(): void
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

        $qb = $this->getQueryBuilder();
        $repo->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $queryWithCriteria = $this->createMock(AbstractQuery::class);
        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->willReturn($queryWithCriteria);
        $queryWithCriteria->expects(self::any())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        // flush should not be called
        $em->expects(self::never())
            ->method('flush');
        $this->expectException(AccessDeniedException::class);
        $this->manager->setOwner(1, new ShoppingList());
    }
}
