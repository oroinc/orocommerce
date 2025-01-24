<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\Repository\QuoteRepository;
use Oro\Bundle\SaleBundle\EventListener\Datagrid\QuoteItemDatagridUserAccessListener;
use Oro\Bundle\SaleBundle\Provider\GuestQuoteAccessProvider;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class QuoteItemDatagridUserAccessListenerTest extends TestCase
{
    private TokenStorageInterface|MockObject $tokenStorage;
    private ManagerRegistry|MockObject $doctrine;
    private GuestQuoteAccessProvider|MockObject $guestQuoteAccessProvider;
    private EntityManagerInterface|MockObject $entityManager;
    private QuoteRepository|MockObject $repository;
    private QuoteItemDatagridUserAccessListener $listener;

    protected function setUp(): void
    {
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->guestQuoteAccessProvider = $this->createMock(GuestQuoteAccessProvider::class);

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(QuoteRepository::class);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(Quote::class)
            ->willReturn($this->entityManager);

        $this->entityManager->expects(self::any())
            ->method('getRepository')
            ->with(Quote::class)
            ->willReturn($this->repository);

        $this->listener = new QuoteItemDatagridUserAccessListener(
            $this->tokenStorage,
            $this->doctrine,
            $this->guestQuoteAccessProvider
        );
    }

    public function testOnResultBeforeQueryWithoutGuestAccessId(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $parameters = $this->createMock(ParameterBag::class);
        $qb = $this->createMock(QueryBuilder::class);

        $datagrid->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $parameters->expects(self::once())
            ->method('get')
            ->with('guest_access_id')
            ->willReturn(null);

        $this->expectException(AccessDeniedException::class);
        $event = new OrmResultBeforeQuery($datagrid, $qb);
        $this->listener->onResultBeforeQuery($event);
    }

    public function testOnResultBeforeQueryWithInvalidQuote(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $parameters = $this->createMock(ParameterBag::class);
        $qb = $this->createMock(QueryBuilder::class);

        $datagrid->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $parameters->expects(self::once())
            ->method('get')
            ->with('guest_access_id')
            ->willReturn('test_access_id');

        $this->repository->expects(self::once())
            ->method('getQuoteByGuestAccessId')
            ->with('test_access_id')
            ->willReturn(null);

        $this->expectException(AccessDeniedException::class);
        $event = new OrmResultBeforeQuery($datagrid, $qb);
        $this->listener->onResultBeforeQuery($event);
    }

    public function testOnResultBeforeQueryWithAnonymousUserAndGrantedAccess(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $parameters = $this->createMock(ParameterBag::class);
        $qb = $this->createMock(QueryBuilder::class);

        $datagrid->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $parameters->expects(self::once())
            ->method('get')
            ->with('guest_access_id')
            ->willReturn('test_access_id');

        $quote = $this->createMock(Quote::class);

        $this->repository->expects(self::once())
            ->method('getQuoteByGuestAccessId')
            ->with('test_access_id')
            ->willReturn($quote);

        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->guestQuoteAccessProvider->expects(self::once())
            ->method('isGranted')
            ->with($quote)
            ->willReturn(true);

        $qb->expects(self::never())
            ->method('andWhere');

        $event = new OrmResultBeforeQuery($datagrid, $qb);
        $this->listener->onResultBeforeQuery($event);
    }

    public function testOnResultBeforeQueryWithoutAnonymousUser(): void
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $parameters = $this->createMock(ParameterBag::class);
        $qb = $this->createMock(QueryBuilder::class);
        $dataSource = $this->createMock(OrmDatasource::class);

        $datagrid->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters);

        $parameters->expects(self::once())
            ->method('get')
            ->with('guest_access_id')
            ->willReturn('test_access_id');

        $quote = $this->createMock(Quote::class);

        $this->repository->expects(self::once())
            ->method('getQuoteByGuestAccessId')
            ->with('test_access_id')
            ->willReturn($quote);

        $token = $this->createMock(UsernamePasswordOrganizationToken::class);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->guestQuoteAccessProvider->expects($this->never())
            ->method('isGranted');

        $datagrid->expects(self::once())
            ->method('getDatasource')
            ->willReturn($dataSource);

        $dataSource->expects(self::once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('andWhere')
            ->with('1 = 0');

        $event = new OrmResultBeforeQuery($datagrid, $qb);
        $this->listener->onResultBeforeQuery($event);
    }
}
