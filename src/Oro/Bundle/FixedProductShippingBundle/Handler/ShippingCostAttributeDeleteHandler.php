<?php

namespace Oro\Bundle\FixedProductShippingBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FixedProductShippingBundle\Integration\FixedProductChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ChannelLoaderInterface;

/**
 * Checks if product shipping cost integration is enabled or specified in shipping rules.
 */
class ShippingCostAttributeDeleteHandler
{
    private const FIELD_NAME = 'shippingCost';

    private ManagerRegistry $doctrine;
    private ChannelLoaderInterface $channelLoader;

    public function __construct(ManagerRegistry $doctrine, ChannelLoaderInterface $channelLoader)
    {
        $this->doctrine = $doctrine;
        $this->channelLoader = $channelLoader;
    }

    public function isAttributeFixed(PriceAttributePriceList $priceAttributePriceList): bool
    {
        if (self::FIELD_NAME !== $priceAttributePriceList->getFieldName()) {
            return false;
        }

        $channels = $this->channelLoader->loadChannels(
            FixedProductChannelType::TYPE,
            false,
            $priceAttributePriceList->getOrganization()
        );
        if (!$channels) {
            return false;
        }

        if ($this->hasEnabledIntegrations($channels)) {
            return true;
        }

        return $this->doctrine->getRepository(ShippingMethodConfig::class)
            ->configExistsByMethods($this->getIntegrationMethods($channels));
    }

    /**
     * @param Channel[] $channels
     *
     * @return bool
     */
    private function hasEnabledIntegrations(array $channels): bool
    {
        $hasEnabled = false;
        foreach ($channels as $channel) {
            if ($channel->isEnabled()) {
                $hasEnabled = true;
                break;
            }
        }

        return $hasEnabled;
    }

    /**
     * @param Channel[] $channels
     *
     * @return string[]
     */
    private function getIntegrationMethods(array $channels): array
    {
        $methods = [];
        foreach ($channels as $channel) {
            $methods[] = sprintf('%s_%s', $channel->getType(), $channel->getId());
        }

        return $methods;
    }
}
