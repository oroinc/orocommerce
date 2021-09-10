<?php

namespace Oro\Bundle\ShippingBundle\Method\EventListener;

use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDeleteEvent;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEventDispatcherInterface;

class IntegrationRemovalListener
{
    /**
     * @var string
     */
    private $channelType;

    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $identifierGenerator;

    /**
     * @var MethodRemovalEventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @param string                                        $channelType
     * @param IntegrationIdentifierGeneratorInterface $identifierGenerator
     * @param MethodRemovalEventDispatcherInterface         $dispatcher
     */
    public function __construct(
        $channelType,
        IntegrationIdentifierGeneratorInterface $identifierGenerator,
        MethodRemovalEventDispatcherInterface $dispatcher
    ) {
        $this->channelType = $channelType;
        $this->identifierGenerator = $identifierGenerator;
        $this->dispatcher = $dispatcher;
    }

    public function onRemove(ChannelDeleteEvent $event)
    {
        $channel = $event->getChannel();
        if ($this->channelType === $channel->getType()) {
            $this->dispatcher->dispatch($this->identifierGenerator->generateIdentifier($channel));
        }
    }
}
