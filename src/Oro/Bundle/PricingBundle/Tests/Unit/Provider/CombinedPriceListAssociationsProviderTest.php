<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectByConfigEvent;
use Oro\Bundle\PricingBundle\Event\CombinedPriceList\Assignment\CollectEventFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListAssociationsProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CombinedPriceListAssociationsProviderTest extends TestCase
{
    private CollectEventFactoryInterface|MockObject $eventFactory;
    private EventDispatcherInterface|MockObject $eventDispatcher;
    private CombinedPriceListAssociationsProvider $provider;

    protected function setUp(): void
    {
        $this->eventFactory = $this->createMock(CollectEventFactoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->provider = new CombinedPriceListAssociationsProvider(
            $this->eventFactory,
            $this->eventDispatcher
        );
    }

    public function testGetCombinedPriceListsWithAssociations()
    {
        $associations = ['config' => true];
        $event = $this->createMock(CollectByConfigEvent::class);
        $this->eventFactory->expects($this->once())
            ->method('createEvent')
            ->willReturn($event);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, $event::NAME);
        $event->expects($this->once())
            ->method('getCombinedPriceListAssociations')
            ->willReturn($associations);

        $this->assertEquals($associations, $this->provider->getCombinedPriceListsWithAssociations());
    }
}
