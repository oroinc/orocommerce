<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\EventListener\AbstractIntegrationRemovalListener;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

abstract class IntegrationRemovalListenerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IntegrationMethodIdentifierGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $identifierGenerator;

    /**
     * @var MethodRemovalEventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var AbstractIntegrationRemovalListener
     */
    private $listener;


    protected function setUp()
    {
        $this->identifierGenerator = $this->createMock(IntegrationMethodIdentifierGeneratorInterface::class);
        $this->dispatcher = $this->createMock(MethodRemovalEventDispatcherInterface::class);

        $this->listener = $this->createListener($this->identifierGenerator, $this->dispatcher);
    }

    /**
     * @param IntegrationMethodIdentifierGeneratorInterface $identifierGenerator
     * @param MethodRemovalEventDispatcherInterface $dispatcher
     * @return AbstractIntegrationRemovalListener
     */
    abstract protected function createListener(
        IntegrationMethodIdentifierGeneratorInterface $identifierGenerator,
        MethodRemovalEventDispatcherInterface $dispatcher
    );

    /**
     * @return string
     */
    abstract protected function getType();

    public function testPreRemove()
    {
        /** @var LifecycleEventArgs $args */
        $args = $this->createMock(LifecycleEventArgs::class);

        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects($this->once())
            ->method('getType')
            ->willReturn($this->getType());

        $identifier = 'method';

        $this->identifierGenerator->expects($this->once())
            ->method('generateIdentifier')
            ->with($channel)
            ->willReturn($identifier);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($identifier);

        $this->listener->preRemove($channel, $args);
    }

    public function testPreRemoveOtherType()
    {
        /** @var LifecycleEventArgs $args */
        $args = $this->createMock(LifecycleEventArgs::class);

        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel */
        $channel = $this->createMock(Channel::class);
        $channel->expects($this->once())
            ->method('getType')
            ->willReturn('other_type');

        $this->identifierGenerator->expects($this->never())
            ->method('generateIdentifier');

        $this->dispatcher->expects($this->never())
            ->method('dispatch');

        $this->listener->preRemove($channel, $args);
    }
}
