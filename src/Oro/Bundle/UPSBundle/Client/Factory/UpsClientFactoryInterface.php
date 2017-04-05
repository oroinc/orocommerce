<?php

namespace Oro\Bundle\UPSBundle\Client\Factory;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;

interface UpsClientFactoryInterface
{
    /**
     * @param bool $isTestMode
     *
     * @return RestClientInterface
     */
    public function createUpsClient($isTestMode);
}
