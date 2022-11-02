<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SurchargeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var SurchargeProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->provider = new SurchargeProvider($this->dispatcher);
    }

    public function testGetSurcharges()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(CollectSurchargeEvent::class), CollectSurchargeEvent::NAME);

        $entity = new \stdClass();
        $surcharge = $this->provider->getSurcharges($entity);

        $this->assertInstanceOf(Surcharge::class, $surcharge);
    }
}
