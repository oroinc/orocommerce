<?php

namespace Oro\Bundle\UPSBundle\Client\Url\Provider\Basic;

use Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface;

/**
 * Basic implementation for UPS Client URL Provider
 */
class BasicUpsClientUrlProvider implements UpsClientUrlProviderInterface
{
    public function __construct(
        private string $productionUrl,
        private string $testUrl
    ) {
    }

    #[\Override]
    public function getUpsUrl($isTestMode): string
    {
        if ($isTestMode) {
            return $this->testUrl;
        }

        return $this->productionUrl;
    }
}
