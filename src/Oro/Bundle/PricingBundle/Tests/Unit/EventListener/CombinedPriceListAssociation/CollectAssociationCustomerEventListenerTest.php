<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener\CombinedPriceListAssociation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByCustomerEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByCustomerGroupEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByWebsiteEvent;
use Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation\CollectAssociationCustomerEventListener;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CollectAssociationCustomerEventListenerTest extends TestCase
{
    use EntityTrait;

    private PriceListCollectionProvider|MockObject $collectionProvider;
    private CombinedPriceListProvider|MockObject $combinedPriceListProvider;
    private ManagerRegistry|MockObject $registry;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private CollectAssociationCustomerEventListener $listener;

    protected function setUp(): void
    {
        $this->collectionProvider = $this->createMock(PriceListCollectionProvider::class);
        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new CollectAssociationCustomerEventListener(
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

        $customer = $this->getEntity(Customer::class, ['id' => 10]);
        $repo = $this->createMock(PriceListToCustomerRepository::class);
        $repo->expects($this->once())
            ->method('getAllCustomersWithEmptyGroupAndDefaultFallback')
            ->with($website)
            ->willReturn([$customer]);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(PriceListToCustomer::class)
            ->willReturn($repo);

        $customerEvent = new CollectByCustomerEvent($website, $customer, false);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($customerEvent, $customerEvent::NAME);

        $event->expects($this->once())
            ->method('mergeAssociations')
            ->with($customerEvent);

        $this->listener->onCollectAssociationsWebsiteLevel($event);
    }

    public function testOnCollectAssociationsWebsiteLevelSelfFallback()
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

        $customer1 = $this->getEntity(Customer::class, ['id' => 10]);
        $customer2 = $this->getEntity(Customer::class, ['id' => 20]);
        $repo = $this->createMock(PriceListToCustomerRepository::class);
        $repo->expects($this->once())
            ->method('getAllCustomersWithEmptyGroupAndDefaultFallback')
            ->with($website)
            ->willReturn([$customer1]);
        $repo->expects($this->once())
            ->method('getAllCustomersWithEmptyGroupAndSelfFallback')
            ->with($website)
            ->willReturn([$customer2]);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(PriceListToCustomer::class)
            ->willReturn($repo);

        $customerEvent1 = new CollectByCustomerEvent($website, $customer1, true);
        $customerEvent2 = new CollectByCustomerEvent($website, $customer2, true);
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$customerEvent1, $customerEvent1::NAME],
                [$customerEvent2, $customerEvent2::NAME],
            );

        $event->expects($this->exactly(2))
            ->method('mergeAssociations')
            ->withConsecutive(
                [$customerEvent1],
                [$customerEvent2]
            );

        $this->listener->onCollectAssociationsWebsiteLevel($event);
    }

    public function testOnCollectAssociationsCustomerGroupLevelNoSelfFallback()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 100]);

        /** @var CollectByCustomerGroupEvent|MockObject $event */
        $event = $this->createMock(CollectByCustomerGroupEvent::class);
        $event->expects($this->any())
            ->method('getWebsite')
            ->willReturn($website);
        $event->expects($this->any())
            ->method('getCustomerGroup')
            ->willReturn($customerGroup);
        $event->expects($this->any())
            ->method('isIncludeSelfFallback')
            ->willReturn(false);

        $customer = $this->getEntity(Customer::class, ['id' => 10]);
        $repo = $this->createMock(PriceListToCustomerRepository::class);
        $repo->expects($this->once())
            ->method('getCustomerIteratorWithDefaultFallback')
            ->with($customerGroup, $website)
            ->willReturn([$customer]);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(PriceListToCustomer::class)
            ->willReturn($repo);

        $customerEvent = new CollectByCustomerEvent($website, $customer, false);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($customerEvent, $customerEvent::NAME);

        $event->expects($this->once())
            ->method('mergeAssociations')
            ->with($customerEvent);

        $this->listener->onCollectAssociationsCustomerGroupLevel($event);
    }

    public function testOnCollectAssociationsCustomerGroupLevelSelfFallback()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 100]);

        /** @var CollectByCustomerGroupEvent|MockObject $event */
        $event = $this->createMock(CollectByCustomerGroupEvent::class);
        $event->expects($this->any())
            ->method('getWebsite')
            ->willReturn($website);
        $event->expects($this->any())
            ->method('getCustomerGroup')
            ->willReturn($customerGroup);
        $event->expects($this->any())
            ->method('isIncludeSelfFallback')
            ->willReturn(true);

        $customer1 = $this->getEntity(Customer::class, ['id' => 10]);
        $customer2 = $this->getEntity(Customer::class, ['id' => 20]);
        $repo = $this->createMock(PriceListToCustomerRepository::class);
        $repo->expects($this->once())
            ->method('getCustomerIteratorWithDefaultFallback')
            ->with($customerGroup, $website)
            ->willReturn([$customer1]);
        $repo->expects($this->once())
            ->method('getCustomerIteratorWithSelfFallback')
            ->with($customerGroup, $website)
            ->willReturn([$customer2]);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with(PriceListToCustomer::class)
            ->willReturn($repo);

        $customerEvent1 = new CollectByCustomerEvent($website, $customer1, true);
        $customerEvent2 = new CollectByCustomerEvent($website, $customer2, true);
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [$customerEvent1, $customerEvent1::NAME],
                [$customerEvent2, $customerEvent2::NAME],
            );

        $event->expects($this->exactly(2))
            ->method('mergeAssociations')
            ->withConsecutive(
                [$customerEvent1],
                [$customerEvent2]
            );

        $this->listener->onCollectAssociationsCustomerGroupLevel($event);
    }

    public function testOnCollectAssociationsSkipCurrentLevel()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customer = $this->getEntity(Customer::class, ['id' => 10]);

        $event = new CollectByCustomerEvent($website, $customer, false, false);
        $this->collectionProvider->expects($this->never())
            ->method($this->anything());
        $this->combinedPriceListProvider->expects($this->never())
            ->method($this->anything());
        $this->listener->onCollectAssociations($event);
    }

    public function testOnCollectAssociations()
    {
        $website = $this->getEntity(Website::class, ['id' => 1]);
        $customer = $this->getEntity(Customer::class, ['id' => 10]);

        $event = new CollectByCustomerEvent($website, $customer);
        $collection = [
            new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true)
        ];
        $this->collectionProvider->expects($this->once())
            ->method('getPriceListsByCustomer')
            ->with($customer, $website)
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
                                'customer' => ['ids' => [10]]
                            ]
                        ]
                    ]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );
    }
}
