<?php
namespace Oro\Bundle\FlatRateShippingBundle\EventListener;

use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDisableEvent;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\FlatRateShippingBundle\Integration\FlatRateChannelType;
use Oro\Bundle\ShippingBundle\Method\Identifier\IntegrationMethodIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Handler\ShippingMethodDisableHandlerInterface;

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
     * @param IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator
     * @param ShippingMethodDisableHandlerInterface         $shippingMethodDisableHandler
     */
    public function __construct(
        IntegrationMethodIdentifierGeneratorInterface $methodIdentifierGenerator,
        ShippingMethodDisableHandlerInterface $shippingMethodDisableHandler
    ) {
        $this->methodIdentifierGenerator = $methodIdentifierGenerator;
        $this->shippingMethodDisableHandler = $shippingMethodDisableHandler;
    }

    /**
     * @param ChannelDisableEvent $event
     */
    public function onIntegrationDisable(ChannelDisableEvent $event)
    {
        /** @var Channel $channel */
        $channel = $event->getChannel();
        $channelType = $channel->getType();
        if ($channelType === FlatRateChannelType::TYPE) {
            $methodId = $this->methodIdentifierGenerator->generateIdentifier($channel);
            $this->shippingMethodDisableHandler->handleMethodDisable($methodId);
        }
    }
}
