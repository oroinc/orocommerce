<?php

namespace Oro\Bundle\ApruveBundle\Client\Factory;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClientInterface;

interface ApruveRestClientFactoryInterface
{
    /**
     * @param string $apiKey
     * @param bool   $isTestMode
     *
     * @return ApruveRestClientInterface
     */
    public function create($apiKey, $isTestMode);
}
