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
use Oro\Component\Testing\ReflectionUtil;

class FlatPriceListSystemConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var PriceListRelationTriggerHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $triggerHandler;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var FlatPriceListSystemConfigListener */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->triggerHandler = $this->createMock(PriceListRelationTriggerHandlerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->listener = new FlatPriceListSystemConfigListener(
            $this->registry,
            $this->triggerHandler
        );
    }

    private function getPriceList(int $id): PriceList
    {
        $priceList = new PriceList();
        ReflectionUtil::setId($priceList, $id);

        return $priceList;
    }

    private function getWebsite(int $id): Website
    {
        $website = new Website();
        ReflectionUtil::setId($website, $id);

        return $website;
    }

    public function testOnFormPreSetData()
    {
        $priceList = $this->getPriceList(1);
        $settingsKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, ['oro_pricing', 'default_price_list']);
        $settings = [$settingsKey => ['value' => 1]];

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('find')
            ->with(PriceList::class, 1)
            ->willReturn($priceList);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(PriceList::class)
            ->willReturn($em);

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);
        $this->listener->onFormPreSetData($event);

        self::assertEquals([$settingsKey => ['value' => $priceList]], $event->getSettings());
    }

    /**
     * @dataProvider unsupportedSettingsDataProvider
     */
    public function testOnFormPreSetDataUnsupportedSettings(array $settings)
    {
        $this->registry->expects($this->never())
            ->method('getManagerForClass');

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);
        $this->listener->onFormPreSetData($event);

        self::assertEquals($settings, $event->getSettings());
    }

    public function unsupportedSettingsDataProvider(): array
    {
        $settingsKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, ['oro_pricing', 'default_price_list']);

        return [
            [[]],
            [[$settingsKey => []]],
            [[$settingsKey => ['a' => true]]],
            [[$settingsKey => ['value' => null]]],
        ];
    }

    public function testOnSettingsSaveBefore()
    {
        $priceList = $this->getPriceList(1);
        $settings = ['value' => $priceList];

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);
        $this->listener->onSettingsSaveBefore($event);

        self::assertEquals(['value' => 1], $event->getSettings());
    }

    /**
     * @dataProvider unsupportedSettingsDataProvider
     */
    public function testOnSettingsSaveBeforeUnsupported(array $settings)
    {
        $settingsKey = implode(ConfigManager::SECTION_VIEW_SEPARATOR, ['oro_pricing', 'default_price_list']);
        if (array_key_exists($settingsKey, $settings)) {
            $settings = $settings[$settingsKey];
        }

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);
        $this->listener->onSettingsSaveBefore($event);

        self::assertEquals($settings, $event->getSettings());
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

        $website = $this->getWebsite(1);
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
