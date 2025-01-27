<?php

namespace Oro\Bundle\UPSBundle\AddressValidation\Cache;

use Oro\Bundle\AddressValidationBundle\Cache\AbstractAddressValidationResponseCache;
use Oro\Bundle\AddressValidationBundle\Cache\AddressValidationCacheKeyInterface;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

/**
 * Cache adapter for storing responses from UPS Address Validation Resolver service
 */
class UPSAddressValidationResponseCache extends AbstractAddressValidationResponseCache
{
    /**
     * @param UPSTransport $transport
     */
    protected function getInvalidatedAt(Transport $transport): ?\DateTimeInterface
    {
        return $transport->getUpsInvalidateCacheAt();
    }

    protected function generateCacheKey(AddressValidationCacheKeyInterface $key): string
    {
        /** @var UPSTransport $transport */
        $transport = $key->getTransport();

        return UniversalCacheKeyGenerator::normalizeCacheKey(sprintf(
            '%s_%s_%s_%s',
            $key->getCacheKey(),
            $transport->getId(),
            $transport->getUpsClientId(),
            $transport->getUpsClientSecret()
        ));
    }
}
