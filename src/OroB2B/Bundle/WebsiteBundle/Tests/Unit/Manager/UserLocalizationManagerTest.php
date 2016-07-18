<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Manager;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;
use Oro\Bundle\UserBundle\Entity\BaseUserManager;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUserSettings;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\WebsiteBundle\Manager\UserLocalizationManager;
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
            ->willReturn(['L1', 'L2']);

        $this->localizationProvider->expects($this->once())
            ->method('getLocalizations')
            ->with(['L1', 'L2'])
            ->willReturn([(new Localization())->setLanguageCode('en')]);

        $this->assertEquals(
            [(new Localization())->setLanguageCode('en')],
            $this->localizationManager->getEnabledLocalizations()
        );
    }

    public function testGetDefaultLocalization()
    {
        $localization = $this->getEntity(Localization::class, ['id' => 1]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn($localization->getId());

        $this->localizationProvider->expects($this->once())
            ->method('getLocalization')
            ->with($localization->getId())
            ->willReturn($localization);

        $this->assertEquals(
            $localization,
            $this->localizationManager->getDefaultLocalization()
        );
    }

    public function testGetCurrentLocalization()
    {
        /** @var Localization $localization1 */
        $localization1 = $this->getEntity(Localization::class, ['id' => 1]);
        $localization2 = $this->getEntity(Localization::class, ['id' => 2]);

        /** @var Website $website **/
        $website = $this->getEntity(Website::class, ['id' => 1]);

        $userWebsiteSettings = new AccountUserSettings($website);
        $userWebsiteSettings->setLocalization($localization1);

        $user = $this->getMockBuilder('OroB2B\Bundle\AccountBundle\Entity\AccountUser')
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager->expects($this->never())
            ->method('getCurrentWebsite');
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
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
}
