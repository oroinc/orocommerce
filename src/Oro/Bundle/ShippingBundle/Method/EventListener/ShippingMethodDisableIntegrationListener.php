<?php

namespace Oro\Bundle\ShippingBundle\Method\EventListener;

use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDisableEvent;
use Oro\Bundle\ShippingBundle\Method\Handler\ShippingMethodDisableHandlerInterface;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;

class ShippingMethodDisableIntegrationListener
{
    /**
     * @var IntegrationMethodIdentifierGeneratorInterface
     */
    private $methodIdentifierGenerator;

    /**
     * @var ShippingMethodDisableHandlerInterface
     */
    private $shippingMethodDisableHandler;

    /**
     * @param string                                        $channelType
     * @param IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator
     * @param ShippingMethodDisableHandlerInterface         $shippingMethodDisableHandler
     */
    public function __construct(
        $channelType,
        IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator,
        ShippingMethodDisableHandlerInterface $shippingMethodDisableHandler
    ) {
        $this->channelType = $channelType;
        $this->methodIdentifierGenerator = $methodIdentifierGenerator;
        $this->shippingMethodDisableHandler = $shippingMethodDisableHandler;
    }

    /**
     * @param ChannelDisableEvent $event
     */
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
