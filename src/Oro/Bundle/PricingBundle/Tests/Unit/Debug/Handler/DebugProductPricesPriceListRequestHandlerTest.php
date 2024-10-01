<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Debug\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Debug\Provider\CombinedPriceListActivationRulesProvider;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DebugProductPricesPriceListRequestHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var Request|MockObject */
    private $request;

    /** @var RequestStack|MockObject */
    private $requestStack;

    /** @var ManagerRegistry|MockObject */
    private $doctrine;

    private CombinedPriceListTreeHandler|MockObject $combinedPriceListTreeHandler;
    private CombinedPriceListActivationRulesProvider|MockObject $cplActivationRulesProvider;
    private CustomerUserRelationsProvider|MockObject $customerUserRelationsProvider;

    /** @var DebugProductPricesPriceListRequestHandler */
    private $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->request = $this->createMock(Request::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->combinedPriceListTreeHandler = $this->createMock(CombinedPriceListTreeHandler::class);
        $this->cplActivationRulesProvider = $this->createMock(CombinedPriceListActivationRulesProvider::class);
        $this->customerUserRelationsProvider = $this->createMock(CustomerUserRelationsProvider::class);

        $this->handler = new DebugProductPricesPriceListRequestHandler(
            $this->requestStack,
            $this->doctrine,
            $this->combinedPriceListTreeHandler,
            $this->cplActivationRulesProvider,
            $this->customerUserRelationsProvider
        );
    }

    public function testGetPriceListWithDate()
    {
        $websiteId = 1;
        $customerId = 10;
        $date = '2030-01-02 12:34:00';

        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 100]);
        $fullChainCpl = $this->getEntity(CombinedPriceList::class, ['id' => 40]);

        $website = $this->getEntity(Website::class, ['id' => $websiteId]);
        $customer = $this->getEntity(Customer::class, ['id' => $customerId]);

        $websiteRepo = $this->createMock(WebsiteRepository::class);
        $customerRepo = $this->createMock(CustomerRepository::class);
        $activationRuleRepo = $this->createMock(CombinedPriceListActivationRuleRepository::class);

        $this->cplActivationRulesProvider->expects($this->once())
            ->method('getFullChainCpl')
            ->with($customer, $website)
            ->willReturn($fullChainCpl);

        $websiteRepo->expects($this->atLeastOnce())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $customerRepo->expects($this->atLeastOnce())
            ->method('find')
            ->with($customerId)
            ->willReturn($customer);

        $rule = new CombinedPriceListActivationRule();
        $rule->setFullChainPriceList($fullChainCpl);
        $rule->setCombinedPriceList($cpl);

        $activationRuleRepo->expects($this->once())
            ->method('getActualRuleByCpl')
            ->with($fullChainCpl, $this->isInstanceOf(\DateTime::class))
            ->willReturn($rule);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Website::class, null, $websiteRepo],
                [Customer::class, null, $customerRepo],
                [CombinedPriceListActivationRule::class, null, $activationRuleRepo]
            ]);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::WEBSITE_KEY, null, $websiteId],
                [DebugProductPricesPriceListRequestHandler::CUSTOMER_KEY, null, $customerId],
                [DebugProductPricesPriceListRequestHandler::DATE_KEY, null, $date]
            ]);

        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        $this->combinedPriceListTreeHandler->expects($this->never())
            ->method('getPriceList');

        $this->assertSame($cpl, $this->handler->getPriceList());
    }

    public function testGetPriceListWithDateNoRule()
    {
        $websiteId = 1;
        $customerId = 10;
        $date = '2030-01-02 12:34:00';

        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 100]);
        $fullChainCpl = $this->getEntity(CombinedPriceList::class, ['id' => 40]);

        $website = $this->getEntity(Website::class, ['id' => $websiteId]);
        $customer = $this->getEntity(Customer::class, ['id' => $customerId]);

        $websiteRepo = $this->createMock(WebsiteRepository::class);
        $customerRepo = $this->createMock(CustomerRepository::class);
        $activationRuleRepo = $this->createMock(CombinedPriceListActivationRuleRepository::class);

        $this->cplActivationRulesProvider->expects($this->once())
            ->method('getFullChainCpl')
            ->with($customer, $website)
            ->willReturn($fullChainCpl);

        $websiteRepo->expects($this->atLeastOnce())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $customerRepo->expects($this->atLeastOnce())
            ->method('find')
            ->with($customerId)
            ->willReturn($customer);

        $rule = null;

        $activationRuleRepo->expects($this->once())
            ->method('getActualRuleByCpl')
            ->with($fullChainCpl, $this->isInstanceOf(\DateTime::class))
            ->willReturn($rule);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Website::class, null, $websiteRepo],
                [Customer::class, null, $customerRepo],
                [CombinedPriceListActivationRule::class, null, $activationRuleRepo]
            ]);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::WEBSITE_KEY, null, $websiteId],
                [DebugProductPricesPriceListRequestHandler::CUSTOMER_KEY, null, $customerId],
                [DebugProductPricesPriceListRequestHandler::DATE_KEY, null, $date]
            ]);

        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        $this->combinedPriceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn($cpl);

        $this->assertSame($cpl, $this->handler->getPriceList());
    }

    public function testGetPriceListWithDateNoFullCpl()
    {
        $websiteId = 1;
        $customerId = 10;
        $date = '2030-01-20 12:34:00';

        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 100]);

        [$customer, $website] = $this->assertCustomerAndWebsiteLoading($websiteId, $customerId, $date);

        $this->combinedPriceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn($cpl);

        $this->cplActivationRulesProvider->expects($this->once())
            ->method('getFullChainCpl')
            ->with($customer, $website)
            ->willReturn(null);

        $this->assertSame($cpl, $this->handler->getPriceList());
    }

    public function testGetFullChainCpl()
    {
        $websiteId = 1;
        $customerId = 10;
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 100]);

        [$customer, $website] = $this->assertCustomerAndWebsiteLoading($websiteId, $customerId);

        $this->cplActivationRulesProvider->expects($this->once())
            ->method('getFullChainCpl')
            ->with($customer, $website)
            ->willReturn($cpl);

        $this->assertSame($cpl, $this->handler->getFullChainCpl());
    }

    public function testGetPriceListNoDate()
    {
        $websiteId = 1;
        $customerId = 10;

        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 100]);

        [$customer, $website] = $this->assertCustomerAndWebsiteLoading($websiteId, $customerId);

        $this->combinedPriceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn($cpl);

        $this->assertSame($cpl, $this->handler->getPriceList());
    }

    public function testGetCurrentActivePriceListForExistingCustomer()
    {
        $websiteId = 1;
        $customerId = 10;

        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 100]);

        [$customer, $website] = $this->assertCustomerAndWebsiteLoading($websiteId, $customerId);

        $this->combinedPriceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn($cpl);

        $this->assertSame($cpl, $this->handler->getCurrentActivePriceList());
    }

    protected function assertCustomerAndWebsiteLoading(
        int $websiteId,
        int $customerId,
        ?string $date = null
    ): array {
        $website = $this->getEntity(Website::class, ['id' => $websiteId]);
        $customer = $this->getEntity(Customer::class, ['id' => $customerId]);

        $websiteRepo = $this->createMock(WebsiteRepository::class);
        $customerRepo = $this->createMock(CustomerRepository::class);

        $websiteRepo->expects($this->atLeastOnce())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $customerRepo->expects($this->atLeastOnce())
            ->method('find')
            ->with($customerId)
            ->willReturn($customer);

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Website::class, null, $websiteRepo],
                [Customer::class, null, $customerRepo]
            ]);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::WEBSITE_KEY, null, $websiteId],
                [DebugProductPricesPriceListRequestHandler::CUSTOMER_KEY, null, $customerId],
                [DebugProductPricesPriceListRequestHandler::DATE_KEY, null, $date],
            ]);

        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        return [$customer, $website];
    }


    public function testGetCurrentActivePriceListForAnonymous()
    {
        $websiteId = 1;
        $websiteRepo = $this->createMock(WebsiteRepository::class);
        $customerRepo = $this->createMock(CustomerRepository::class);

        $website = $this->getEntity(Website::class, ['id' => $websiteId]);
        $customer = $this->getEntity(Customer::class, ['id' => 50]);
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 100]);

        $websiteRepo->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $customerRepo->expects($this->never())
            ->method('find');

        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Website::class, null, $websiteRepo],
                [Customer::class, null, $customerRepo]
            ]);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::WEBSITE_KEY, null, $websiteId]
            ]);

        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        $this->combinedPriceListTreeHandler->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn($cpl);

        $this->customerUserRelationsProvider->expects($this->once())
            ->method('getCustomerIncludingEmpty')
            ->willReturn($customer);

        $this->assertSame($cpl, $this->handler->getCurrentActivePriceList());
    }

    public function testGetWebsiteWithoutRequest(): void
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);

        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn(null);

        $repo = $this->createMock(WebsiteRepository::class);
        $repo->expects($this->once())
            ->method('getDefaultWebsite')
            ->willReturn($website);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        self::assertEquals($website, $this->handler->getWebsite());
    }

    public function testGetWebsite()
    {
        $paramValue = 5;
        $website = $this->getEntity(Website::class, ['id' => $paramValue]);

        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::WEBSITE_KEY, null, $paramValue],
            ]);

        $repo = $this->createMock(WebsiteRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($paramValue)
            ->willReturn($website);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        self::assertEquals($website, $this->handler->getWebsite());
    }

    public function testGetCustomerWithoutRequest(): void
    {
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn(null);
        self::assertNull($this->handler->getCustomer());
    }

    public function testGetCustomer()
    {
        $paramValue = 5;
        $customer = $this->getEntity(Customer::class, ['id' => $paramValue]);

        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::CUSTOMER_KEY, null, $paramValue],
            ]);

        $repo = $this->createMock(CustomerRepository::class);
        $repo->expects($this->once())
            ->method('find')
            ->with($paramValue)
            ->willReturn($customer);
        $this->doctrine->expects($this->once())
            ->method('getRepository')
            ->willReturn($repo);

        self::assertEquals($customer, $this->handler->getCustomer());
    }

    public function testGetPriceListCurrenciesWithoutRequest(): void
    {
        $priceList = $this->getPriceList(2, ['USD']);
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
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
            ->method('getMainRequest')
            ->willReturn($this->request);

        $sortedCurrencies = $currencies;
        sort($sortedCurrencies);
        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::PRICE_LIST_CURRENCY_KEY, $sortedCurrencies, $paramValue],
            ]);

        self::assertEquals(
            $expected,
            $this->handler->getPriceListSelectedCurrencies($this->getPriceList(42, $currencies))
        );
    }

    public function getPriceListCurrenciesDataProvider(): array
    {
        return [
            'all currencies on initial state' => [
                'paramValue' => null,
                'currencies' => ['USD', 'GBP', 'EUR'],
                'expected' => []
            ],
            'submit valid currency' => [
                'paramValue' => ['GBP'],
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

    public function testGetSelectedDateWithoutRequest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn(null);

        self::assertNull($this->handler->getSelectedDate());
    }

    public function testGetSelectedDate()
    {
        $paramValue = '2023-02-03 12:00:00';
        $expected = new \DateTime($paramValue, new \DateTimeZone('UTC'));

        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::DATE_KEY, null, $paramValue],
            ]);

        self::assertEquals($expected, $this->handler->getSelectedDate());
    }

    public function testGetIncorrectSelectedDate()
    {
        $paramValue = 'undefined';

        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::DATE_KEY, null, $paramValue],
            ]);

        self::assertNull($this->handler->getSelectedDate());
    }

    public function testGetShowDetailedAssignmentInfoWithoutRequest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn(null);

        self::assertFalse($this->handler->getShowDetailedAssignmentInfo());
    }

    /**
     * @dataProvider requestBoolValueDataProvider
     */
    public function testGetShowDetailedAssignmentInfo(mixed $paramValue, bool $expected)
    {
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::DETAILED_ASSIGNMENTS_KEY, false, $paramValue],
            ]);

        self::assertEquals($expected, $this->handler->getShowDetailedAssignmentInfo());
    }

    public function testGetShowDevelopersInfoWithoutRequest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn(null);

        self::assertFalse($this->handler->getShowDevelopersInfo());
    }

    /**
     * @dataProvider requestBoolValueDataProvider
     */
    public function testGetShowDevelopersInfo(mixed $paramValue, bool $expected)
    {
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::SHOW_DEVELOPERS_INFO, false, $paramValue],
            ]);

        self::assertEquals($expected, $this->handler->getShowDevelopersInfo());
    }

    public function testGetShowTierPricesWithoutRequest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getMainRequest')
            ->willReturn(null);

        self::assertTrue($this->handler->getShowTierPrices());
    }

    /**
     * @dataProvider requestBoolValueDataProvider
     */
    public function testGetShowTierPricesWithRequest(mixed $paramValue, bool $expected)
    {
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);

        $this->request->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                [DebugProductPricesPriceListRequestHandler::TIER_PRICES_KEY, true, $paramValue],
            ]);

        self::assertEquals($expected, $this->handler->getShowTierPrices());
    }

    public function requestBoolValueDataProvider(): array
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

    private function getPriceList(int $id, array $currencies): BasePriceList
    {
        /** @var CombinedPriceList $priceList */
        $priceList = $this->getEntity(CombinedPriceList::class, ['id' => $id]);
        $priceList->setCurrencies($currencies);

        return $priceList;
    }
}
