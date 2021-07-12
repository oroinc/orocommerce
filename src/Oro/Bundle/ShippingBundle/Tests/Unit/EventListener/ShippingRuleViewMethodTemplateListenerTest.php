<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\EventListener;

use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\ShippingBundle\EventListener\ShippingRuleViewMethodTemplateListener;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class ShippingRuleViewMethodTemplateListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @internal
     */
    const TEMPLATE = '@Foo/bar.html.twig';

    /**
     * @var ShippingMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $provider;

    /**
     * @var ShippingRuleViewMethodTemplateListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(ShippingMethodProviderInterface::class);

        $this->listener = new ShippingRuleViewMethodTemplateListener(self::TEMPLATE, $this->provider);
    }

    public function testOnGetConfigData()
    {
        $methodIdentifier = 'method_1';
        $event = new ShippingMethodConfigDataEvent($methodIdentifier);

        $this->provider
            ->expects(static::once())
            ->method('hasShippingMethod')
            ->with($methodIdentifier)
            ->willReturn(true);

        $this->listener->onGetConfigData($event);

        self::assertEquals(self::TEMPLATE, $event->getTemplate());
    }

    public function testOnGetConfigDataNoMethod()
    {
        $methodIdentifier = 'method_1';
        $event = new ShippingMethodConfigDataEvent($methodIdentifier);

        $this->provider
            ->expects(static::once())
            ->method('hasShippingMethod')
            ->with($methodIdentifier)
            ->willReturn(false);

        $this->listener->onGetConfigData($event);

        self::assertNull($event->getTemplate());
    }
}
