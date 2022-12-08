<?php

namespace Oro\Bundle\FixedProductShippingBundle\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\FixedProductShippingBundle\Integration\FixedProductChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodConfigRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;

/**
 * Checks if product shipping cost integration is enabled or specified in shipping rules.
 */
class ShippingCostAttributeDeleteHandler
{
    private const FIELD_NAME = 'shippingCost';

    private ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    public function isAttributeFixed(PriceAttributePriceList $priceAttributePriceList): bool
    {
        if (self::FIELD_NAME !== $priceAttributePriceList->getFieldName()) {
            return false;
        }

        return $this->integrationEnabled() || $this->integrationSpecifiedInShippingRules();
    }

    private function integrationEnabled(): bool
    {
        /** @var ChannelRepository $integrationRepository */
        $integrationRepository = $this->getRepository(Channel::class);

        return (bool) $integrationRepository->findActiveByType(FixedProductChannelType::TYPE);
    }

    private function integrationSpecifiedInShippingRules(): bool
    {
        /** @var ChannelRepository $integrationRepository */
        $integrationRepository = $this->getRepository(Channel::class);

        $callback = fn (Channel $channel) => sprintf('%s_%s', $channel->getType(), $channel->getId());
        $methods = array_map($callback, $integrationRepository->findByType(FixedProductChannelType::TYPE));

        /** @var ShippingMethodConfigRepository $shippingMethodConfigRepository */
        $shippingMethodConfigRepository = $this->getRepository(ShippingMethodConfig::class);

        return $shippingMethodConfigRepository->configExistsByMethods($methods);
    }

    private function getRepository(string $className): ObjectRepository
    {
        return $this->managerRegistry->getRepository($className);
    }
}
