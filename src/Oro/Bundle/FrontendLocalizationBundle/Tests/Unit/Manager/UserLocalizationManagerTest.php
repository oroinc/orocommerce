<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\AccountUserSettings;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class UserLocalizationManagerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var Session|\PHPUnit_Framework_MockObject_MockObject */
    private $session;

    /** @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $tokenStorage;

    /** @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject */
    private $websiteManager;

    /** @var BaseUserManager|\PHPUnit_Framework_MockObject_MockObject */
    private $userManager;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var LocalizationProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $localizationProvider;

    /** @var UserLocalizationManager */
    private $localizationManager;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenStorage = $this->getMock(TokenStorageInterface::class);
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

        $this->localizationProvider = $this->getMockBuilder(LocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationManager = new UserLocalizationManager(
            $this->session,
            $this->tokenStorage,
            $this->configManager,
            $this->websiteManager,
            $this->userManager,
            $this->localizationProvider
        );
    }

    public function testGetEnabledLocalizations()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS))
            ->willReturn(['1', '2']);

        $this->localizationProvider->expects($this->once())
            ->method('getLocalizations')
            ->with(['1', '2'])
            ->willReturn([(new Localization())->setLanguageCode('en')]);

        $this->assertEquals(
            [(new Localization())->setLanguageCode('en')],
            $this->localizationManager->getEnabledLocalizations()
        );
    }

    public function testGetCurrentLocalizationLoggedUser()
    {
        /** @var Localization|\PHPUnit_Framework_MockObject_MockObject $localization1 */
        $localization1 = $this->getEntity(Localization::class, ['id' => 1]);
        $localization2 = $this->getEntity(Localization::class, ['id' => 2]);

        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website **/
        $website = $this->getEntity(Website::class, ['id' => 1]);

        $userWebsiteSettings = new AccountUserSettings($website);
        $userWebsiteSettings->setLocalization($localization1);

        $user = $this->getMockBuilder(AccountUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $token = $this->getMock(TokenInterface::class);
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

        $this->localizationProvider->expects($this->once())
            ->method('getLocalizations')
            ->with([$localization1->getId(), $localization2->getId()])
            ->willReturn([$localization1, $localization2]);

        $this->assertEquals(
            $userWebsiteSettings->getLocalization(),
            $this->localizationManager->getCurrentLocalization($website)
        );
    }

    public function testGetCurrentLocalizationNotLoggedUser()
    {
        /** @var Localization|\PHPUnit_Framework_MockObject_MockObject $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 1]);
        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website **/
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
            ->method('get')
            ->with(UserLocalizationManager::SESSION_LOCALIZATIONS)
            ->willReturn($sessionLocalizations);

        $this->localizationProvider->expects($this->once())
            ->method('getLocalizations')
            ->with([$localization->getId(), 4])
            ->willReturn([$localization]);

        $this->localizationProvider->expects($this->once())
            ->method('getLocalization')
            ->with($localization->getId())
            ->willReturn($localization);

        $this->assertEquals($localization, $this->localizationManager->getCurrentLocalization());
    }

    public function testGetCurrentLocalizationNoWebsite()
    {
        $this->configManager->expects($this->never())
            ->method($this->anything());
        $this->assertNull($this->localizationManager->getCurrentLocalization());
    }

    public function testSetCurrentLocalizationLoggedUser()
    {
        /** @var Localization|\PHPUnit_Framework_MockObject_MockObject $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 1]);
        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $website **/
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $user = $this->getMockBuilder(AccountUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $token = $this->getMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $user->expects($this->once())
            ->method('setWebsiteSettings');
        $em = $this->getMock(ObjectManager::class);
        $em->expects($this->once())
            ->method('flush');
        $this->userManager->expects($this->once())
            ->method('getStorageManager')
            ->willReturn($em);

        $this->localizationManager->setCurrentLocalization($localization, $website);
    }

    public function testSetCurrentLocalizationNotLoggedUser()
    {
        /** @var Localization|\PHPUnit_Framework_MockObject_MockObject $localization */
        $localization = $this->getEntity(Localization::class, ['id' => 1]);
        $sessionLocalizations = [2 => 3];
        /** @var Website $website **/
        $website = $this->getEntity(Website::class, ['id' => 4]);

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);
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

        $this->localizationManager->setCurrentLocalization($localization);
    }
}
