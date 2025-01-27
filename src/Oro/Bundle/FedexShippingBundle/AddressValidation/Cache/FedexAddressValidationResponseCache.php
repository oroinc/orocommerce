<?php

namespace Oro\Bundle\FedexShippingBundle\AddressValidation\Cache;

use Oro\Bundle\AddressValidationBundle\Cache\AbstractAddressValidationResponseCache;
use Oro\Bundle\AddressValidationBundle\Cache\AddressValidationCacheKeyInterface;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\IntegrationBundle\Entity\Transport;

/**
 * Implementation for FedEx cache adapter
 */
class FedexAddressValidationResponseCache extends AbstractAddressValidationResponseCache
{
    /**
     * @param FedexIntegrationSettings $transport
     */
    protected function getInvalidatedAt(Transport $transport): ?\DateTime
    {
        return $transport->getInvalidateCacheAt();
    }

    protected function generateCacheKey(AddressValidationCacheKeyInterface $key): string
    {
        /** @var FedexIntegrationSettings $transport */
        $transport = $key->getTransport();

        return UniversalCacheKeyGenerator::normalizeCacheKey(sprintf(
            '%s_%s_%s_%s',
            $key->getCacheKey(),
            $transport->getId(),
            $transport->getClientId(),
            $transport->getClientSecret()
        ));
    }
}
