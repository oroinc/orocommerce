<?php

namespace Oro\Bundle\FedexShippingBundle\Cache;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequestInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;

interface FedexResponseCacheKeyInterface
{
    public function getRequest(): FedexRequestInterface;

    public function getSettings(): FedexIntegrationSettings;

    public function getCacheKey(): string;
}
