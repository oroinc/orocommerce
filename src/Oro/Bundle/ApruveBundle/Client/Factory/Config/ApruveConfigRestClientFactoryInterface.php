<?php

namespace Oro\Bundle\ApruveBundle\Client\Factory\Config;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClientInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;

interface ApruveConfigRestClientFactoryInterface
{
    /**
     * @param ApruveConfigInterface $apruveConfig
     *
     * @return ApruveRestClientInterface
     */
    public function create(ApruveConfigInterface $apruveConfig);
}
