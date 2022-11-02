<?php

namespace Oro\Bundle\ShippingBundle\Method\EventListener;

use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDisableEvent;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Handler\ShippingMethodDisableHandlerInterface;

class ShippingMethodDisableIntegrationListener
{
    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $methodIdentifierGenerator;

    /**
     * @var ShippingMethodDisableHandlerInterface
     */
    private $shippingMethodDisableHandler;

    /**
     * @param string                                        $channelType
     * @param IntegrationIdentifierGeneratorInterface $methodIdentifierGenerator
     * @param ShippingMethodDisableHandlerInterface         $shippingMethodDisableHandler
     */
    public function __construct(
        $channelType,
        IntegrationIdentifierGeneratorInterface $methodIdentifierGenerator,
        ShippingMethodDisableHandlerInterface $shippingMethodDisableHandler
    ) {
        $this->channelType = $channelType;
        $this->methodIdentifierGenerator = $methodIdentifierGenerator;
        $this->shippingMethodDisableHandler = $shippingMethodDisableHandler;
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
