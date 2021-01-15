<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\EventListener\SystemConfigListener;
use Oro\Component\Testing\Unit\EntityTrait;

class SystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var SystemConfigListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->listener = new SystemConfigListener($this->registry);
    }

    /**
     * @dataProvider invalidSettingsDataProvider
     * @param mixed $settings
     */
    public function testOnFormPreSetDataInvalidSettings($settings)
    {
        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

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
        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

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
            [[null]],
            [[]],
            [['x' => 'y']],
            [[new \stdClass()]]
        ];
    }

    public function testOnFormPreSetData()
    {
        $id = 42;
        $key = 'oro_web_catalog___web_catalog';

        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => $id]);

        $settings = [
            $key => ['value' => $id],
        ];

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

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

        $this->assertEquals([$key => ['value' => $webCatalog]], $event->getSettings());
    }

    public function testOnSettingsSaveBefore()
    {
        $id = 42;
        $webCatalog = $this->getEntity(WebCatalog::class, ['id' => $id]);

        $settings = [
            'value' => $webCatalog,
        ];

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);

        $this->listener->onSettingsSaveBefore($event);

        $this->assertEquals(['value' => $id], $event->getSettings());
    }
}
