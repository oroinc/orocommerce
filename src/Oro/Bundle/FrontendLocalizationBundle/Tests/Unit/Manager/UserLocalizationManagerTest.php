<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserLocalizationManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var Session|\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var BaseUserManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userManager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var UserLocalizationManager */
    private $userLocalizationManager;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->websiteManager = $this->getMockBuilder(WebsiteManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userManager = $this->getMockBuilder(BaseUserManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationManager = $this->getMockBuilder(LocalizationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userLocalizationManager = new UserLocalizationManager(
            $this->session,
            $this->tokenStorage,
            $this->configManager,
            $this->websiteManager,
            $this->userManager,
            $this->localizationManager
        );
    }

    public function testGetCurrentLocalizationAndDefaultWebsiteLocalization()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $localization = $this->getEntity(Localization::class, ['id' => 42]);

        $this->websiteManager->expects($this->once())->method('getCurrentWebsite')->willReturn($website);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn(new CustomerUser());

        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn($localization->getId());

        $this->localizationManager->expects($this->once())
            ->method('getLocalization')
            ->with($localization->getId())
            ->willReturn($localization);

        $this->localizationManager->expects($this->never())->method('getDefaultLocalization');

        $this->assertSame($localization, $this->userLocalizationManager->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationAndDefaultGlobalLocalization()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $localization1 = $this->getEntity(Localization::class, ['id' => 41]);
        $localization2 = $this->getEntity(Localization::class, ['id' => 42]);

        $this->websiteManager->expects($this->once())->method('getCurrentWebsite')->willReturn($website);

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())->method('getUser')->willReturn(new CustomerUser());

        $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn($localization1->getId());

        $this->localizationManager->expects($this->once())
            ->method('getLocalization')
            ->with($localization1->getId())
            ->willReturn(null);

        $this->localizationManager->expects($this->once())
            ->method('getDefaultLocalization')
            ->willReturn($localization2);

        $this->assertSame($localization2, $this->userLocalizationManager->getCurrentLocalization());
    }

    public function testGetEnabledLocalizations()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
            ->willReturn(['1', '2']);

        $localization = $this->getEntity(
            Localization::class,
            ['language' => $this->getEntity(Language::class, ['code' => 'en'])]
        );

        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->with(['1', '2'])
            ->willReturn([$localization]);

        $this->assertEquals(
            [$localization],
            $this->userLocalizationManager->getEnabledLocalizations()
        );
    }

    public function testGetCurrentLocalizationLoggedUser()
    {
        /** @var Localization $localization1 */
        $localization1 = $this->getEntity(Localization::class, ['id' => 1]);
        $localization2 = $this->getEntity(Localization::class, ['id' => 2]);

        /** @var Website $website **/
        $website = $this->getEntity(Website::class, ['id' => 1]);

        $userWebsiteSettings = new CustomerUserSettings($website);
        $userWebsiteSettings->setLocalization($localization1);

        $user = $this->getMockBuilder(CustomerUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $user->expects($this->once())
            ->method('getWebsiteSettings')
            ->with($website)
            ->willReturn($userWebsiteSettings);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
            ->willReturn([$localization1->getId(), $localization2->getId()]);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->with([$localization1->getId(), $localization2->getId()])
            ->willReturn([$localization1, $localization2]);

        $this->assertEquals(
            $userWebsiteSettings->getLocalization(),
            $this->userLocalizationManager->getCurrentLocalization($website)
        );
    }

    public function testGetCurrentLocalizationNotLoggedUserAndSessionWasStarted()
    {
        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 1]);
        /** @var Website $website **/
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $sessionLocalizations = [$website->getId() => $localization->getId(), 3 => 4];

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
            ->willReturn([$localization->getId(), 4]);
        $this->session->expects($this->once())
            ->method('isStarted')
            ->willReturn(true);
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserLocalizationManager::SESSION_LOCALIZATIONS)
            ->willReturn($sessionLocalizations);

        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->with([$localization->getId(), 4])
            ->willReturn([$localization->getId() => $localization]);

        $this->localizationManager->expects($this->once())
            ->method('getLocalization')
            ->with($localization->getId())
            ->willReturn($localization);

        $this->assertEquals($localization, $this->userLocalizationManager->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationNotLoggedUserAndSessionWasNotStarted()
    {
        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 1]);
        /** @var Website $website **/
        $website = $this->getEntity(Website::class, ['id' => 1]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(1);
        $this->session->expects($this->once())
            ->method('isStarted')
            ->willReturn(false);
        $this->session->expects($this->never())
            ->method('get');

        $this->localizationManager->expects($this->never())
            ->method('getLocalizations');
        $this->localizationManager->expects($this->once())
            ->method('getLocalization')
            ->with($localization->getId())
            ->willReturn($localization);

        $this->assertEquals($localization, $this->userLocalizationManager->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationNoWebsite()
    {
        $this->configManager->expects($this->never())
            ->method($this->anything());
        $this->assertNull($this->userLocalizationManager->getCurrentLocalization());
    }

    public function testSetCurrentLocalizationLoggedUser()
    {
        /** @var Localization $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 1]);
        /** @var Website $website **/
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $user = $this->getMockBuilder(CustomerUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $user->expects($this->once())
            ->method('setWebsiteSettings');
        $em = $this->createMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('flush');
        $this->userManager->expects($this->once())
            ->method('getStorageManager')
            ->willReturn($em);

        $this->userLocalizationManager->setCurrentLocalization($localization, $website);
    }

    public function testSetCurrentLocalizationNotLoggedUserAndSessionWasStarted()
    {
        /** @var Localization|\PHPUnit\Framework\MockObject\MockObject $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 1]);
        $sessionLocalizations = [2 => 3];
        /** @var Website $website **/
        $website = $this->getEntity(Website::class, ['id' => 4]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->session->expects($this->once())
            ->method('isStarted')
            ->willReturn(true);
        $this->session->expects($this->once())
            ->method('get')
            ->with(UserLocalizationManager::SESSION_LOCALIZATIONS)
            ->willReturn($sessionLocalizations);
        $this->session->expects($this->once())
            ->method('set')
            ->with(
                UserLocalizationManager::SESSION_LOCALIZATIONS,
                [2 => 3, $website->getId() => $localization->getId()]
            );

        $this->userLocalizationManager->setCurrentLocalization($localization);
    }

    public function testSetCurrentLocalizationNotLoggedUserAndSessionWasNotStarted()
    {
        /** @var Localization|\PHPUnit\Framework\MockObject\MockObject $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 1]);
        /** @var Website $website **/
        $website = $this->getEntity(Website::class, ['id' => 4]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
        $this->session->expects($this->once())
            ->method('isStarted')
            ->willReturn(false);
        $this->session->expects($this->never())
            ->method('get');
        $this->session->expects($this->never())
            ->method('set');

        $this->userLocalizationManager->setCurrentLocalization($localization);
    }
}
