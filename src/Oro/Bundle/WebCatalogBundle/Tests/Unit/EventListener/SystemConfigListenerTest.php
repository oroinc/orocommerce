<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\EventListener\SystemConfigListener;
use Oro\Component\Testing\Unit\EntityTrait;

class SystemConfigListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var SystemConfigListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);

        $this->listener = new SystemConfigListener($this->registry);
    }

    /**
     * @dataProvider invalidSettingsDataProvider
     * @param mixed $settings
     */
    public function testOnFormPreSetDataInvalidSettings($settings)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigSettingsUpdateEvent $event */
        $event = $this->getMockBuilder(ConfigSettingsUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getSettings')
            ->will($this->returnValue($settings));

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
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigSettingsUpdateEvent $event */
        $event = $this->getMockBuilder(ConfigSettingsUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getSettings')
            ->will($this->returnValue($settings));

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
            [['x' => 'y']],
            [new \stdClass()]
        ];
    }

    public function testOnFormPreSetData()
    {
        $id = 42;
        $key = 'oro_web_catalog___web_catalog';

        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => $id]);
        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigSettingsUpdateEvent $event */
        $event = $this->getMockBuilder(ConfigSettingsUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getSettings')
            ->will($this->returnValue([$key => ['value' => $id]]));
        $event->expects($this->once())
            ->method('setSettings')
            ->with([$key => ['value' => $webCatalog]]);

        $manager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->once())
            ->method('find')
            ->with(WebCatalog::class, $id)
            ->will($this->returnValue($webCatalog));

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(WebCatalog::class)
            ->will($this->returnValue($manager));

        $this->listener->onFormPreSetData($event);
    }

    public function testOnSettingsSaveBefore()
    {
        $id = 42;
        $key = 'oro_web_catalog.web_catalog';
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => $id]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigSettingsUpdateEvent $event */
        $event = $this->getMockBuilder(ConfigSettingsUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getSettings')
            ->will($this->returnValue([$key => ['value' => $webCatalog]]));

        $event->expects($this->once())
            ->method('setSettings')
            ->with([$key => ['value' => $id]]);

        $this->listener->onSettingsSaveBefore($event);
    }
}
