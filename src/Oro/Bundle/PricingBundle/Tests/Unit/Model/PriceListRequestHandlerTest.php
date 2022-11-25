<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
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
    private $registry;

    /** @var PriceListRequestHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->repository = $this->createMock(PriceListRepository::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->request = $this->createMock(Request::class);
        $this->request->expects($this->any())
            ->method('hasSession')
            ->willReturn(true);
        $this->request->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->handler = new PriceListRequestHandler($this->requestStack, $this->registry);
    }

    private function initEntityManager()
    {
        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);
    }

    public function testGetPriceListWithoutRequest()
    {
        $priceList = $this->getPriceList(2);

        $this->initEntityManager();
        $this->repository->expects($this->once())
            ->method('getDefault')
            ->willReturn($priceList);
        $this->repository->expects($this->never())
            ->method('find');

        $this->assertSame($priceList, $this->handler->getPriceList());
    }

    public function testGetPriceList()
    {
        $this->initEntityManager();
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $priceList = $this->getPriceList(2);

        $this->request->expects($this->exactly(2))
            ->method('get')
            ->with(PriceListRequestHandler::PRICE_LIST_KEY)
            ->willReturn($priceList->getId());

        $this->repository->expects($this->once())
            ->method('find')
            ->with($priceList->getId())
            ->willReturn($priceList);
        $this->repository->expects($this->never())
            ->method('getDefault');
        $handler = $this->handler;
        $this->assertSame($priceList, $handler->getPriceList());

        // cache
        $this->assertSame($priceList, $handler->getPriceList());
    }

    public function testDefaultPriceListNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Default PriceList not found');

        $this->initEntityManager();
        $this->repository->expects($this->once())
            ->method('getDefault')
            ->willReturn(null);
        $this->repository->expects($this->never())
            ->method('find');
        $this->handler->getPriceList();
    }

    public function testGetPriceListNotFound()
    {
        $priceList = $this->getPriceList(2);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);
        $this->request->expects($this->once())
            ->method('get')
            ->with(PriceListRequestHandler::PRICE_LIST_KEY)
            ->willReturn($priceList->getId());

        $this->initEntityManager();
        $this->repository->expects($this->once())
            ->method('find')
            ->with($priceList->getId())
            ->willReturn(null);
        $this->repository->expects($this->once())
            ->method('getDefault')
            ->willReturn($priceList);
        $this->assertSame($priceList, $this->handler->getPriceList());
    }

    public function testGetPriceListCurrenciesWithoutRequest()
    {
        $priceList = $this->getPriceList(2, ['USD']);
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $this->assertSame(['USD'], $this->handler->getPriceListSelectedCurrencies($priceList));
    }

    /**
     * @dataProvider getPriceListCurrenciesDataProvider
     */
    public function testGetPriceListCurrenciesWithRequest(
        mixed $paramValue,
        array $currencies = [],
        array $expected = []
    ) {
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY, null, $paramValue],
            ]);

        $this->assertEquals(
            $expected,
            $this->handler->getPriceListSelectedCurrencies($this->getPriceList(42, $currencies))
        );
    }

    /**
     * @dataProvider getPriceListKeysDataProvider
     */
    public function testGetPriceListCurrenciesWithRequestValueInvalid(mixed $paramValue)
    {
        $priceList = $this->getPriceList(2);

        $this->initEntityManager();
        $this->repository->expects($this->once())
            ->method('getDefault')
            ->willReturn($priceList);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->once())
            ->method('get')
            ->with(PriceListRequestHandler::PRICE_LIST_KEY)
            ->willReturn($paramValue);

        $this->assertSame($priceList, $this->handler->getPriceList());
    }

    public function getPriceListKeysDataProvider(): array
    {
        return [
            'passing boolean value should return null' => [true],
            'no decimals are allowed' => [5.5],
            'no strings are allowed' => ['string'],
            'even string which could be casted to int' => ['22'],
        ];
    }

    public function testGetPriceListCurrenciesWithSessionParam()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->session->expects($this->once())
            ->method('has')
            ->with(PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY)
            ->willReturn(true);

        $this->session->expects($this->once())
            ->method('get')
            ->with(PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY)
            ->willReturn('USD');

        $this->assertEquals(
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

    public function testGetShowTierPricesWithoutRequest()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->assertFalse($this->handler->getShowTierPrices());
    }

    /**
     * @dataProvider getGetShowTierPricesDataProvider
     */
    public function testGetShowTierPricesWithRequest(mixed $paramValue, bool $expected)
    {
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [PriceListRequestHandler::TIER_PRICES_KEY, null, $paramValue],
            ]);

        $this->assertEquals($expected, $this->handler->getShowTierPrices());
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
