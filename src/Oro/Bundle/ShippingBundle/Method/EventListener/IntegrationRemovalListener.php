<?php

namespace Oro\Bundle\ShippingBundle\Method\EventListener;

use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDeleteEvent;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEventDispatcherInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

class IntegrationRemovalListener
{
    /**
     * @var string
     */
    private $channelType;

    /**
     * @var IntegrationMethodIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @var MethodRemovalEventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param string                                        $channelType
     * @param IntegrationMethodIdentifierGeneratorInterface $identifierGenerator
     * @param MethodRemovalEventDispatcherInterface         $dispatcher
     */
    public function __construct(
        $channelType,
        IntegrationMethodIdentifierGeneratorInterface $identifierGenerator,
        MethodRemovalEventDispatcherInterface $dispatcher
    ) {
        $this->channelType = $channelType;
        $this->identifierGenerator = $identifierGenerator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param ChannelDeleteEvent $event
     */
    public function onRemove(ChannelDeleteEvent $event)
    {
        $channel = $event->getChannel();
        if ($this->channelType === $channel->getType()) {
            $this->dispatcher->dispatch($this->identifierGenerator->generateIdentifier($channel));
        }
    }
}
