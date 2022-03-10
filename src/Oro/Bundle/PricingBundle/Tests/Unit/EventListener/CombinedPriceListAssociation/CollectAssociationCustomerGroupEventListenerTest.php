<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener\CombinedPriceListAssociation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\Repository\CustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByCustomerGroupEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByWebsiteEvent;
use Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation\CollectAssociationCustomerGroupEventListener;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CollectAssociationCustomerGroupEventListenerTest extends TestCase
{
    use EntityTrait;

    private PriceListCollectionProvider|MockObject $collectionProvider;
    private CombinedPriceListProvider|MockObject $combinedPriceListProvider;
    private ManagerRegistry|MockObject $registry;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private CollectAssociationCustomerGroupEventListener $listener;

    protected function setUp(): void
    {
        $this->collectionProvider = $this->createMock(PriceListCollectionProvider::class);
        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new CollectAssociationCustomerGroupEventListener(
            $this->collectionProvider,
            $this->combinedPriceListProvider,
            $this->registry,
            $this->eventDispatcher
        );
    }

    public function testOnCollectAssociationsWebsiteLevelNoSelfFallback()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        /** @var CollectByWebsiteEvent|MockObject $event */
        $event = $this->createMock(CollectByWebsiteEvent::class);
        $event->expects($this->any())
            ->method('getWebsite')
            ->willReturn($website);
        $event->expects($this->any())
            ->method('isIncludeSelfFallback')
            ->willReturn(false);

        $customerGroup1 = $this->getEntity(CustomerGroup::class, ['id' => 10]);
        $customerGroup3 = $this->getEntity(CustomerGroup::class, ['id' => 30]);
        $priceListToCustomerGroupRepository = $this->createMock(PriceListToCustomerGroupRepository::class);
        $priceListToCustomerGroupRepository->expects($this->once())
            ->method('getCustomerGroupIteratorWithDefaultFallback')
            ->with($website)
            ->willReturn([$customerGroup1]);

        $customerGroupRepository = $this->createMock(CustomerGroupRepository::class);
        $customerGroupRepository->expects($this->once())
            ->method('getCustomerGroupsNotInList')
            ->with([10])
            ->willReturn(new \ArrayIterator([$customerGroup3]));
        $this->registry->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive(
                [PriceListToCustomerGroup::class],
                [CustomerGroup::class]
            )
            ->willReturnOnConsecutiveCalls(
                $priceListToCustomerGroupRepository,
                $customerGroupRepository
            );

        $customerGroupEvent1 = new CollectByCustomerGroupEvent($website, $customerGroup1, false, true);
        $customerGroupEvent3 = new CollectByCustomerGroupEvent($website, $customerGroup3, false, false);
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$customerGroupEvent1, $customerGroupEvent1::NAME],
                [$customerGroupEvent3, $customerGroupEvent3::NAME],
            );

        $event->expects($this->exactly(2))
            ->method('mergeAssociations')
            ->withConsecutive(
                [$customerGroupEvent1],
                [$customerGroupEvent3]
            );

        $this->listener->onCollectAssociationsWebsiteLevel($event);
    }

    public function testOnCollectAssociationsWebsiteLevelWithSelfFallback()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        /** @var CollectByWebsiteEvent|MockObject $event */
        $event = $this->createMock(CollectByWebsiteEvent::class);
        $event->expects($this->any())
            ->method('getWebsite')
            ->willReturn($website);
        $event->expects($this->any())
            ->method('isIncludeSelfFallback')
            ->willReturn(true);

        $customerGroup1 = $this->getEntity(CustomerGroup::class, ['id' => 10]);
        $customerGroup2 = $this->getEntity(CustomerGroup::class, ['id' => 20]);
        $customerGroup3 = $this->getEntity(CustomerGroup::class, ['id' => 30]);
        $priceListToCustomerGroupRepository = $this->createMock(PriceListToCustomerGroupRepository::class);
        $priceListToCustomerGroupRepository->expects($this->once())
            ->method('getCustomerGroupIteratorWithDefaultFallback')
            ->with($website)
            ->willReturn([$customerGroup1]);
        $priceListToCustomerGroupRepository->expects($this->once())
            ->method('getCustomerGroupIteratorWithSelfFallback')
            ->with($website)
            ->willReturn([$customerGroup2]);

        $customerGroupRepository = $this->createMock(CustomerGroupRepository::class);
        $customerGroupRepository->expects($this->once())
            ->method('getCustomerGroupsNotInList')
            ->with([10, 20])
            ->willReturn(new \ArrayIterator([$customerGroup3]));
        $this->registry->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive(
                [PriceListToCustomerGroup::class],
                [CustomerGroup::class]
            )
            ->willReturnOnConsecutiveCalls(
                $priceListToCustomerGroupRepository,
                $customerGroupRepository
            );

        $customerGroupEvent1 = new CollectByCustomerGroupEvent($website, $customerGroup1, true, true);
        $customerGroupEvent2 = new CollectByCustomerGroupEvent($website, $customerGroup2, true, true);
        $customerGroupEvent3 = new CollectByCustomerGroupEvent($website, $customerGroup3, true, false);
        $this->eventDispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$customerGroupEvent1, $customerGroupEvent1::NAME],
                [$customerGroupEvent2, $customerGroupEvent2::NAME],
                [$customerGroupEvent3, $customerGroupEvent3::NAME],
            );

        $event->expects($this->exactly(3))
            ->method('mergeAssociations')
            ->withConsecutive(
                [$customerGroupEvent1],
                [$customerGroupEvent2],
                [$customerGroupEvent3]
            );

        $this->listener->onCollectAssociationsWebsiteLevel($event);
    }

    public function testOnCollectAssociationsSkipCurrentLevel()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 10]);

        $event = new CollectByCustomerGroupEvent($website, $customerGroup, false, false);
        $this->collectionProvider->expects($this->never())
            ->method($this->anything());
        $this->combinedPriceListProvider->expects($this->never())
            ->method($this->anything());
        $this->listener->onCollectAssociations($event);
    }

    public function testOnCollectAssociations()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 10]);

        $event = new CollectByCustomerGroupEvent($website, $customerGroup);
        $collection = [
            new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true)
        ];
        $this->collectionProvider->expects($this->once())
            ->method('getPriceListsByCustomerGroup')
            ->with($customerGroup, $website)
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
                        'website' => [
                            'id:1' => [
                                'customer_group' => ['ids' => [10]]
                            ]
                        ]
                    ]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );
    }
}
