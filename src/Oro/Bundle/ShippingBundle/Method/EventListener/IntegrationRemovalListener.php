<?php

namespace Oro\Bundle\ShippingBundle\Method\EventListener;

use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDeleteEvent;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEventDispatcherInterface;

/**
 * Handles {@see ChannelDeleteEvent} to dispatch shipping method removal events.
 *
 * This listener monitors integration channel deletions and dispatches method removal events for shipping methods
 * associated with the deleted integration, allowing cleanup of related shipping configurations and rules.
 */
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
