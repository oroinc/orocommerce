<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProviderInterface;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Event\ExtractShippingAddressOptionsEvent;
use Oro\Bundle\PayPalBundle\Provider\ExtractOptionsDispatcherProvider;
use Oro\Bundle\PayPalBundle\Tests\Unit\Method\EntityStub;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ExtractOptionsDispatcherProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $dispatcherMock;

    /** @var EntityAliasProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $aliasProviderMock;

    /** @var ExtractOptionsDispatcherProvider */
    private $provider;

    protected function setUp()
    {
        $this->dispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $this->aliasProviderMock = $this->getMockBuilder(EntityAliasProviderInterface::class)->getMock();
        $this->provider = new ExtractOptionsDispatcherProvider($this->dispatcherMock, $this->aliasProviderMock);
    }

    public function testGetShippingAddressOptions()
    {
        $entity = new \stdClass();
        $classname = \stdClass::class;
        $keys = [];
        $entityAlias = new EntityAlias('stdclass', 'stdclasses');
        $event = new ExtractShippingAddressOptionsEvent($entity, $keys);

        $this->aliasProviderMock->expects($this->once())->method('getEntityAlias')->with($classname)
            ->willReturn($entityAlias);

        $this->dispatcherMock->expects($this->once())->method('dispatch')
            ->with(ExtractShippingAddressOptionsEvent::NAME . '.' . $entityAlias->getAlias(), $event);

        $this->provider->getShippingAddressOptions($classname, $entity, $keys);
    }

    public function testGetLineItemPaymentOptions()
    {
        $entity = new EntityStub();
        $event = new ExtractLineItemPaymentOptionsEvent($entity, []);

        $this->dispatcherMock->expects($this->once())->method('dispatch')
            ->with(ExtractLineItemPaymentOptionsEvent::NAME, $event);

        $this->provider->getLineItemPaymentOptions($entity, []);
    }
}
