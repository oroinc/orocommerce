<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\EventListener\SystemConfigListener;

class SystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var SystemConfigListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->listener = new SystemConfigListener($this->doctrine);
    }

    private function getEvent(array $settings): ConfigSettingsUpdateEvent
    {
        return new ConfigSettingsUpdateEvent($this->configManager, $settings);
    }

    /**
     * @dataProvider invalidSettingsDataProvider
     */
    public function testOnFormPreSetDataInvalidSettings(array $settings)
    {
        $event = $this->getEvent($settings);

        $this->doctrine->expects($this->never())
            ->method($this->anything());

        $this->listener->onFormPreSetData($event);
    }

    /**
     * @dataProvider invalidSettingsDataProvider
     */
    public function testOnSettingsSaveBeforeInvalidSettings(array $settings)
    {
        $event = $this->getEvent($settings);

        $this->doctrine->expects($this->never())
            ->method($this->anything());

        $this->listener->onSettingsSaveBefore($event);
    }

    public function invalidSettingsDataProvider(): array
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

        $webCatalog = $this->createMock(WebCatalog::class);

        $event = $this->getEvent([$key => ['value' => $id]]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(WebCatalog::class, $id)
            ->willReturn($webCatalog);

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(WebCatalog::class)
            ->willReturn($em);

        $this->listener->onFormPreSetData($event);

        $this->assertEquals([$key => ['value' => $webCatalog]], $event->getSettings());
    }

    public function testOnSettingsSaveBefore()
    {
        $id = 42;
        $webCatalog = $this->createMock(WebCatalog::class);
        $webCatalog->expects($this->once())
            ->method('getId')
            ->willReturn($id);

        $event = $this->getEvent(['value' => $webCatalog]);

        $this->listener->onSettingsSaveBefore($event);

        $this->assertEquals(['value' => $id], $event->getSettings());
    }
}
