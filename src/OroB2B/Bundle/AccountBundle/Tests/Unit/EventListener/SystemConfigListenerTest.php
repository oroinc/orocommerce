<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;

use OroB2B\Bundle\AccountBundle\EventListener\SystemConfigListener;

class SystemConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $userClass;

    /**
     * @var SystemConfigListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->userClass = 'Oro\Bundle\UserBundle\Entity\User';

        $this->listener = new SystemConfigListener($this->registry, $this->userClass);
    }

    /**
     * @dataProvider invalidSettingsDataProvider
     * @param mixed $settings
     */
    public function testOnFormPreSetDataInvalidSettings($settings)
    {
        $event = $this->getEvent($settings);

        $this->registry->expects($this->never())
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

        $this->registry->expects($this->never())
            ->method($this->anything());

        $this->listener->onSettingsSaveBefore($event);
    }

    /**
     * @return array
     */
    public function invalidSettingsDataProvider()
    {
        return [
            [null],
            [[]],
            [['a' => 'b']],
            [new \DateTime()]
        ];
    }

    public function testOnFormPreSetData()
    {
        $id = 1;
        $key = 'oro_b2b_account___default_account_owner';

        $user = $this->getMockBuilder($this->userClass)
            ->disableOriginalConstructor()
            ->getMock();

        $event = $this->getEvent([$key => ['value' => $id]]);
        $event->expects($this->once())
            ->method('setSettings')
            ->with([$key => ['value' => $user]]);

        $manager = $this->getMockBuilder('\Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->once())
            ->method('find')
            ->with($this->userClass, $id)
            ->will($this->returnValue($user));

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with($this->userClass)
            ->will($this->returnValue($manager));

        $this->listener->onFormPreSetData($event);
    }

    public function testOnSettingsSaveBefore()
    {
        $id = 1;
        $key = 'oro_b2b_account___default_account_owner';

        $user = $this->getMockBuilder($this->userClass)
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($id));

        $event = $this->getEvent([$key => ['value' => $user]]);
        $event->expects($this->once())
            ->method('setSettings')
            ->with([$key => ['value' => $id]]);

        $this->listener->onSettingsSaveBefore($event);
    }

    /**
     * @param mixed $settings
     * @return ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEvent($settings)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigSettingsUpdateEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getSettings')
            ->will($this->returnValue($settings));

        return $event;
    }
}
