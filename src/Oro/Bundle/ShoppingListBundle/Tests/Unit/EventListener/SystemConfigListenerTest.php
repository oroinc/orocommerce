<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ShoppingListBundle\EventListener\SystemConfigListener;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\UserBundle\Entity\User;

class SystemConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var GuestShoppingListManager|\PHPUnit_Framework_MockObject_MockObject */
    private $guestShoppingListManager;

    /** @var SystemConfigListener */
    private $listener;

    /** @var ConfigManager */
    private $configManager;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->guestShoppingListManager = $this->getMockBuilder(GuestShoppingListManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new SystemConfigListener($this->guestShoppingListManager);
    }

    /**
     * @dataProvider invalidSettingsDataProvider
     * @param mixed $settings
     */
    public function testOnFormPreSetDataInvalidSettings($settings)
    {
        $event = $this->getEvent($settings);

        $this->guestShoppingListManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onFormPreSetData($event);
    }

    /**
     * @dataProvider invalidSettingsDataProvider
     * @param mixed $settings
     */
    public function testOnSettingsSaveBeforeInvalidSettings($settings)
    {
        $event = $this->getEvent($settings);

        $this->guestShoppingListManager->expects($this->never())
            ->method($this->anything());

        $this->listener->onSettingsSaveBefore($event);
    }

    /**
     * @return array
     */
    public function invalidSettingsDataProvider()
    {
        return [
            [[null]],
            [[]],
            [['a' => 'b']],
            [[new \DateTime()]],
        ];
    }

    public function testOnFormPreSetData()
    {
        $id = 1;
        $key = 'oro_shopping_list___default_guest_shopping_list_owner';


        $event = $this->getEvent([$key => ['value' => $id]]);

        $user = new User();
        $this->guestShoppingListManager->expects($this->once())
            ->method('getDefaultUser')
            ->with(1)
            ->willReturn($user);
        $this->listener->onFormPreSetData($event);

        $this->assertEquals([$key => ['value' => $user]], $event->getSettings());
    }

    public function testOnSettingsSaveBefore()
    {
        $user = new User();

        $event = $this->getEvent(['value' => $user]);

        $this->listener->onSettingsSaveBefore($event);

        $this->assertEquals(['value' => $user->getId()], $event->getSettings());
    }

    /**
     * @param array $settings
     * @return ConfigSettingsUpdateEvent
     */
    protected function getEvent(array $settings)
    {
        return new ConfigSettingsUpdateEvent($this->configManager, $settings);
    }
}
