<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PriceListRequestHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var PriceListRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var PriceListRequestHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->repository = $this->createMock(PriceListRepository::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->request = $this->createMock(Request::class);
        $this->request->expects(self::any())
            ->method('hasSession')
            ->willReturn(true);
        $this->request->expects(self::any())
            ->method('getSession')
            ->willReturn($this->session);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->handler = new PriceListRequestHandler(
            $this->requestStack,
            $this->doctrine,
            $this->aclHelper
        );
    }

    private function initEntityManager(): void
    {
        $this->doctrine->expects(self::any())
            ->method('getRepository')
            ->with(PriceList::class)
            ->willReturn($this->repository);
    }

    public function testGetPriceListWithoutRequest(): void
    {
        $priceList = $this->getPriceList(2);

        $this->initEntityManager();
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->repository->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('orderBy')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('setMaxResults')
            ->willReturn($qb);

        $this->aclHelper->expects(static::once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($priceList);

        self::assertSame($priceList, $this->handler->getPriceList());
    }

    public function testGetPriceList(): void
    {
        $priceList = $this->getPriceList(2);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->initEntityManager();
        $this->requestStack->expects(self::exactly(2))
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects(self::exactly(2))
            ->method('get')
            ->with(PriceListRequestHandler::PRICE_LIST_KEY)
            ->willReturn($priceList->getId());

        $this->repository->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('where')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('setParameter')
            ->willReturn($qb);

        $this->aclHelper->expects(static::once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn($priceList);

        $handler = $this->handler;
        self::assertSame($priceList, $handler->getPriceList());

        // cache
        self::assertSame($priceList, $handler->getPriceList());
    }

    public function testPriceListNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('PriceList not found');

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->initEntityManager();

        $this->repository->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('orderBy')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('setMaxResults')
            ->willReturn($qb);

        $this->aclHelper->expects(static::once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $query->expects(self::once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $this->handler->getPriceList();
    }

    public function testGetPriceListNotFound(): void
    {
        $priceList = $this->getPriceList(2);

        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);

        $this->initEntityManager();

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);
        $this->request->expects($this->once())
            ->method('get')
            ->with(PriceListRequestHandler::PRICE_LIST_KEY)
            ->willReturn($priceList->getId());

        $this->repository->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('where')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('setParameter')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('setMaxResults')
            ->willReturn($qb);

        $qb->expects(self::once())
            ->method('orderBy')
            ->willReturn($qb);

        $this->aclHelper->expects(static::exactly(2))
            ->method('apply')
            ->with($qb)
            ->willReturn($query);

        $query->expects(self::exactly(2))
            ->method('getOneOrNullResult')
            ->willReturnOnConsecutiveCalls(null, $priceList);

        self::assertSame($priceList, $this->handler->getPriceList());
    }

    public function testGetPriceListCurrenciesWithoutRequest(): void
    {
        $priceList = $this->getPriceList(2, ['USD']);
        $this->requestStack->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn(null);
        self::assertSame(['USD'], $this->handler->getPriceListSelectedCurrencies($priceList));
    }

    /**
     * @dataProvider getPriceListCurrenciesDataProvider
     */
    public function testGetPriceListCurrenciesWithRequest(
        mixed $paramValue,
        array $currencies = [],
        array $expected = []
    ): void {
        $this->requestStack->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY, null, $paramValue],
            ]);

        self::assertEquals(
            $expected,
            $this->handler->getPriceListSelectedCurrencies($this->getPriceList(42, $currencies))
        );
    }

    public function testGetPriceListCurrenciesWithSessionParam(): void
    {
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->session->expects(self::once())
            ->method('has')
            ->with(PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY)
            ->willReturn(true);

        $this->session->expects(self::once())
            ->method('get')
            ->with(PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY)
            ->willReturn('USD');

        self::assertEquals(
            ['USD'],
            $this->handler->getPriceListSelectedCurrencies($this->getPriceList(42, ['USD', 'EUR']))
        );
    }

    public function getPriceListCurrenciesDataProvider(): array
    {
        return [
            'no currencies on initial state' => [
                'paramValue' => null,
                'currencies' => ['USD', 'GBP', 'EUR'],
                'expected' => []
            ],
            'true returns all price list currencies with cast' => [
                'paramValue' => 'true',
                'currencies' => ['USD', 'EUR'],
                'expected' => ['EUR', 'USD']
            ],
            'true returns all price list currencies' => [
                'paramValue' => true,
                'currencies' => ['USD', 'EUR'],
                'expected' => ['EUR', 'USD']
            ],
            'false returns nothings with cast' => [
                'paramValue' => false,
                'currencies' => ['USD', 'EUR'],
                'expected' => []
            ],
            'false returns nothings' => [
                'paramValue' => 'false',
                'currencies' => ['USD', 'EUR'],
                'expected' => []
            ],
            'submit valid currency' => [
                'paramValue' => 'GBP',
                'currencies' => ['USD', 'GBP', 'EUR'],
                'expected' => ['GBP']
            ],
            'submit invalid currency' => [
                'paramValue' => ['UAH'],
                'currencies' => ['USD', 'EUR'],
                'expected' => []
            ],
        ];
    }

    public function testGetShowTierPricesWithoutRequest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        self::assertFalse($this->handler->getShowTierPrices());
    }

    /**
     * @dataProvider getGetShowTierPricesDataProvider
     */
    public function testGetShowTierPricesWithRequest(mixed $paramValue, bool $expected)
    {
        $this->requestStack->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [PriceListRequestHandler::TIER_PRICES_KEY, null, $paramValue],
            ]);

        self::assertEquals($expected, $this->handler->getShowTierPrices());
    }

    public function getGetShowTierPricesDataProvider(): array
    {
        return [
            [
                'paramValue' => true,
                'expected' => true
            ],
            [
                'paramValue' => false,
                'expected' => false
            ],
            [
                'paramValue' => 'true',
                'expected' => true
            ],
            [
                'paramValue' => 'false',
                'expected' => false
            ],
            [
                'paramValue' => 1,
                'expected' => true
            ],
            [
                'paramValue' => 0,
                'expected' => false
            ]
        ];
    }

    private function getPriceList(int $id, array $currencies = []): PriceList
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $id);
        $priceList->setCurrencies($currencies);

        return $priceList;
    }
}
