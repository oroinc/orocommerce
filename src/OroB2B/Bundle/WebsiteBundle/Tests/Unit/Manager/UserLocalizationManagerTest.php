<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

use OroB2B\Bundle\WebsiteBundle\Manager\UserLocalizationManager;

class UserLocalizationManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var LocalizationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $localizationProvider;

    /** @var UserLocalizationManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationProvider = $this->getMockBuilder(LocalizationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new UserLocalizationManager($this->configManager, $this->localizationProvider);
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
            $this->manager->getEnabledLocalizations()
        );
    }

    public function testGetCurrentLocalization()
    {
        $localization = (new Localization())->setLanguageCode('en');

        $this->localizationProvider->expects($this->once())
            ->method('getLocalizations')
            ->willReturn([$localization]);

        $this->assertSame(
            $localization,
            $this->manager->getCurrentLocalization()
        );
    }
}
