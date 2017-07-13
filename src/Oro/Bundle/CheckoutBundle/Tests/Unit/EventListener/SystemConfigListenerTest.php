<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Configuration;
use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;
use Oro\Bundle\CheckoutBundle\EventListener\SystemConfigListener;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Utils\TreeUtils;
use Oro\Bundle\UserBundle\Entity\User;

class SystemConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var SystemConfigListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->registry   = $this->createMock(ManagerRegistry::class);
        $this->listener   = new SystemConfigListener($this->registry);
    }

    /**
     * @dataProvider invalidSettingsDataProvider
     * @param mixed $settings
     */
    public function testOnFormPreSetDataInvalidSettings($settings)
    {
        $this->registry->expects($this->never())
            ->method($this->anything());
        $event = $this->getEvent($settings);

        $this->listener->onFormPreSetData($event);
    }

    /**
     * @dataProvider invalidSettingsDataProvider
     * @param mixed $settings
     */
    public function testOnSettingsSaveBeforeInvalidSettings($settings)
    {
        $event = $this->getEvent($settings);
        $settingsKey = TreeUtils::getConfigKey(
            OroCheckoutExtension::ALIAS,
            Configuration::DEFAULT_GUEST_CHECKOUT_OWNER,
            ConfigManager::SECTION_VIEW_SEPARATOR
        );
        $this->listener->onSettingsSaveBefore($event);

        $this->assertFalse(isset($event->getSettings()[$settingsKey]['value']));
    }

    /**
     * @return array
     */
    public function invalidSettingsDataProvider()
    {
        $key = TreeUtils::getConfigKey(
            OroCheckoutExtension::ALIAS,
            Configuration::DEFAULT_GUEST_CHECKOUT_OWNER,
            ConfigManager::SECTION_VIEW_SEPARATOR
        );

        return [
            [[]],
            [[null]],
            [['bar' => 'foo']],
            [[new \stdClass()]],
            [[$key => ['bar' => 'foo']]],
            [[$key => ['value' => null]]]
        ];
    }

    public function testOnFormPreSetData()
    {
        $id      = 1;
        $key     = TreeUtils::getConfigKey(
            OroCheckoutExtension::ALIAS,
            Configuration::DEFAULT_GUEST_CHECKOUT_OWNER,
            ConfigManager::SECTION_VIEW_SEPARATOR
        );
        $owner   = new User();
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->once())
            ->method('find')
            ->with(User::class, $id)
            ->will($this->returnValue($owner));
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(User::class)
            ->will($this->returnValue($manager));

        $event = $this->getEvent([$key => ['value' => $id]]);

        $this->listener->onFormPreSetData($event);

        $this->assertEquals([$key => ['value' => $owner]], $event->getSettings());
    }

    public function testOnSettingsSaveBeforeWithWrongInstance()
    {
        $settingsKey = TreeUtils::getConfigKey(
            OroCheckoutExtension::ALIAS,
            Configuration::DEFAULT_GUEST_CHECKOUT_OWNER,
            ConfigManager::SECTION_VIEW_SEPARATOR
        );
        $settings = [$settingsKey => ['value' => new \stdClass()]];
        $event = $this->getEvent($settings);

        $this->listener->onSettingsSaveBefore($event);

        $this->assertEquals(new \stdClass(), $event->getSettings()[$settingsKey]['value']);
    }

    public function testOnSettingsSaveBefore()
    {
        $id    = 1;
        $settingsKey = TreeUtils::getConfigKey(
            OroCheckoutExtension::ALIAS,
            Configuration::DEFAULT_GUEST_CHECKOUT_OWNER,
            ConfigManager::SECTION_VIEW_SEPARATOR
        );
        $owner = $this->createMock(User::class);
        $owner->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));
        $event = $this->getEvent([$settingsKey => ['value' => $owner]]);

        $this->listener->onSettingsSaveBefore($event);

        $this->assertEquals([$settingsKey => ['value' => $id]], $event->getSettings());
    }

    /**
     * @param array $settings
     * @return ConfigSettingsUpdateEvent
     */
    protected function getEvent(array $settings)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        return new ConfigSettingsUpdateEvent($configManager, $settings);
    }
}
