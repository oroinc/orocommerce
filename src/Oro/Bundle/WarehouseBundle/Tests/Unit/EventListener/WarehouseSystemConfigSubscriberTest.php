<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\WarehouseBundle\EventListener\WarehouseSystemConfigSubscriber;
use Oro\Bundle\WarehouseBundle\SystemConfig\WarehouseConfigConverter;

class WarehouseSystemConfigSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WarehouseConfigConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $converter;

    /**
     * @var WarehouseSystemConfigSubscriber
     */
    protected $warehouseSystemConfigSubscriber;

    /**
     * @var ConfigSettingsUpdateEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    protected function setUp()
    {
        $this->event = $this
            ->getMockBuilder(ConfigSettingsUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->converter = $this
            ->getMockBuilder(WarehouseConfigConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->warehouseSystemConfigSubscriber = new WarehouseSystemConfigSubscriber($this->converter);
    }

    public function testFormPreSetNoSettings()
    {
        $this->event->expects($this->once())->method('getSettings')->willReturn(null);
        $this->event->expects($this->never())->method('setSettings');

        $this->warehouseSystemConfigSubscriber->formPreSet($this->event);
    }

    public function testFormPreSetEmptySettings()
    {
        $this->event->expects($this->once())->method('getSettings')->willReturn([]);
        $this->event->expects($this->never())->method('setSettings');

        $this->warehouseSystemConfigSubscriber->formPreSet($this->event);
    }

    public function testFormPreSet()
    {
        $this
            ->event
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn(['oro_warehouse___enabled_warehouses' => ['value' => [1]]]);
        $this->converter->expects($this->once())->method('convertFromSaved')->with([1])->willReturn(2);
        $this->event
            ->expects($this->once())
            ->method('setSettings')
            ->with(['oro_warehouse___enabled_warehouses' => ['value' => 2]]);

        $this->warehouseSystemConfigSubscriber->formPreSet($this->event);
    }

    public function testBeforeSaveNoSettings()
    {
        $this->event->expects($this->once())->method('getSettings')->willReturn(null);
        $this->event->expects($this->never())->method('setSettings');

        $this->warehouseSystemConfigSubscriber->beforeSave($this->event);
    }

    public function testBeforeSaveEmptySettings()
    {
        $this->event->expects($this->once())->method('getSettings')->willReturn([]);
        $this->event->expects($this->never())->method('setSettings');

        $this->warehouseSystemConfigSubscriber->beforeSave($this->event);
    }

    public function testBeforeSave()
    {
        $this
            ->event
            ->expects($this->once())
            ->method('getSettings')
            ->willReturn(['oro_warehouse.enabled_warehouses' => ['value' => [1]]]);
        $this->converter->expects($this->once())->method('convertBeforeSave')->with([1])->willReturn(2);
        $this->event
            ->expects($this->once())
            ->method('setSettings')
            ->with(['oro_warehouse.enabled_warehouses' => ['value' => 2]]);

        $this->warehouseSystemConfigSubscriber->beforeSave($this->event);
    }
}
