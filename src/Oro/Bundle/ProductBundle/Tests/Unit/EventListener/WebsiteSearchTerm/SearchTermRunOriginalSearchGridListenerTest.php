<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener\WebsiteSearchTerm;

// phpcs:disable Generic.Files.LineLength.TooLong
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerGroupRepository;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerRepository;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Tests\Unit\Fixtures\Entity\Customer as CustomerStub;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\SearchTermRunOriginalSearchGridListener;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultBefore;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteBundle\Tests\Unit\Stub\WebsiteStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchTermRunOriginalSearchGridListenerTest extends WebTestCase
{
    private RequestStack $requestStack;

    private WebsiteManager|MockObject $websiteManager;

    private LocalizationProviderInterface|MockObject $localizationProvider;

    private FrontendHelper|MockObject $frontendHelper;

    private ProductVisibilitySearchQueryModifier|MockObject $productVisibilitySearchQueryModifier;

    private CustomerUserRelationsProvider|MockObject $customerUserRelationsProvider;

    private SearchTermRunOriginalSearchGridListener $listener;

    private WebsiteRepository|MockObject $websiteRepo;

    private Localization|MockObject $localizationRepo;

    private CustomerRepository|MockObject $customerRepo;

    private CustomerGroupRepository|MockObject $customerGroupRepo;

    #[\Override]
    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->requestStack = new RequestStack();
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->localizationProvider = $this->createMock(LocalizationProviderInterface::class);
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->productVisibilitySearchQueryModifier = $this->createMock(ProductVisibilitySearchQueryModifier::class);
        $this->customerUserRelationsProvider = $this->createMock(CustomerUserRelationsProvider::class);

        $this->listener = new SearchTermRunOriginalSearchGridListener(
            $doctrine,
            $this->requestStack,
            $this->websiteManager,
            $this->localizationProvider,
            $this->frontendHelper,
            $this->productVisibilitySearchQueryModifier,
            $this->customerUserRelationsProvider
        );

        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('oro_website_search_terms_management');

        $this->websiteRepo = $this->createMock(WebsiteRepository::class);
        $this->localizationRepo = $this->createMock(LocalizationRepository::class);
        $this->customerRepo = $this->createMock(CustomerRepository::class);
        $this->customerGroupRepo = $this->createMock(CustomerGroupRepository::class);

        $doctrine
            ->method('getRepository')
            ->willReturnMap([
                [Website::class, null, $this->websiteRepo],
                [Localization::class, null, $this->localizationRepo],
                [Customer::class, null, $this->customerRepo],
                [CustomerGroup::class, null, $this->customerGroupRepo],
            ]);
    }

    public function testOnPreBuildWhenFeatureNotEnabled(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(false);

        $event
            ->expects(self::never())
            ->method('getConfig');

        $this->listener->onPreBuild($event);
    }

    public function testOnPreBuildWhenNoCurrentRequest(): void
    {
        $event = $this->createMock(PreBuild::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $event
            ->expects(self::never())
            ->method('getConfig');

        $this->listener->onPreBuild($event);
    }

    public function testOnPreBuildWhenNoParameters(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $datagridConfig = DatagridConfiguration::create([], new PropertyAccessor());
        $event
            ->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $website = new WebsiteStub(12);
        $this->websiteManager
            ->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn($website);

        $this->websiteManager
            ->expects(self::once())
            ->method('setCurrentWebsite')
            ->with($website);

        $customerGroup = new CustomerGroup();
        ReflectionUtil::setPropertyValue($customerGroup, 'id', 78);

        $this->customerUserRelationsProvider
            ->expects(self::once())
            ->method('getCustomerGroup')
            ->willReturn($customerGroup);

        $customer = (new CustomerStub())->setId(56);
        $this->customerRepo
            ->expects(self::once())
            ->method('getCustomerGroupFirstCustomer')
            ->with($customerGroup)
            ->willReturn($customer);

        $this->productVisibilitySearchQueryModifier
            ->expects(self::once())
            ->method('setCurrentCustomer')
            ->with($customer);

        $this->frontendHelper->emulateFrontendRequest();

        $this->listener->onPreBuild($event);

        self::assertEquals(
            [
                'options' => [
                    'urlParams' => [
                        'website' => '0',
                        'localization' => '0',
                        'customerGroup' => '0',
                        'customer' => '0',
                    ],
                ],
            ],
            $datagridConfig->toArray()
        );
    }

    public function testOnPreBuildWhenHasWebsite(): void
    {
        $request = new Request();
        $websiteId = 12;
        $request->query->set('website', $websiteId);
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $datagridConfig = DatagridConfiguration::create([], new PropertyAccessor());
        $event
            ->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $website = new WebsiteStub($websiteId);
        $this->websiteManager
            ->expects(self::never())
            ->method('getDefaultWebsite');

        $this->websiteRepo
            ->expects(self::once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);

        $this->websiteManager
            ->expects(self::once())
            ->method('setCurrentWebsite')
            ->with($website);

        $customerGroup = new CustomerGroup();
        ReflectionUtil::setPropertyValue($customerGroup, 'id', 78);

        $this->customerUserRelationsProvider
            ->expects(self::once())
            ->method('getCustomerGroup')
            ->willReturn($customerGroup);

        $customer = (new CustomerStub())->setId(56);
        $this->customerRepo
            ->expects(self::once())
            ->method('getCustomerGroupFirstCustomer')
            ->with($customerGroup)
            ->willReturn($customer);

        $this->productVisibilitySearchQueryModifier
            ->expects(self::once())
            ->method('setCurrentCustomer')
            ->with($customer);

        $this->frontendHelper->emulateFrontendRequest();

        $this->listener->onPreBuild($event);

        self::assertEquals(
            [
                'options' => [
                    'urlParams' => [
                        'website' => $websiteId,
                        'localization' => '0',
                        'customerGroup' => '0',
                        'customer' => '0',
                    ],
                ],
            ],
            $datagridConfig->toArray()
        );
    }

    public function testOnPreBuildWhenHasLocalization(): void
    {
        $request = new Request();
        $localizationId = 34;
        $request->query->set('localization', $localizationId);
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $datagridConfig = DatagridConfiguration::create([], new PropertyAccessor());
        $event
            ->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $website = new WebsiteStub(12);
        $this->websiteManager
            ->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn($website);

        $this->websiteManager
            ->expects(self::once())
            ->method('setCurrentWebsite')
            ->with($website);

        $localization = new LocalizationStub($localizationId);
        $this->localizationRepo
            ->expects(self::once())
            ->method('find')
            ->with($localizationId)
            ->willReturn($localization);

        $this->localizationProvider
            ->expects(self::once())
            ->method('setCurrentLocalization')
            ->with($localization);

        $customerGroup = new CustomerGroup();
        ReflectionUtil::setPropertyValue($customerGroup, 'id', 78);

        $this->customerUserRelationsProvider
            ->expects(self::once())
            ->method('getCustomerGroup')
            ->willReturn($customerGroup);

        $customer = (new CustomerStub())->setId(56);
        $this->customerRepo
            ->expects(self::once())
            ->method('getCustomerGroupFirstCustomer')
            ->with($customerGroup)
            ->willReturn($customer);

        $this->productVisibilitySearchQueryModifier
            ->expects(self::once())
            ->method('setCurrentCustomer')
            ->with($customer);

        $this->frontendHelper->emulateFrontendRequest();

        $this->listener->onPreBuild($event);

        self::assertEquals(
            [
                'options' => [
                    'urlParams' => [
                        'website' => '0',
                        'localization' => $localizationId,
                        'customerGroup' => '0',
                        'customer' => '0',
                    ],
                ],
            ],
            $datagridConfig->toArray()
        );
    }

    public function testOnPreBuildWhenHasCustomer(): void
    {
        $request = new Request();
        $customerId = 56;
        $request->query->set('customer', $customerId);
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $datagridConfig = DatagridConfiguration::create([], new PropertyAccessor());
        $event
            ->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $website = new WebsiteStub(12);
        $this->websiteManager
            ->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn($website);

        $this->websiteManager
            ->expects(self::once())
            ->method('setCurrentWebsite')
            ->with($website);

        $this->customerUserRelationsProvider
            ->expects(self::never())
            ->method('getCustomerGroup');

        $customer = (new CustomerStub())->setId($customerId);
        $this->customerRepo
            ->expects(self::once())
            ->method('find')
            ->with($customerId)
            ->willReturn($customer);

        $this->customerRepo
            ->expects(self::never())
            ->method('getCustomerGroupFirstCustomer');

        $this->productVisibilitySearchQueryModifier
            ->expects(self::once())
            ->method('setCurrentCustomer')
            ->with($customer);

        $this->frontendHelper->emulateFrontendRequest();

        $this->listener->onPreBuild($event);

        self::assertEquals(
            [
                'options' => [
                    'urlParams' => [
                        'website' => '0',
                        'localization' => '0',
                        'customerGroup' => '0',
                        'customer' => $customerId,
                    ],
                ],
            ],
            $datagridConfig->toArray()
        );
    }

    public function testOnPreBuildWhenHasCustomerGroup(): void
    {
        $request = new Request();
        $customerGroupId = 78;
        $request->query->set('customerGroup', $customerGroupId);
        $this->requestStack->push($request);

        $event = $this->createMock(PreBuild::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $datagridConfig = DatagridConfiguration::create([], new PropertyAccessor());
        $event
            ->expects(self::atLeastOnce())
            ->method('getConfig')
            ->willReturn($datagridConfig);

        $website = new WebsiteStub(12);
        $this->websiteManager
            ->expects(self::once())
            ->method('getDefaultWebsite')
            ->willReturn($website);

        $this->websiteManager
            ->expects(self::once())
            ->method('setCurrentWebsite')
            ->with($website);

        $customerGroup = new CustomerGroup();
        ReflectionUtil::setPropertyValue($customerGroup, 'id', $customerGroupId);

        $this->customerGroupRepo
            ->expects(self::once())
            ->method('find')
            ->with($customerGroupId)
            ->willReturn($customerGroup);

        $this->customerUserRelationsProvider
            ->expects(self::never())
            ->method('getCustomerGroup');

        $customer = (new CustomerStub())->setId(56);
        $this->customerRepo
            ->expects(self::once())
            ->method('getCustomerGroupFirstCustomer')
            ->with($customerGroup)
            ->willReturn($customer);

        $this->productVisibilitySearchQueryModifier
            ->expects(self::once())
            ->method('setCurrentCustomer')
            ->with($customer);

        $this->frontendHelper->emulateFrontendRequest();

        $this->listener->onPreBuild($event);

        self::assertEquals(
            [
                'options' => [
                    'urlParams' => [
                        'website' => '0',
                        'localization' => '0',
                        'customerGroup' => $customerGroupId,
                        'customer' => '0',
                    ],
                ],
            ],
            $datagridConfig->toArray()
        );
    }

    public function testOnSearchResultAfterWhenFeatureNotEnabled(): void
    {
        $event = $this->createMock(SearchResultAfter::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(false);

        $this->websiteManager
            ->expects(self::never())
            ->method(self::anything());

        $this->localizationProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->productVisibilitySearchQueryModifier
            ->expects(self::never())
            ->method(self::anything());

        $this->frontendHelper
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onSearchResultAfter($event);
    }

    public function testOnSearchResultBeforeWhenFeatureNotEnabled(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $event = $this->createMock(SearchResultBefore::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(false);

        $this->frontendHelper
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onSearchResultBefore($event);
    }

    public function testOnSearchResultBeforeWhenNoCurrentRequest(): void
    {
        $event = $this->createMock(SearchResultBefore::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->frontendHelper
            ->expects(self::never())
            ->method(self::anything());

        $this->listener->onSearchResultBefore($event);
    }

    public function testOnSearchResultBefore(): void
    {
        $request = new Request();
        $this->requestStack->push($request);

        $event = $this->createMock(SearchResultBefore::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->frontendHelper
            ->expects(self::once())
            ->method('emulateFrontendRequest');

        $this->listener->onSearchResultBefore($event);
    }

    public function testOnSearchResultAfterWithoutOriginalWebsiteAndLocalization(): void
    {
        $event = $this->createMock(SearchResultAfter::class);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->websiteManager
            ->expects(self::once())
            ->method('setCurrentWebsite')
            ->with(null);

        $this->localizationProvider
            ->expects(self::once())
            ->method('setCurrentLocalization')
            ->with(null);

        $this->productVisibilitySearchQueryModifier
            ->expects(self::once())
            ->method('setCurrentCustomer')
            ->with(null);

        $this->frontendHelper
            ->expects(self::once())
            ->method('resetRequestEmulation');

        $this->listener->onSearchResultAfter($event);
    }

    public function testOnSearchResultAfter(): void
    {
        $event = $this->createMock(SearchResultAfter::class);

        $website = new WebsiteStub(12);
        $localization = new LocalizationStub(34);

        $this->featureChecker
            ->expects(self::once())
            ->method('isFeatureEnabled')
            ->with('oro_website_search_terms_management')
            ->willReturn(true);

        $this->websiteManager
            ->expects(self::once())
            ->method('setCurrentWebsite')
            ->with($website);

        $this->localizationProvider
            ->expects(self::once())
            ->method('setCurrentLocalization')
            ->with($localization);

        $this->productVisibilitySearchQueryModifier
            ->expects(self::once())
            ->method('setCurrentCustomer')
            ->with(null);

        $this->frontendHelper
            ->expects(self::once())
            ->method('resetRequestEmulation');

        ReflectionUtil::setPropertyValue($this->listener, 'originalWebsite', $website);
        ReflectionUtil::setPropertyValue($this->listener, 'originalLocalization', $localization);

        $this->listener->onSearchResultAfter($event);
    }
}
