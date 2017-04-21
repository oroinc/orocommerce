<?php

namespace Oro\Bundle\ApruveBundle\Client\Factory;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClientInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfig;

interface ApruveRestClientFactoryInterface
{
    /**
     * @param ApruveConfig $apruveConfig
     *
     * @return ApruveRestClientInterface
     */
    public function create(ApruveConfig $apruveConfig);
}
