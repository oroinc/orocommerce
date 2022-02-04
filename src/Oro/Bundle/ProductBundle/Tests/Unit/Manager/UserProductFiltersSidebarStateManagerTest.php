<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\CustomerBundle\Tests\Unit\Stub\CustomerUserStub;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Manager\UserProductFiltersSidebarStateManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteBundle\Tests\Unit\Stub\WebsiteStub;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UserProductFiltersSidebarStateManagerTest extends \PHPUnit\Framework\TestCase
{
    private SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session;

    private ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $managerRegistry;

    private EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    private TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $tokenAccessor;

    private WebsiteManager|\PHPUnit\Framework\MockObject\MockObject $websiteManager;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private UserProductFiltersSidebarStateManager $sidebarStateManager;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);

        $request = new Request();
        $request->setSession($this->session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(CustomerUser::class)
            ->willReturn($this->entityManager);

        $this->sidebarStateManager = new UserProductFiltersSidebarStateManager(
            $requestStack,
            $this->managerRegistry,
            $this->tokenAccessor,
            $this->websiteManager,
            $this->configManager
        );
    }

    public function testSetCurrentProductFiltersSidebarStateNoWebsite(): void
    {
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->tokenAccessor
            ->expects(self::never())
            ->method('getToken');

        $this->session
            ->expects(self::never())
            ->method('get');

        $this->sidebarStateManager->setCurrentProductFiltersSidebarState(true);
    }

    /**
     * @dataProvider getProductFiltersSidebarExpandedDataProvider
     */
    public function testSetCurrentProductFiltersSidebarStateForCustomerUserNoSettings(bool $isSidebarExpanded): void
    {
        $website = new WebsiteStub(1);
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $user = new CustomerUserStub(1);

        self::assertNull($user->getWebsiteSettings($website));

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->sidebarStateManager->setCurrentProductFiltersSidebarState($isSidebarExpanded);

        $customerUserSettings = $user->getWebsiteSettings($website);

        self::assertInstanceOf(CustomerUserSettings::class, $customerUserSettings);
        self::assertEquals($isSidebarExpanded, $customerUserSettings->isProductFiltersSidebarExpanded());
    }

    /**
     * @dataProvider getProductFiltersSidebarExpandedDataProvider
     */
    public function testSetCurrentProductFiltersSidebarStateForCustomerUser(bool $isSidebarExpanded): void
    {
        $website = new WebsiteStub(1);
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $userWebsiteSettings = new CustomerUserSettings($website);
        $user = (new CustomerUserStub(1))
            ->setWebsiteSettings($userWebsiteSettings);

        self::assertNull($user->getWebsiteSettings($website)->isProductFiltersSidebarExpanded());

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->entityManager->expects(self::once())
            ->method('flush');

        $this->sidebarStateManager->setCurrentProductFiltersSidebarState($isSidebarExpanded);

        self::assertEquals($isSidebarExpanded, $user->getWebsiteSettings($website)->isProductFiltersSidebarExpanded());
    }

    /**
     * @dataProvider getProductFiltersSidebarExpandedDataProvider
     */
    public function testSetCurrentProductFiltersSidebarStateForAnonNoSettings(bool $isSidebarExpanded): void
    {
        $website = new WebsiteStub(1);
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn('Anonymous Customer User');

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->managerRegistry
            ->expects(self::never())
            ->method('getManagerForClass');

        $this->session
            ->expects(self::once())
            ->method('get')
            ->with(
                'product_filters_sidebar_states_by_website',
                []
            )
            ->willReturn([]);
        $this->session
            ->expects(self::once())
            ->method('set')
            ->with(
                'product_filters_sidebar_states_by_website',
                [
                    $website->getId() => $isSidebarExpanded,
                ]
            );

        $this->sidebarStateManager->setCurrentProductFiltersSidebarState($isSidebarExpanded);
    }

    /**
     * @dataProvider getProductFiltersSidebarExpandedDataProvider
     */
    public function testSetCurrentProductFiltersSidebarStateForAnon(bool $isSidebarExpanded): void
    {
        $website = new WebsiteStub(1);
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn('Anonymous Customer User');

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->managerRegistry
            ->expects(self::never())
            ->method('getManagerForClass');

        $sessionProductFiltersSidebarStates = [
            $website->getId() => true,
        ];
        $this->session
            ->expects(self::once())
            ->method('get')
            ->with(
                'product_filters_sidebar_states_by_website',
                []
            )
            ->willReturn($sessionProductFiltersSidebarStates);
        $this->session
            ->expects(self::once())
            ->method('set')
            ->with(
                'product_filters_sidebar_states_by_website',
                [
                    $website->getId() => $isSidebarExpanded,
                ]
            );

        $this->sidebarStateManager->setCurrentProductFiltersSidebarState($isSidebarExpanded);
    }

    /**
     * @dataProvider getDefaultFiltersDisplaySettingsStateDataProvider
     */
    public function testIsProductFiltersSidebarExpandedNoWebsite(string $defaultState, bool $expectedResult): void
    {
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn(null);
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::FILTERS_DISPLAY_SETTINGS_STATE),
                false,
                false,
                null
            )
            ->willReturn($defaultState);

        self::assertEquals($expectedResult, $this->sidebarStateManager->isProductFiltersSidebarExpanded());
    }

    /**
     * @dataProvider getDefaultFiltersDisplaySettingsStateDataProvider
     */
    public function testIsProductFiltersSidebarExpandedCurrentWebsite(string $defaultState, bool $expectedResult): void
    {
        $website = new WebsiteStub(1);
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::FILTERS_DISPLAY_SETTINGS_STATE),
                false,
                false,
                $website
            )
            ->willReturn($defaultState);

        self::assertEquals($expectedResult, $this->sidebarStateManager->isProductFiltersSidebarExpanded());
    }

    /**
     * @dataProvider getDefaultFiltersDisplaySettingsStateDataProvider
     */
    public function testIsProductFiltersSidebarExpandedWebsite(string $defaultState, bool $expectedResult): void
    {
        $this->websiteManager
            ->expects(self::never())
            ->method('getCurrentWebsite');

        $website = new WebsiteStub(1);
        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::FILTERS_DISPLAY_SETTINGS_STATE),
                false,
                false,
                $website
            )
            ->willReturn($defaultState);

        self::assertEquals($expectedResult, $this->sidebarStateManager->isProductFiltersSidebarExpanded($website));
    }

    /**
     * @dataProvider getDefaultFiltersDisplaySettingsStateDataProvider
     */
    public function testIsProductFiltersSidebarExpandedForCustomerUserDefaultState(
        string $defaultState,
        bool $expectedResult
    ): void {
        $website = new WebsiteStub(1);
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $userWebsiteSettings = new CustomerUserSettings($website);
        $user = (new CustomerUserStub(1))
            ->setWebsiteSettings($userWebsiteSettings);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::FILTERS_DISPLAY_SETTINGS_STATE),
                false,
                false,
                $website
            )
            ->willReturn($defaultState);

        self::assertEquals($expectedResult, $this->sidebarStateManager->isProductFiltersSidebarExpanded());
    }

    /**
     * @dataProvider getDefaultFiltersDisplaySettingsStateDataProvider
     */
    public function testIsProductFiltersSidebarExpandedForAnonDefaultState(
        string $defaultState,
        bool $expectedResult
    ): void {
        $website = new WebsiteStub(1);
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn('Anonymous Customer User');

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->session
            ->expects(self::once())
            ->method('get')
            ->with(
                'product_filters_sidebar_states_by_website',
                []
            )
            ->willReturn([]);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(
                Configuration::getConfigKeyByName(Configuration::FILTERS_DISPLAY_SETTINGS_STATE),
                false,
                false,
                $website
            )
            ->willReturn($defaultState);

        self::assertEquals($expectedResult, $this->sidebarStateManager->isProductFiltersSidebarExpanded());
    }

    public function getDefaultFiltersDisplaySettingsStateDataProvider(): array
    {
        return [
            [
                'defaultState' => '',
                'expectedResult' => false,
            ],
            [
                'defaultState' => 'unknown state',
                'expectedResult' => false,
            ],
            [
                'defaultState' => Configuration::FILTERS_DISPLAY_SETTINGS_STATE_COLLAPSED,
                'expectedResult' => false,
            ],
            [
                'defaultState' => Configuration::FILTERS_DISPLAY_SETTINGS_STATE_EXPANDED,
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider getProductFiltersSidebarExpandedDataProvider
     */
    public function testIsProductFiltersSidebarExpandedForCustomerUser(bool $isSidebarExpanded): void
    {
        $website = new WebsiteStub(1);
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $userWebsiteSettings = (new CustomerUserSettings($website))
            ->setProductFiltersSidebarExpanded($isSidebarExpanded);

        $user = (new CustomerUserStub(1))
            ->setWebsiteSettings($userWebsiteSettings);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->configManager
            ->expects(self::never())
            ->method('get');

        self::assertEquals($isSidebarExpanded, $this->sidebarStateManager->isProductFiltersSidebarExpanded());
    }

    /**
     * @dataProvider getProductFiltersSidebarExpandedDataProvider
     */
    public function testIsProductFiltersSidebarExpandedForAnon(bool $isSidebarExpanded): void
    {
        $website = new WebsiteStub(1);
        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects(self::once())
            ->method('getUser')
            ->willReturn('Anonymous Customer User');

        $this->tokenAccessor
            ->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->session
            ->expects(self::once())
            ->method('get')
            ->with(
                'product_filters_sidebar_states_by_website',
                []
            )
            ->willReturn([$website->getId() => $isSidebarExpanded]);

        $this->configManager
            ->expects(self::never())
            ->method('get');

        self::assertEquals($isSidebarExpanded, $this->sidebarStateManager->isProductFiltersSidebarExpanded());
    }

    public function getProductFiltersSidebarExpandedDataProvider(): array
    {
        return [
            [
                'isSidebarExpanded' => false,
            ],
            [
                'isSidebarExpanded' => true,
            ],
        ];
    }
}
