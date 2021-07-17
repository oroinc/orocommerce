<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\EventListener\FlatPriceListSystemConfigListener;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandlerInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class FlatPriceListSystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var ManagerRegistry|MockObject
     */
    private $registry;

    /**
     * @var PriceListRelationTriggerHandlerInterface|MockObject
     */
    private $triggerHandler;

    /**
     * @var FlatPriceListSystemConfigListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->triggerHandler = $this->createMock(PriceListRelationTriggerHandlerInterface::class);

        $this->listener = new FlatPriceListSystemConfigListener(
            $this->registry,
            $this->triggerHandler
        );
    }

    public function testOnFormPreSetData()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $settingsKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, ['oro_pricing', 'default_price_list']);
        $settings = [$settingsKey => ['value' => 1]];

        $event = $this->createMock(ConfigSettingsUpdateEvent::class);
        $event->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 1)
            ->willReturn($priceList);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $event->expects($this->once())
            ->method('setSettings')
            ->with([$settingsKey => ['value' => $priceList]]);

        $this->listener->onFormPreSetData($event);
    }

    /**
     * @dataProvider unsupportedSettingsDataProvider
     * @param mixed $settings
     */
    public function testOnFormPreSetDataUnsupportedSettings($settings)
    {
        $event = $this->createMock(ConfigSettingsUpdateEvent::class);
        $event->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);

        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $event->expects($this->never())
            ->method('setSettings');

        $this->listener->onFormPreSetData($event);
    }

    public function unsupportedSettingsDataProvider(): array
    {
        $settingsKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, ['oro_pricing', 'default_price_list']);

        return [
            [null],
            [[]],
            [[$settingsKey => null]],
            [[$settingsKey => []]],
            [[$settingsKey => ['a' => true]]],
            [[$settingsKey => ['value' => null]]],
        ];
    }

    public function testOnSettingsSaveBefore()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $settings = ['value' => $priceList];

        $event = $this->createMock(ConfigSettingsUpdateEvent::class);
        $event->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);
        $event->expects($this->once())
            ->method('setSettings')
            ->with(['value' => 1]);

        $this->listener->onSettingsSaveBefore($event);
    }

    /**
     * @dataProvider unsupportedSettingsDataProvider
     */
    public function testOnSettingsSaveBeforeUnsupported($settings)
    {
        $settingsKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, ['oro_pricing', 'default_price_list']);
        if (is_array($settings) && array_key_exists($settingsKey, $settings)) {
            $settings = $settings[$settingsKey];
        }

        $event = $this->createMock(ConfigSettingsUpdateEvent::class);
        $event->expects($this->once())
            ->method('getSettings')
            ->willReturn($settings);
        $event->expects($this->never())
            ->method('setSettings');

        $this->listener->onSettingsSaveBefore($event);
    }

    public function testUpdateAfterWebsiteScope()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_pricing.default_price_list')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getScope')
            ->willReturn('website');
        $event->expects($this->atLeastOnce())
            ->method('getScopeId')
            ->willReturn(1);

        $website = $this->getEntity(Website::class, ['id' => 1]);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(Website::class, 1)
            ->willReturn($website);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($em);

        $this->triggerHandler->expects($this->once())
            ->method('handleWebsiteChange')
            ->with($website);

        $this->listener->updateAfter($event);
    }

    public function testUpdateAfterAppScope()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_pricing.default_price_list')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('getScope')
            ->willReturn('app');
        $event->expects($this->never())
            ->method('getScopeId');

        $this->triggerHandler->expects($this->once())
            ->method('handleConfigChange');

        $this->listener->updateAfter($event);
    }

    public function testUpdateAfterNoChanges()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);
        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_pricing.default_price_list')
            ->willReturn(false);

        $this->triggerHandler->expects($this->never())
            ->method($this->anything());

        $this->listener->updateAfter($event);
    }
}
