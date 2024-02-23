<?php

namespace Oro\Bundle\ShippingBundle\Method\EventListener;

use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDisableEvent;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Handler\ShippingMethodDisableHandlerInterface;

/**
 * Listener for user disables or removes the integration currently used in shipping rules,
 */
class ShippingMethodDisableIntegrationListener
{
    public function __construct(
        private string $channelType,
        private IntegrationIdentifierGeneratorInterface $methodIdentifierGenerator,
        private ShippingMethodDisableHandlerInterface $shippingMethodDisableHandler
    ) {
    }

    public function onIntegrationDisable(ChannelDisableEvent $event)
    {
        $channel = $event->getChannel();
        $channelType = $channel->getType();
        if ($channelType === $this->channelType) {
            $methodId = $this->methodIdentifierGenerator->generateIdentifier($channel);
            $this->shippingMethodDisableHandler->handleMethodDisable($methodId);
        }
    }
}
