<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener\CombinedPriceListAssociation;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByConfigEvent;
use Oro\Bundle\PricingBundle\EventListener\CombinedPriceListAssociation\CollectAssociationConfigEventListener;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListSequenceMember;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectAssociationConfigEventListenerTest extends TestCase
{
    use EntityTrait;

    private PriceListCollectionProvider|MockObject $collectionProvider;
    private CombinedPriceListProvider|MockObject $combinedPriceListProvider;
    private CollectAssociationConfigEventListener $listener;

    protected function setUp(): void
    {
        $this->collectionProvider = $this->createMock(PriceListCollectionProvider::class);
        $this->combinedPriceListProvider = $this->createMock(CombinedPriceListProvider::class);

        $this->listener = new CollectAssociationConfigEventListener(
            $this->collectionProvider,
            $this->combinedPriceListProvider
        );
    }

    public function testOnCollectAssociationsSkipCurrentLevel()
    {
        $event = new CollectByConfigEvent(false, false);
        $this->collectionProvider->expects($this->never())
            ->method($this->anything());
        $this->combinedPriceListProvider->expects($this->never())
            ->method($this->anything());
        $this->listener->onCollectAssociations($event);
    }

    public function testOnCollectAssociations()
    {
        $event = new CollectByConfigEvent();
        $collection = [
            new PriceListSequenceMember($this->getEntity(PriceList::class, ['id' => 1]), true)
        ];
        $this->collectionProvider->expects($this->once())
            ->method('getPriceListsByConfig')
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
                    'assign_to' => ['config' => true]
                ]
            ],
            $event->getCombinedPriceListAssociations()
        );
    }
}
