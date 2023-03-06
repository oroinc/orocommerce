<?php

namespace Oro\Bundle\ShippingBundle\Method\Provider\Integration;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Represents a service to load a specific type of integration channels.
 */
interface ChannelLoaderInterface
{
    /**
     * @psalm-return array<int, Channel>
     */
    public function loadChannels(string $channelType, bool $applyAcl, Organization $organization = null): array;
}
