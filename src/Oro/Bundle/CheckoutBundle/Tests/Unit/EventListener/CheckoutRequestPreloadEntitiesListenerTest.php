<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Event\CheckoutRequestEvent;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutRequestPreloadEntitiesListener;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class CheckoutRequestPreloadEntitiesListenerTest extends TestCase
{
    private PreloadingManager|MockObject $preloadingManager;
    private CheckoutRequestPreloadEntitiesListener $listener;

    protected function setUp(): void
    {
        $this->preloadingManager = $this->createMock(PreloadingManager::class);
        $this->listener = new CheckoutRequestPreloadEntitiesListener($this->preloadingManager);
    }

    public function testOnCheckoutRequest()
    {
        $lineItems = new ArrayCollection([$this->createMock(CheckoutLineItem::class)]);
        $checkout = $this->createMock(Checkout::class);
        $checkout->method('getLineItems')->willReturn($lineItems);

        $event = new CheckoutRequestEvent(
            $this->createMock(Request::class),
            $checkout
        );

        $preloadConfig = ['config1', 'config2'];
        $this->listener->setPreloadConfig($preloadConfig);

        $this->preloadingManager->expects($this->once())
            ->method('preloadInEntities')
            ->with($lineItems->toArray(), $preloadConfig);

        $this->listener->onCheckoutRequest($event);
    }
}
