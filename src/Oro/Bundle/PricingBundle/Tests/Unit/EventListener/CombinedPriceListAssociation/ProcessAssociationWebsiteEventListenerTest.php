<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener\CombinedPriceListAssociation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\GetAssociatedWebsitesEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\ProcessEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation\ProcessAssociationWebsiteEventListener;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Resolver\ActiveCombinedPriceListResolver;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ProcessAssociationWebsiteEventListenerTest extends TestCase
{
    use EntityTrait;

    private EventDispatcherInterface|MockObject $eventDispatcher;
    private ManagerRegistry|MockObject $registry;
    private ActiveCombinedPriceListResolver|MockObject $activeCombinedPriceListResolver;
    private CombinedPriceListTriggerHandler|MockObject $triggerHandler;
    private CombinedPriceListScheduleResolver|MockObject $scheduleResolver;
    private ProcessAssociationWebsiteEventListener $listener;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->activeCombinedPriceListResolver = $this->createMock(ActiveCombinedPriceListResolver::class);
        $this->triggerHandler = $this->createMock(CombinedPriceListTriggerHandler::class);
        $this->scheduleResolver = $this->createMock(CombinedPriceListScheduleResolver::class);

        $this->listener = new ProcessAssociationWebsiteEventListener(
            $this->eventDispatcher,
            $this->registry,
            $this->activeCombinedPriceListResolver,
            $this->triggerHandler,
        );
    }

    public function testOnProcessAssociationsSkippedEvent()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $associations = ['config' => true];
        $processEvent = new ProcessEvent($cpl, $associations, 100);

        $this->activeCombinedPriceListResolver->expects($this->never())
            ->method($this->anything());
        $this->eventDispatcher->expects($this->never())
            ->method($this->anything());

        $this->listener->onProcessAssociations($processEvent);
    }

    /**
     * @dataProvider skipDataProvider
     */
    public function testOnProcessAssociations(bool $isSkipNotifications)
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $website = $this->getEntity(Website::class, ['id' => 10]);
        $associations = ['website' => ['ids' => [10]]];
        $processEvent = new ProcessEvent($cpl, $associations, 100, $isSkipNotifications);

        $websiteRepo = $this->createMock(WebsiteRepository::class);
        $websiteRepo->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                return $this->getEntity(Website::class, ['id' => $id]);
            });

        $relation = new CombinedPriceListToWebsite();
        $relation->setFullChainPriceList($cpl);
        $relation->setPriceList($cpl);
        $relation->setWebsite($website);
        $cplRepo = $this->createMock(CombinedPriceListRepository::class);
        $cplRepo->expects($this->once())
            ->method('updateCombinedPriceListConnection')
            ->with($cpl, $cpl, $website, 100, null)
            ->willReturn($relation);
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Website::class, null, $websiteRepo],
                [CombinedPriceList::class, null, $cplRepo]
            ]);

        $this->activeCombinedPriceListResolver->expects($this->once())
            ->method('getActiveCplByFullCPL')
            ->with($cpl)
            ->willReturn($cpl);

        $this->eventDispatcher->expects($isSkipNotifications ? $this->never() : $this->once())
            ->method('dispatch')
            ->with(new WebsiteCPLUpdateEvent([10]), WebsiteCPLUpdateEvent::NAME);

        $this->listener->onProcessAssociations($processEvent);
    }

    public function skipDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    public function testOnProcessAssociationsWebsitesNotFound()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $associations = ['website' => ['ids' => [10]]];
        $processEvent = new ProcessEvent($cpl, $associations, 100);

        $websiteRepo = $this->createMock(WebsiteRepository::class);
        $websiteRepo->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [Website::class, null, $websiteRepo]
            ]);

        $this->activeCombinedPriceListResolver->expects($this->never())
            ->method('getActiveCplByFullCPL');

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

        $repo = $this->createMock(CombinedPriceListToWebsiteRepository::class);
        $repo->expects($this->once())
            ->method('getWebsitesByCombinedPriceList')
            ->with($cpl)
            ->willReturn([$website]);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(CombinedPriceListToWebsite::class)
            ->willReturn($repo);

        $this->listener->onGetAssociatedWebsites($event);
        $this->assertEquals([42 => $website], $event->getWebsites());
    }

    public function testOnGetAssociatedWebsitesWhenAssociationsProvided()
    {
        /** @var CombinedPriceList $cpl */
        $cpl = $this->getEntity(CombinedPriceList::class, ['id' => 1]);
        $associations = [
            'website' => [
                'ids' => [2, 5],
                'id:10' => [
                    'customer' => ['ids' => [100]]
                ]
            ]
        ];
        $event = new GetAssociatedWebsitesEvent($cpl, $associations);
        $website2 = $this->getEntity(Website::class, ['id' => 2]);
        $website5 = $this->getEntity(Website::class, ['id' => 5]);
        $website10 = $this->getEntity(Website::class, ['id' => 10]);

        $websiteRepo = $this->createMock(WebsiteRepository::class);
        $websiteRepo->expects($this->any())
            ->method('find')
            ->willReturnCallback(function ($id) {
                return $this->getEntity(Website::class, ['id' => $id]);
            });
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($websiteRepo);

        $this->listener->onGetAssociatedWebsites($event);
        $this->assertEquals([2 => $website2, 5 => $website5, 10 => $website10], $event->getWebsites());
    }
}
