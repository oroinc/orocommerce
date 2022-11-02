<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\CustomerBundle\Tests\Unit\Stub\CustomerUserStub;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\WebsiteBundle\Tests\Unit\Stub\WebsiteStub;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UserLocalizationManagerTest extends \PHPUnit\Framework\TestCase
{
    private const CUSTOMER_USER_ID = 8;
    private const NOT_EXISTENT_LOCALIZATION_ID =  9;
    private const CURRENT_LOCALIZATION_ID =  9;
    private const ENABLED_LOCALIZATION_IDS = [3, 9];

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private Session $session;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private TokenStorageInterface $tokenStorage;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private ManagerRegistry $doctrine;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private WebsiteManager $websiteManager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private ConfigManager $configManager;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private LocalizationManager $localizationManager;

    private UserLocalizationManager $userLocalizationManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->session = $this->createMock(Session::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $this->userLocalizationManager = new UserLocalizationManager(
            $this->session,
            $this->tokenStorage,
            $this->doctrine,
            $this->configManager,
            $this->websiteManager,
            $this->localizationManager
        );
    }

    public function testGetCurrentLocalizationAndDefaultWebsiteLocalization(): void
    {
        $website = new WebsiteStub(1);
        $localization = new LocalizationStub(42);

        $this->websiteManager->expects(self::atMost(2))->method('getCurrentWebsite')->willReturn($website);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::atMost(2))->method('getUser')->willReturn(new CustomerUser());

        $this->tokenStorage->expects(self::atMost(2))->method('getToken')->willReturn($token);

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    [
                        Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
                        false,
                        false,
                        null,
                        $localization->getId(),
                    ],
                    [
                        Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
                        false,
                        false,
                        null,
                        [$localization->getId()],
                    ],
                ]
            );

        $this->localizationManager->expects(self::once())
            ->method('getLocalization')
            ->with($localization->getId())
            ->willReturn($localization);

        $this->localizationManager->expects(self::once())
            ->method('getLocalizations')
            ->with([$localization->getId()])
            ->willReturn([$localization]);

        $this->localizationManager->expects(self::never())->method('getDefaultLocalization');

        self::assertSame($localization, $this->userLocalizationManager->getCurrentLocalization());

        // Checks local cache.
        self::assertSame($localization, $this->userLocalizationManager->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationAndDefaultGlobalLocalization(): void
    {
        $website = new WebsiteStub(1);
        $localization1 = new LocalizationStub(41);
        $localization2 = new LocalizationStub(42);

        $this->websiteManager->expects(self::atMost(2))->method('getCurrentWebsite')->willReturn($website);

        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::atMost(2))->method('getUser')->willReturn(new CustomerUser());

        $this->tokenStorage->expects(self::atMost(2))->method('getToken')->willReturn($token);

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    [
                        Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
                        false,
                        false,
                        null,
                        $localization1->getId(),
                    ],
                    [
                        Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
                        false,
                        false,
                        null,
                        [$localization1->getId(), $localization2->getId()],
                    ],
                ]
            );

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

        $language = (new Language())->setCode('en');
        $localization = (new LocalizationStub(42))->setLanguage($language);

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
        $localization1 = new LocalizationStub(1);
        $localization2 = new LocalizationStub(2);
        $website = new WebsiteStub(1);

        $userWebsiteSettings = new CustomerUserSettings($website);
        $userWebsiteSettings->setLocalization($localization1);

        $user = $this->createMock(CustomerUser::class);

        $this->websiteManager->expects(self::never())
            ->method('getCurrentWebsite');
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::atMost(2))
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
        $this->tokenStorage->expects(self::atMost(2))
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
        $localization = new LocalizationStub(1);
        $website = new WebsiteStub(1);
        $sessionLocalizations = [$website->getId() => $localization->getId(), 3 => 4];

        $this->websiteManager->expects(self::atMost(2))
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
            ->willReturn([$localization->getId(), 4]);
        $this->session->expects(self::once())
            ->method('isStarted')
            ->willReturn(true);
        $this->session->expects(self::once())
            ->method('get')
            ->with(UserLocalizationManager::SESSION_LOCALIZATIONS)
            ->willReturn($sessionLocalizations);

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
        $localization = new LocalizationStub(1);
        $website = new WebsiteStub(1);

        $this->websiteManager->expects(self::atMost(2))
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    [
                        Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
                        false,
                        false,
                        null,
                        $localization->getId(),
                    ],
                    [
                        Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
                        false,
                        false,
                        null,
                        [$localization->getId()],
                    ],
                ]
            );
        $this->session->expects(self::once())
            ->method('isStarted')
            ->willReturn(false);
        $this->session->expects(self::never())
            ->method('get');

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
        $localization = new LocalizationStub(1);
        $website = new WebsiteStub(1);
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
        $localization = new LocalizationStub(self::CURRENT_LOCALIZATION_ID);
        $website = new WebsiteStub(1);
        $customerUser = new CustomerUserStub(self::CUSTOMER_USER_ID);

        $customerUser->setWebsiteSettings((new CustomerUserSettings($website))->setLocalization($localization));

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
            ->willReturn(self::ENABLED_LOCALIZATION_IDS);

        $this->localizationManager
            ->expects(self::once())
            ->method('getLocalizations')
            ->with(self::ENABLED_LOCALIZATION_IDS)
            ->willReturn($this->getEnabledLocalizations());

        $this->websiteManager
            ->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        self::assertSame(
            $localization,
            $this->userLocalizationManager->getCurrentLocalizationByCustomerUser($customerUser)
        );
    }

    public function testGetCurrentLocalizationByCustomerUserWhenWebsiteGivenAndConfigurationLocalizationExists(): void
    {
        $localization = new LocalizationStub(self::CURRENT_LOCALIZATION_ID);
        $website = new WebsiteStub(1);
        $customerUser = new CustomerUserStub(self::CUSTOMER_USER_ID);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION), false, false, $website)
            ->willReturn(self::CURRENT_LOCALIZATION_ID);

        $this->localizationManager
            ->expects(self::once())
            ->method('getLocalization')
            ->with(self::CURRENT_LOCALIZATION_ID)
            ->willReturn($localization);

        $this->websiteManager
            ->expects(self::never())
            ->method('getCurrentWebsite');

        self::assertSame(
            $localization,
            $this->userLocalizationManager->getCurrentLocalizationByCustomerUser($customerUser, $website)
        );
    }

    public function testGetCurrentLocalizationByCustomerUserWhenWebsiteGivenAndNoConfigurationLocalizationExists(): void
    {
        $localization = new LocalizationStub(self::CURRENT_LOCALIZATION_ID);
        $website = new WebsiteStub(1);
        $customerUser = new CustomerUserStub(self::CUSTOMER_USER_ID);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION), false, false, $website)
            ->willReturn(self::NOT_EXISTENT_LOCALIZATION_ID);

        $this->localizationManager
            ->expects(self::once())
            ->method('getLocalization')
            ->with(self::NOT_EXISTENT_LOCALIZATION_ID)
            ->willReturn(null);

        $this->localizationManager
            ->expects(self::once())
            ->method('getDefaultLocalization')
            ->willReturn($localization);

        $this->websiteManager
            ->expects(self::never())
            ->method('getCurrentWebsite');

        self::assertSame(
            $localization,
            $this->userLocalizationManager->getCurrentLocalizationByCustomerUser($customerUser, $website)
        );
    }

    public function testSetCurrentLocalizationNotLoggedUser(): void
    {
        $localization = new LocalizationStub(1);
        $sessionLocalizations = [2 => 3];
        $website = new WebsiteStub(4);

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

        $this->userLocalizationManager->setCurrentLocalization($localization);
    }

    private function getEnabledLocalizations(): array
    {
        return [
            self::ENABLED_LOCALIZATION_IDS[0] => new LocalizationStub(self::ENABLED_LOCALIZATION_IDS[0]),
            self::ENABLED_LOCALIZATION_IDS[1] => new LocalizationStub(self::ENABLED_LOCALIZATION_IDS[1]),
        ];
    }
}
