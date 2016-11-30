<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Provider\AccountUserRelationsProvider;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @SuppressWarnings(PHPMD)
 */
class PriceListRequestHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected $session;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PriceListTreeHandler
     */
    protected $priceListTreeHandler;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected $requestStack;

    /**
     * @var PriceListRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $em;

    /**
     * @var AccountUserRelationsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $relationsProvider;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->session = $this->getMock(SessionInterface::class);

        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListTreeHandler = $this->getMockBuilder(PriceListTreeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMock(Request::class);
        $this->request->expects($this->any())->method('getSession')->willReturn($this->session);
        $this->requestStack = $this->getMock(RequestStack::class);
        $this->requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->repository = $this->getMockBuilder(PriceListRepository::class)
            ->disableOriginalConstructor()->getMock();
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->relationsProvider = $this->getMockBuilder(AccountUserRelationsProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteManager = $this->getMockBuilder(WebsiteManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown()
    {
        unset(
            $this->session,
            $this->securityFacade,
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
        return new PriceListRequestHandler(
            $this->requestStack,
            $this->securityFacade,
            $this->priceListTreeHandler,
            $this->registry,
            $this->relationsProvider,
            $this->websiteManager
        );
    }

    public function testGetPriceListWithoutRequest()
    {
        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $priceList = $this->getPriceList(2);
        $this->repository->expects($this->once())
            ->method('getDefault')
            ->willReturn($priceList);

        $this->assertSame($priceList, $this->createHandler()->getPriceList());
    }

    public function testGetPriceListWithoutParam()
    {
        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $priceList = $this->getPriceList(2);

        $this->repository->expects($this->once())->method('getDefault')->willReturn($priceList);
        $this->repository->expects($this->never())->method('find');
        $this->assertSame($priceList, $this->createHandler()->getPriceList());
    }

    public function testGetPriceList()
    {
        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Default PriceList not found
     */
    public function testDefaultPriceListNotFound()
    {
        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $this->repository->expects($this->once())->method('getDefault')->willReturn(null);
        $this->repository->expects($this->never())->method('find');
        $this->createHandler()->getPriceList();
    }

    public function testGetPriceListNotFound()
    {
        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $priceList = $this->getPriceList(2);

        $this->request->expects($this->once())->method('get')->with(PriceListRequestHandler::PRICE_LIST_KEY)
            ->willReturn($priceList->getId());

        $this->repository->expects($this->once())->method('find')->with($priceList->getId())->willReturn(null);
        $this->repository->expects($this->once())->method('getDefault')->willReturn($priceList);
        $this->assertSame($priceList, $this->createHandler()->getPriceList());
    }

    /**
     * @dataProvider getPriceListByAccountForUserDataProvider
     *
     * @param int|null $accountId
     * @param int|null $websiteId
     * @param Account $expectedAccount
     */
    public function testGetPriceListByAccountForUser(
        $accountId,
        $websiteId,
        Account $expectedAccount = null
    ) {
        /** @var User $user */
        $user = $this->getEntity(User::class, 11);
        $repositoryMap = [PriceList::class, $this->repository];

        /** @var Website $website */
        $website = $this->getEntity(Website::class, 2);

        /** @var CombinedPriceList $priceList */
        $priceList = $this->getEntity(CombinedPriceList::class, 42);
        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->request->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    [PriceListRequestHandler::ACCOUNT_ID_KEY, null, false, $accountId],
                    [PriceListRequestHandler::WEBSITE_KEY, null, false, $websiteId]
                ]
            );

        if ($websiteId) {
            $websiteRepo = $this->getMockBuilder(EntityRepository::class)
                ->disableOriginalConstructor()->getMock();
            $websiteRepo->expects($this->once())
                ->method('find')
                ->with($websiteId)
                ->willReturn($website);

            $repositoryMap[] = [Website::class, $websiteRepo];
        } else {
            $this->websiteManager->expects($this->once())
                ->method('getDefaultWebsite')
                ->willReturn($website);
        }

        if ($accountId) {
            /** @var Account $expectedAccount */
            $expectedAccount = $this->getEntity(Account::class, $accountId);
            $accountRepo = $this->getMockBuilder(EntityRepository::class)
                ->disableOriginalConstructor()->getMock();
            $accountRepo->expects($this->once())
                ->method('find')
                ->with($accountId)
                ->willReturn($expectedAccount);

            $repositoryMap[] = [Account::class, $accountRepo];
        }

        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap($repositoryMap);
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($expectedAccount, $website)
            ->willReturn($priceList);

        $this->assertSame($priceList, $this->createHandler()->getPriceListByAccount());
    }

    /**
     * @return array
     */
    public function getPriceListByAccountForUserDataProvider()
    {
        return [
            'user, with account id, website' => [
                'accountId' => 1,
                'websiteId' => 1,
                'expectedAccount' => new Account(),
            ],
            'user, with account id, no website' => [
                'accountId' => 1,
                'websiteId' => null,
                'expectedAccount' => new Account(),
            ],
            'default price list' => [
                'accountId' => null,
                'websiteId' => null,
                'expectedAccount' => null,
            ]
        ];
    }

    /**
     * @dataProvider accountUserAccountDataProvider
     * @param AccountUser|null $user
     * @param Account|null $expectedAccount
     */
    public function testGetPriceListByAccountForAccountUser($user, $expectedAccount)
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity(CombinedPriceList::class, 42);
        $websiteId = 1;

        /** @var Website $website */
        $website = $this->getEntity(Website::class, $websiteId);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->relationsProvider->expects($this->once())
            ->method('getAccountIncludingEmpty')
            ->with($user)
            ->will($this->returnValue($expectedAccount));

        $this->priceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($expectedAccount, $website)
            ->willReturn($priceList);

        $this->assertSame($priceList, $this->createHandler()->getPriceListByAccount());
    }

    /**
     * @return array
     */
    public function accountUserAccountDataProvider()
    {
        return [
            [null, null],
            [null, new Account()],
            [new AccountUser(), new Account()]
        ];
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
        $this->request->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap(
                [
                    [PriceListRequestHandler::PRICE_LIST_CURRENCY_KEY, null, false, $paramValue],
                ]
            );

        $this->assertEquals(
            $expected,
            $this->createHandler()->getPriceListSelectedCurrencies($this->getPriceList(42, $currencies))
        );
    }

    public function testGetPriceListCurrenciesWithSessionParam()
    {
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
            'all currencies on initial state' => [null, ['USD', 'GBP', 'EUR'], ['EUR', 'GBP', 'USD']],
            'true returns all price list currencies with cast' => ['true', ['USD', 'EUR'], ['EUR', 'USD']],
            'true returns all price list currencies' => [true, ['USD', 'EUR'], ['EUR', 'USD']],
            'false returns nothings with cast' => [false, ['USD', 'EUR'], []],
            'false returns nothings' => ['false', ['USD', 'EUR'], []],
            'submit valid currency' => ['GBP', ['USD', 'GBP', 'EUR'], ['GBP']],
            'submit invalid currency' => [['USD', 'UAH'], ['USD', 'EUR'], ['USD']],
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
        $this->request->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap(
                [
                    [PriceListRequestHandler::TIER_PRICES_KEY, null, false, $paramValue],
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
            [true, true],
            [false, false],
            ['true', true],
            ['false', false],
            [1, true],
            [0, false]
        ];
    }

    /**
     * @param int $id
     * @param array $currencies
     * @return PriceList
     */
    protected function getPriceList($id, array $currencies = [])
    {
        $priceList = new PriceList();
        $reflection = new \ReflectionProperty(get_class($priceList), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($priceList, $id);

        $priceList->setCurrencies($currencies);

        return $priceList;
    }

    /**
     * @return array
     */
    public function priceListIdDataProvider()
    {
        return [
            [true, false],
            [false, false],
            ['true', false],
            ['false', false],
            [2, true],
            [1, true],
            [0, false],
            [-1, false],
            ['2', true],
            ['1', true],
            ['0', false],
            ['-1', false],
        ];
    }

    /**
     * @param string $class
     * @param int $id
     * @return object
     */
    protected function getEntity($class, $id)
    {
        $entity = new $class();
        $reflection = new \ReflectionProperty(get_class($entity), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);

        return $entity;
    }
}
