<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener\CombinedPriceListAssociation;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\GetAssociatedWebsitesEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\ProcessEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation\ProcessAssociationConfigEventListener;
use Oro\Bundle\PricingBundle\Resolver\ActiveCombinedPriceListResolver;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ProcessAssociationConfigEventListenerTest extends TestCase
{
    use EntityTrait;

    private EventDispatcherInterface|MockObject $eventDispatcher;
    private ActiveCombinedPriceListResolver|MockObject $activeCombinedPriceListResolver;
    private ConfigManager|MockObject $configManager;
    private WebsiteProviderInterface|MockObject $websiteProvider;
    private ProcessAssociationConfigEventListener $listener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->activeCombinedPriceListResolver = $this->createMock(ActiveCombinedPriceListResolver::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);

        $this->listener = new ProcessAssociationConfigEventListener(
            $this->eventDispatcher,
            $this->activeCombinedPriceListResolver,
            $this->configManager,
            $this->websiteProvider
        );
    }

    public function testOnProcessAssociationsSkippedEvent()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $associations = ['website' => ['ids' => [1]]];
        $processEvent = new ProcessEvent($cpl, $associations, 100);

        $this->activeCombinedPriceListResolver->expects($this->never())
            ->method($this->anything());
        $this->configManager->expects($this->never())
            ->method($this->anything());
        $this->eventDispatcher->expects($this->never())
            ->method($this->anything());

        $this->listener->onProcessAssociations($processEvent);
    }

    /**
     * @dataProvider skipDataProvider
     */
    public function testOnProcessAssociationsSameCplChanged(bool $isSkipNotification)
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $associations = ['config' => true];
        $processEvent = new ProcessEvent($cpl, $associations, 100, $isSkipNotification);

        $this->activeCombinedPriceListResolver->expects($this->once())
            ->method('getActiveCplByFullCPL')
            ->with($cpl)
            ->willReturn($cpl);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_pricing.full_combined_price_list', false, false, null, null],
                ['oro_pricing.combined_price_list', false, false, null, null]
            ]);

        $this->configManager->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                ['oro_pricing.full_combined_price_list', 1],
                ['oro_pricing.combined_price_list', 1]
            );

        $this->configManager->expects($this->once())
            ->method('flush');

        $this->eventDispatcher->expects($isSkipNotification ? $this->never() : $this->once())
            ->method('dispatch')
            ->with(new ConfigCPLUpdateEvent(), ConfigCPLUpdateEvent::NAME);

        $this->listener->onProcessAssociations($processEvent);
    }

    /**
     * @dataProvider skipDataProvider
     */
    public function testOnProcessAssociationsActiveCplChanged(bool $isSkipNotification)
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        /** @var CombinedPriceList $cpl */
        $activeCpl = $this->getEntity(CombinedPriceList::class, ['id' => 2]);
        $associations = ['config' => true];
        $processEvent = new ProcessEvent($cpl, $associations, 100, $isSkipNotification);

        $this->activeCombinedPriceListResolver->expects($this->once())
            ->method('getActiveCplByFullCPL')
            ->with($cpl)
            ->willReturn($activeCpl);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_pricing.full_combined_price_list', false, false, null, 1],
                ['oro_pricing.combined_price_list', false, false, null, 1]
            ]);

        $this->configManager->expects($this->once())
            ->method('set')
            ->with('oro_pricing.combined_price_list', 2);

        $this->configManager->expects($this->once())
            ->method('flush');

        $this->eventDispatcher->expects($isSkipNotification ? $this->never() : $this->once())
            ->method('dispatch')
            ->with(new ConfigCPLUpdateEvent(), ConfigCPLUpdateEvent::NAME);

        $this->listener->onProcessAssociations($processEvent);
    }

    public function skipDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    public function testOnProcessAssociationsCplNotChanged()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $associations = ['config' => true];
        $processEvent = new ProcessEvent($cpl, $associations, 100);

        $this->activeCombinedPriceListResolver->expects($this->once())
            ->method('getActiveCplByFullCPL')
            ->with($cpl)
            ->willReturn($cpl);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_pricing.full_combined_price_list', false, false, null, 1],
                ['oro_pricing.combined_price_list', false, false, null, 1]
            ]);

        $this->configManager->expects($this->never())
            ->method('set');

        $this->configManager->expects($this->never())
            ->method('flush');

        $this->eventDispatcher->expects($this->never())
            ->method('dispatch');

        $this->listener->onProcessAssociations($processEvent);
    }

    public function testOnGetAssociatedWebsites()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 42]);

        $event = new GetAssociatedWebsitesEvent($cpl);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.combined_price_list')
            ->willReturn(1);

        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$website]);

        $this->listener->onGetAssociatedWebsites($event);
        $this->assertEquals([42 => $website], $event->getWebsites());
    }

    public function testOnGetAssociatedWebsitesNotConfig()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);

        $event = new GetAssociatedWebsitesEvent($cpl);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_pricing.combined_price_list')
            ->willReturn(10);

        $this->websiteProvider->expects($this->never())
            ->method('getWebsites');

        $this->listener->onGetAssociatedWebsites($event);
        $this->assertEmpty($event->getWebsites());
    }

    public function testOnGetAssociatedWebsitesWhenAssociationsProvided()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $associations = ['config' => true];
        $event = new GetAssociatedWebsitesEvent($cpl, $associations);
        $website = $this->getEntity(Website::class, ['id' => 42]);

        $this->websiteProvider->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$website]);

        $this->listener->onGetAssociatedWebsites($event);
        $this->assertEquals([42 => $website], $event->getWebsites());
    }

    public function testOnGetAssociatedWebsitesWhenAssociationsProvidedNotConfig()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $associations = ['website' => ['ids' => [1]]];
        $event = new GetAssociatedWebsitesEvent($cpl, $associations);

        $this->websiteProvider->expects($this->never())
            ->method('getWebsites');

        $this->listener->onGetAssociatedWebsites($event);
        $this->assertEmpty($event->getWebsites());
    }
}
