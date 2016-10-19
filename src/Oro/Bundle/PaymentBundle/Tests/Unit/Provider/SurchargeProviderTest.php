<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\PaymentBundle\Event\CollectSurchargeEvent;
use Oro\Bundle\PaymentBundle\Model\Surcharge;
use Oro\Bundle\PaymentBundle\Provider\SurchargeProvider;

class SurchargeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $dispatcher;

    /** @var SurchargeProvider */
    private $provider;

    protected function setUp()
    {
        $this->dispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->provider = new SurchargeProvider($this->dispatcher);
    }

    public function testGetSurcharges()
    {
        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(CollectSurchargeEvent::NAME, $this->isInstanceOf(CollectSurchargeEvent::class));

        $entity = new \stdClass();
        $surcharge = $this->provider->getSurcharges($entity);

        $this->assertInstanceOf(Surcharge::class, $surcharge);
    }
}
