<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\PaymentBundle\Event\ExtractAddressOptionsEvent;
use Oro\Bundle\PaymentBundle\Event\ExtractLineItemPaymentOptionsEvent;
use Oro\Bundle\PaymentBundle\Provider\ExtractOptionsProvider;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\EntityStub;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ExtractOptionsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcherMock;

    /** @var EntityAliasResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $aliasProviderMock;

    /** @var ExtractOptionsProvider */
    private $provider;

    protected function setUp()
    {
        $this->dispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $this->aliasProviderMock = $this->getMockBuilder(EntityAliasResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new ExtractOptionsProvider($this->dispatcherMock, $this->aliasProviderMock);
    }

    public function testGetShippingAddressOptions()
    {
        $entity = new Address();
        $classname = Address::class;
        $keys = [];
        $entityAlias = 'address';
        $event = new ExtractAddressOptionsEvent($entity, $keys);

        $this->aliasProviderMock->expects($this->once())->method('getAlias')->with($classname)
            ->willReturn($entityAlias);

        $this->dispatcherMock->expects($this->once())->method('dispatch')
            ->with(ExtractAddressOptionsEvent::NAME . '.address', $event);

        $this->provider->getShippingAddressOptions($classname, $entity);
    }

    public function testGetLineItemPaymentOptions()
    {
        /** @var AbstractAddress|\PHPUnit\Framework\MockObject\MockObject $abstractAddressMock */
        $abstractAddressMock = $this->getMockBuilder(AbstractAddress::class)->getMock();
        $entity = new EntityStub($abstractAddressMock);
        $event = new ExtractLineItemPaymentOptionsEvent($entity, []);

        $this->dispatcherMock->expects($this->once())->method('dispatch')
            ->with(ExtractLineItemPaymentOptionsEvent::NAME, $event);

        $this->provider->getLineItemPaymentOptions($entity);
    }
}
