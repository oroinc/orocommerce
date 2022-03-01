<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener\CombinedPriceListAssociation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToWebsite;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByConfigEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByWebsiteEvent;
use Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation\CollectAssociationWebsiteEventListener;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CollectAssociationWebsiteEventListenerTest extends TestCase
{
    use EntityTrait;

    private PriceListCollectionProvider|MockObject $collectionProvider;
    private CombinedPriceListProvider|MockObject $combinedPriceListProvider;
    private ManagerRegistry|MockObject $registry;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private CollectAssociationWebsiteEventListener $listener;

    protected function setUp(): void
    {
        $this->collectionProvider = $this->createMock(PriceListCollectionProvider::class);
        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new CollectAssociationWebsiteEventListener(
            $this->collectionProvider,
            $this->combinedPriceListProvider,
            $this->registry,
            $this->eventDispatcher
        );
    }

    public function testOnCollectAssociationsConfigLevelNoSelfFallback()
    {
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $website3 = $this->getEntity(Website::class, ['id' => 3]);
        /** @var CollectByConfigEvent|MockObject $event */
        $event = $this->createMock(CollectByConfigEvent::class);
        $event->expects($this->any())
            ->method('isIncludeSelfFallback')
            ->willReturn(false);

        $priceListToWebsiteRepository = $this->createMock(PriceListToWebsiteRepository::class);
        $priceListToWebsiteRepository->expects($this->once())
            ->method('getWebsiteIteratorWithDefaultFallback')
            ->willReturn([$website1]);

        $websiteRepository = $this->createMock(WebsiteRepository::class);
        $websiteRepository->expects($this->once())
            ->method('getWebsitesNotInList')
            ->with([1])
            ->willReturn(new \ArrayIterator([$website3]));
        $this->registry->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive(
                [PriceListToWebsite::class],
                [Website::class]
            )
            ->willReturnOnConsecutiveCalls(
                $priceListToWebsiteRepository,
                $websiteRepository
            );

        $websiteEvent1 = new CollectByWebsiteEvent($website1, false, true);
        $websiteEvent3 = new CollectByWebsiteEvent($website3, false, false);
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$websiteEvent1, $websiteEvent1::NAME],
                [$websiteEvent3, $websiteEvent3::NAME],
            );

        $event->expects($this->exactly(2))
            ->method('mergeAssociations')
            ->withConsecutive(
                [$websiteEvent1],
                [$websiteEvent3]
            );

        $this->listener->onCollectAssociationsConfigLevel($event);
    }

    public function testOnCollectAssociationsWebsiteLevelWithSelfFallback()
    {
        $website1 = $this->getEntity(Website::class, ['id' => 1]);
        $website2 = $this->getEntity(Website::class, ['id' => 2]);
        $website3 = $this->getEntity(Website::class, ['id' => 3]);
        /** @var CollectByConfigEvent|MockObject $event */
        $event = $this->createMock(CollectByConfigEvent::class);
        $event->expects($this->any())
            ->method('isIncludeSelfFallback')
            ->willReturn(true);

        $priceListToWebsiteRepository = $this->createMock(PriceListToWebsiteRepository::class);
        $priceListToWebsiteRepository->expects($this->once())
            ->method('getWebsiteIteratorWithDefaultFallback')
            ->willReturn([$website1]);
        $priceListToWebsiteRepository->expects($this->once())
            ->method('getWebsiteIteratorWithSelfFallback')
            ->willReturn([$website2]);

        $websiteRepository = $this->createMock(WebsiteRepository::class);
        $websiteRepository->expects($this->once())
            ->method('getWebsitesNotInList')
            ->with([1, 2])
            ->willReturn(new \ArrayIterator([$website3]));
        $this->registry->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive(
                [PriceListToWebsite::class],
                [Website::class]
            )
            ->willReturnOnConsecutiveCalls(
                $priceListToWebsiteRepository,
                $websiteRepository
            );

        $websiteEvent1 = new CollectByWebsiteEvent($website1, true, true);
        $websiteEvent2 = new CollectByWebsiteEvent($website2, true, true);
        $websiteEvent3 = new CollectByWebsiteEvent($website3, true, false);
        $this->eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$websiteEvent1, $websiteEvent1::NAME],
                [$websiteEvent2, $websiteEvent2::NAME],
                [$websiteEvent3, $websiteEvent3::NAME],
            );

        $event->expects($this->exactly(3))
            ->method('mergeAssociations')
            ->withConsecutive(
                [$websiteEvent1],
                [$websiteEvent2],
                [$websiteEvent3]
            );

        $this->listener->onCollectAssociationsConfigLevel($event);
    }

    public function testOnCollectAssociationsSkipCurrentLevel()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);

        $event = new CollectByWebsiteEvent($website, false, false);
        $this->collectionProvider->expects($this->never())
            ->method($this->anything());
        $this->combinedPriceListProvider->expects($this->never())
            ->method($this->anything());
        $this->listener->onCollectAssociations($event);
    }

    public function testOnCollectAssociations()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);

        $event = new CollectByWebsiteEvent($website);
        $collection = [
            new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true)
        ];
        $this->collectionProvider->expects($this->once())
            ->method('getPriceListsByWebsite')
            ->with($website)
            ->willReturn($collection);

        $collectionInfo = [
            'identifier' => 'abcdef',
            'elements' => [['p' => 1, 'm' => true]]
        ];
        $this->combinedPriceListProvider->expects($this->once())
            ->method('getCollectionInformation')
            ->with($collection)
            ->willReturn($collectionInfo);

        $this->listener->onCollectAssociations($event);
        $this->assertEquals(
            [
                'abcdef' => [
                    'collection' => [['p' => 1, 'm' => true]],
                    'assign_to' => [
                        'website' => ['ids' => [1]]
                    ]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );
    }
}
