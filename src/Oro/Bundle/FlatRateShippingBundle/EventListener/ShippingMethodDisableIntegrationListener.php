<?php
namespace Oro\Bundle\FlatRateShippingBundle\EventListener;

use Oro\Bundle\FlatRateShippingBundle\Integration\FlatRateChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Event\Action\ChannelDisableEvent;
use Oro\Bundle\IntegrationBundle\Generator\IntegrationIdentifierGeneratorInterface;
use Oro\Bundle\ShippingBundle\Method\Handler\ShippingMethodDisableHandlerInterface;

class ShippingMethodDisableIntegrationListener
{
    /**
     * @var IntegrationIdentifierGeneratorInterface
     */
    private $integrationIdentifierGenerator;

    /**
     * @var ShippingMethodDisableHandlerInterface
     */
    private $shippingMethodDisableHandler;

    public function __construct(
        IntegrationIdentifierGeneratorInterface $integrationIdentifierGenerator,
        ShippingMethodDisableHandlerInterface $shippingMethodDisableHandler
    ) {
        $this->integrationIdentifierGenerator = $integrationIdentifierGenerator;
        $this->shippingMethodDisableHandler = $shippingMethodDisableHandler;
    }

    public function onIntegrationDisable(ChannelDisableEvent $event)
    {
        /** @var Channel $channel */
        $channel = $event->getChannel();
        $channelType = $channel->getType();
        if ($channelType === FlatRateChannelType::TYPE) {
            $methodId = $this->integrationIdentifierGenerator->generateIdentifier($channel);
            $this->shippingMethodDisableHandler->handleMethodDisable($methodId);
        }
    }
}
