<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

/**
 * Represents a service to load a specific type of integration channels.
 */
interface ChannelLoaderInterface
{
    /**
     * @psalm-return array<int, Channel>
     */
    public function loadChannels(string $channelType, bool $applyAcl): array;
}
