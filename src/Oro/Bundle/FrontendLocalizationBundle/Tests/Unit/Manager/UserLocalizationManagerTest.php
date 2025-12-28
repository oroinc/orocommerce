<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Request\ApiRequestHelper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UserLocalizationManagerTest extends TestCase
{
    private const CUSTOMER_USER_ID = 8;
    private const NOT_EXISTENT_LOCALIZATION_ID =  9;
    private const CURRENT_LOCALIZATION_ID =  9;
    private const ENABLED_LOCALIZATION_IDS = [3, 9];

    private RequestStack&MockObject $requestStack;
    private TokenStorageInterface&MockObject $tokenStorage;
    private ManagerRegistry&MockObject $doctrine;
    private WebsiteManager&MockObject $websiteManager;
    private ConfigManager&MockObject $configManager;
    private LocalizationManager&MockObject $localizationManager;
    private ApiRequestHelper&MockObject $apiRequestHelper;
    private Session&MockObject $session;
    private UserLocalizationManager $userLocalizationManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->apiRequestHelper = $this->createMock(ApiRequestHelper::class);
        $this->session = $this->createMock(Session::class);

        $this->requestStack->expects(self::any())
            ->method('getSession')
            ->willReturn($this->session);

        $this->userLocalizationManager = new UserLocalizationManager(
            $this->requestStack,
            $this->tokenStorage,
            $this->doctrine,
            $this->configManager,
            $this->websiteManager,
            $this->localizationManager,
            $this->apiRequestHelper
        );
    }

    private function getCustomerUser(int $id): CustomerUser
    {
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, $id);

        return $customerUser;
    }

    private function getWebsite(int $id): Website
    {
        $website = new Website();
        ReflectionUtil::setId($website, $id);

        return $website;
    }

    private function getLocalization(int $id, string $langCode = 'en_US'): Localization
    {
        $localization = new Localization();
        $language = new Language();
        $language->setCode($langCode);
        $localization->setLanguage($language);
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }

    private function mockRequest(bool $isApiRequest, bool $hasSession): void
    {
        $pathInfo = $isApiRequest ? '/api' : '/test';

        $request = $this->createMock(Request::class);
        $request->expects(self::any())
            ->method('getSession')
            ->willReturn($this->session);
        $request->expects(self::any())
            ->method('hasSession')
            ->willReturn($hasSession);
        $request->expects(self::any())
            ->method('getPathInfo')
            ->willReturn($pathInfo);

        $this->requestStack->expects(self::atLeastOnce())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->apiRequestHelper->expects(self::any())
            ->method('isApiRequest')
            ->with($pathInfo)
            ->willReturn($isApiRequest);
    }

    public function testGetCurrentLocalizationAndDefaultWebsiteLocalization(): void
    {
        $website = $this->getWebsite(1);
        $localization = $this->getLocalization(42);

        $this->mockRequest(false, false);

        $this->websiteManager->expects(self::exactly(2))
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn(new CustomerUser());

        $this->tokenStorage->expects(self::exactly(2))
            ->method('getToken')
            ->willReturn($token);

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
                    false,
                    false,
                    null,
                    $localization->getId()
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
                    false,
                    false,
                    null,
                    [$localization->getId()]
                ]
            ]);

        $this->localizationManager->expects(self::once())
            ->method('getLocalization')
            ->with($localization->getId())
            ->willReturn($localization);

        $this->localizationManager->expects(self::once())
            ->method('getLocalizations')
            ->with([$localization->getId()])
            ->willReturn([$localization]);

        $this->localizationManager->expects(self::never())
            ->method('getDefaultLocalization');

        self::assertSame($localization, $this->userLocalizationManager->getCurrentLocalization());

        // Checks local cache.
        self::assertSame($localization, $this->userLocalizationManager->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationAndDefaultGlobalLocalization(): void
    {
        $website = $this->getWebsite(1);
        $localization1 = $this->getLocalization(41);
        $localization2 = $this->getLocalization(42);

        $this->mockRequest(false, false);

        $this->websiteManager->expects(self::exactly(2))
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn(new CustomerUser());

        $this->tokenStorage->expects(self::exactly(2))
            ->method('getToken')
            ->willReturn($token);

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
                    false,
                    false,
                    null,
                    $localization1->getId()
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
                    false,
                    false,
                    null,
                    [$localization1->getId(), $localization2->getId()]
                ]
            ]);

        $this->localizationManager->expects(self::once())
            ->method('getLocalization')
            ->with($localization1->getId())
            ->willReturn(null);

        $this->localizationManager->expects(self::once())
            ->method('getLocalizations')
            ->with([$localization1->getId(), $localization2->getId()])
            ->willReturn([$localization1, $localization2]);

        $this->localizationManager->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn($localization2);

        self::assertSame($localization2, $this->userLocalizationManager->getCurrentLocalization());

        // Checks local cache.
        self::assertSame($localization2, $this->userLocalizationManager->getCurrentLocalization());
    }

    public function testGetEnabledLocalizations(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
            ->willReturn(['1', '2']);

        $language = new Language();
        $language->setCode('en');
        $localization = $this->getLocalization(42);
        $localization->setLanguage($language);

        $this->localizationManager->expects(self::once())
            ->method('getLocalizations')
            ->with(['1', '2'])
            ->willReturn([$localization]);

        self::assertEquals(
            [$localization],
            $this->userLocalizationManager->getEnabledLocalizations()
        );
    }

    public function testGetCurrentLocalizationLoggedUser(): void
    {
        $this->mockRequest(false, false);
        $localization1 = $this->getLocalization(1);
        $localization2 = $this->getLocalization(2);
        $website = $this->getWebsite(1);

        $userWebsiteSettings = new CustomerUserSettings($website);
        $userWebsiteSettings->setLocalization($localization1);

        $user = $this->createMock(CustomerUser::class);

        $this->websiteManager->expects(self::never())
            ->method('getCurrentWebsite');
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn($user);
        $user->expects(self::once())
            ->method('getWebsiteSettings')
            ->with($website)
            ->willReturn($userWebsiteSettings);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
            ->willReturn([$localization1->getId(), $localization2->getId()]);
        $this->tokenStorage->expects(self::exactly(2))
            ->method('getToken')
            ->willReturn($token);

        $this->localizationManager->expects(self::once())
            ->method('getLocalizations')
            ->with([$localization1->getId(), $localization2->getId()])
            ->willReturn([$localization1, $localization2]);

        self::assertEquals(
            $userWebsiteSettings->getLocalization(),
            $this->userLocalizationManager->getCurrentLocalization($website)
        );

        // Checks local cache.
        self::assertEquals(
            $userWebsiteSettings->getLocalization(),
            $this->userLocalizationManager->getCurrentLocalization($website)
        );
    }

    public function testGetCurrentLocalizationNotLoggedUserAndSessionWasStarted(): void
    {
        $localization = $this->getLocalization(1);
        $website = $this->getWebsite(1);
        $sessionLocalizations = [$website->getId() => $localization->getId(), 3 => 4];

        $this->websiteManager->expects(self::exactly(2))
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->configManager->expects(self::atLeastOnce())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
            ->willReturn([$localization->getId(), 4]);
        $this->session->expects(self::once())
            ->method('get')
            ->with(UserLocalizationManager::SESSION_LOCALIZATIONS)
            ->willReturn($sessionLocalizations);

        $this->mockRequest(false, true);

        $this->localizationManager->expects(self::once())
            ->method('getLocalizations')
            ->with([$localization->getId(), 4])
            ->willReturn([$localization->getId() => $localization]);

        self::assertEquals($localization, $this->userLocalizationManager->getCurrentLocalization());

        // Checks local cache.
        self::assertEquals($localization, $this->userLocalizationManager->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationNotLoggedUserAndSessionWasNotStarted(): void
    {
        $localization = $this->getLocalization(1);
        $website = $this->getWebsite(1);

        $this->websiteManager->expects(self::exactly(2))
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
                    false,
                    false,
                    null,
                    $localization->getId()
                ],
                [
                    Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
                    false,
                    false,
                    null,
                    [$localization->getId()]
                ]
            ]);
        $this->session->expects(self::never())
            ->method('get');

        $this->mockRequest(true, true);

        $this->localizationManager->expects(self::once())
            ->method('getLocalizations')
            ->with([$localization->getId()])
            ->willReturn([$localization->getId() => $localization]);

        $this->localizationManager->expects(self::once())
            ->method('getLocalization')
            ->with($localization->getId())
            ->willReturn($localization);

        self::assertEquals($localization, $this->userLocalizationManager->getCurrentLocalization());

        // Checks local cache.
        self::assertEquals($localization, $this->userLocalizationManager->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationNoWebsite(): void
    {
        $this->configManager->expects(self::never())
            ->method(self::anything());
        self::assertNull($this->userLocalizationManager->getCurrentLocalization());
    }

    public function testSetCurrentLocalizationLoggedUser(): void
    {
        $localization = $this->getLocalization(1);
        $website = $this->getWebsite(1);
        $user = $this->createMock(CustomerUser::class);

        $this->websiteManager->expects(self::never())
            ->method('getCurrentWebsite');
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);
        $user->expects(self::once())
            ->method('setWebsiteSettings');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('flush');
        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(CustomerUser::class)
            ->willReturn($em);

        $this->userLocalizationManager->setCurrentLocalization($localization, $website);
    }

    public function testGetCurrentLocalizationByCustomerUserWhenNoWebsiteGivenAndCustomerUserSettingsExist(): void
    {
        $localization = $this->getLocalization(self::CURRENT_LOCALIZATION_ID);
        $website = $this->getWebsite(1);
        $customerUser = $this->getCustomerUser(self::CUSTOMER_USER_ID);

        $customerUser->setWebsiteSettings((new CustomerUserSettings($website))->setLocalization($localization));

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
            ->willReturn(self::ENABLED_LOCALIZATION_IDS);

        $this->localizationManager->expects(self::once())
            ->method('getLocalizations')
            ->with(self::ENABLED_LOCALIZATION_IDS)
            ->willReturn([
                self::ENABLED_LOCALIZATION_IDS[0] => $this->getLocalization(self::ENABLED_LOCALIZATION_IDS[0]),
                self::ENABLED_LOCALIZATION_IDS[1] => $this->getLocalization(self::ENABLED_LOCALIZATION_IDS[1])
            ]);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        self::assertSame(
            $localization,
            $this->userLocalizationManager->getCurrentLocalizationByCustomerUser($customerUser)
        );
    }

    public function testGetCurrentLocalizationByCustomerUserWhenWebsiteGivenAndConfigurationLocalizationExists(): void
    {
        $localization = $this->getLocalization(self::CURRENT_LOCALIZATION_ID);
        $website = $this->getWebsite(1);
        $customerUser = $this->getCustomerUser(self::CUSTOMER_USER_ID);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION), false, false, $website)
            ->willReturn(self::CURRENT_LOCALIZATION_ID);

        $this->localizationManager->expects(self::once())
            ->method('getLocalization')
            ->with(self::CURRENT_LOCALIZATION_ID)
            ->willReturn($localization);

        $this->websiteManager->expects(self::never())
            ->method('getCurrentWebsite');

        self::assertSame(
            $localization,
            $this->userLocalizationManager->getCurrentLocalizationByCustomerUser($customerUser, $website)
        );
    }

    public function testGetCurrentLocalizationByCustomerUserWhenWebsiteGivenAndNoConfigurationLocalizationExists(): void
    {
        $localization = $this->getLocalization(self::CURRENT_LOCALIZATION_ID);
        $website = $this->getWebsite(1);
        $customerUser = $this->getCustomerUser(self::CUSTOMER_USER_ID);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION), false, false, $website)
            ->willReturn(self::NOT_EXISTENT_LOCALIZATION_ID);

        $this->localizationManager->expects(self::once())
            ->method('getLocalization')
            ->with(self::NOT_EXISTENT_LOCALIZATION_ID)
            ->willReturn(null);

        $this->localizationManager->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn($localization);

        $this->websiteManager->expects(self::never())
            ->method('getCurrentWebsite');

        self::assertSame(
            $localization,
            $this->userLocalizationManager->getCurrentLocalizationByCustomerUser($customerUser, $website)
        );
    }

    public function testSetCurrentLocalizationNotLoggedUser(): void
    {
        $localization = $this->getLocalization(1);
        $sessionLocalizations = [2 => 3];
        $website = $this->getWebsite(4);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->session->expects(self::once())
            ->method('get')
            ->with(UserLocalizationManager::SESSION_LOCALIZATIONS)
            ->willReturn($sessionLocalizations);
        $this->session->expects(self::once())
            ->method('set')
            ->with(
                UserLocalizationManager::SESSION_LOCALIZATIONS,
                [2 => 3, $website->getId() => $localization->getId()]
            );

        $request = $this->createMock(Request::class);
        $request->expects(self::exactly(2))
            ->method('getSession')
            ->willReturn($this->session);
        $request->expects(self::exactly(2))
            ->method('hasSession')
            ->willReturn(true);

        $this->requestStack->expects(self::exactly(2))
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->userLocalizationManager->setCurrentLocalization($localization);
    }
}
