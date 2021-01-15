<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @SuppressWarnings(PHPMD)
 */
class PriceListRequestHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    protected $session;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TokenAccessorInterface
     */
    protected $tokenAccessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CombinedPriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Request
     */
    protected $request;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|RequestStack
     */
    protected $requestStack;

    /**
     * @var PriceListRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $repository;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $em;

    /**
     * @var CustomerUserRelationsProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $relationsProvider;

    /**
     * @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $websiteManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->priceListTreeHandler = $this->createMock(CombinedPriceListTreeHandler::class);

        $this->request = $this->createMock(Request::class);
        $this->request->expects($this->any())->method('getSession')->willReturn($this->session);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->repository = $this->createMock(PriceListRepository::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->relationsProvider = $this->createMock(CustomerUserRelationsProvider::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
    }

    protected function tearDown(): void
    {
        unset(
            $this->session,
            $this->tokenAccessor,
            $this->priceListTreeHandler,
            $this->handler,
            $this->request,
            $this->requestStack,
            $this->repository,
            $this->relationsProvider
        );
    }

    /**
     * @return PriceListRequestHandler
     */
    protected function createHandler()
    {
        return new PriceListRequestHandler($this->requestStack, $this->registry);
    }

    protected function initEm()
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

        $this->initEm();
        $this->repository->expects($this->once())
            ->method('getDefault')
            ->willReturn($priceList);
        $this->repository->expects($this->never())->method('find');

        $this->assertSame($priceList, $this->createHandler()->getPriceList());
    }

    public function testGetPriceList()
    {
        $this->initEm();
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $priceList = $this->getPriceList(2);

        $this->request->expects($this->exactly(2))->method('get')->with(PriceListRequestHandler::PRICE_LIST_KEY)
            ->willReturn($priceList->getId());

        $this->repository->expects($this->once())->method('find')->with($priceList->getId())->willReturn($priceList);
        $this->repository->expects($this->never())->method('getDefault');
        $handler = $this->createHandler();
        $this->assertSame($priceList, $handler->getPriceList());

        // cache
        $this->assertSame($priceList, $handler->getPriceList());
    }

    public function testDefaultPriceListNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Default PriceList not found');

        $this->initEm();
        $this->repository->expects($this->once())->method('getDefault')->willReturn(null);
        $this->repository->expects($this->never())->method('find');
        $this->createHandler()->getPriceList();
    }

    public function testGetPriceListNotFound()
    {
        $priceList = $this->getPriceList(2);

        $this->requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($this->request);
        $this->request->expects($this->once())->method('get')->with(PriceListRequestHandler::PRICE_LIST_KEY)
            ->willReturn($priceList->getId());

        $this->initEm();
        $this->repository->expects($this->once())->method('find')->with($priceList->getId())->willReturn(null);
        $this->repository->expects($this->once())->method('getDefault')->willReturn($priceList);
        $this->assertSame($priceList, $this->createHandler()->getPriceList());
    }

    public function testGetPriceListCurrenciesWithoutRequest()
    {
        $priceList = $this->getPriceList(2, ['USD']);
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn(null);
        $this->assertSame(['USD'], $this->createHandler()->getPriceListSelectedCurrencies($priceList));
    }

    /**
     * @dataProvider getPriceListCurrenciesDataProvider
     *
     * @param string $paramValue
     * @param array $currencies
     * @param array $expected
     */
    public function testGetPriceListCurrenciesWithRequest($paramValue, array $currencies = [], array $expected = [])
    {
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap(
                [
                    [PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY, null, $paramValue],
                ]
            );

        $this->assertEquals(
            $expected,
            $this->createHandler()->getPriceListSelectedCurrencies($this->getPriceList(42, $currencies))
        );
    }

    /**
     * @dataProvider getPriceListKeysDataProvider
     *
     * @param mixed $paramValue
     */
    public function testGetPriceListCurrenciesWithRequestValueInvalid($paramValue)
    {
        $priceList = $this->getPriceList(2);

        $this->initEm();
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

        $this->assertSame($priceList, $this->createHandler()->getPriceList());
    }

    /**
     * @return array
     */
    public function getPriceListKeysDataProvider()
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
            $this->createHandler()->getPriceListSelectedCurrencies($this->getPriceList(42, ['USD', 'EUR']))
        );
    }

    /**
     * @return array
     */
    public function getPriceListCurrenciesDataProvider()
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

        $this->assertFalse($this->createHandler()->getShowTierPrices());
    }

    /**
     * @dataProvider getGetShowTierPricesDataProvider
     *
     * @param mixed $paramValue
     * @param bool $expected
     */
    public function testGetShowTierPricesWithRequest($paramValue, $expected)
    {
        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->request->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap(
                [
                    [PriceListRequestHandler::TIER_PRICES_KEY, null, $paramValue],
                ]
            );

        $this->assertEquals($expected, $this->createHandler()->getShowTierPrices());
    }

    /**
     * @return array
     */
    public function getGetShowTierPricesDataProvider()
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

    /**
     * @param int $id
     * @param array $currencies
     * @return PriceList
     */
    protected function getPriceList($id, array $currencies = [])
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => $id]);
        $priceList->setCurrencies($currencies);

        return $priceList;
    }
}
