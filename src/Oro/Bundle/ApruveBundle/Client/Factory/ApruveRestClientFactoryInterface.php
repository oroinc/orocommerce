<?php

namespace Oro\Bundle\ApruveBundle\Client\Factory;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClientInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;

interface ApruveRestClientFactoryInterface
{
    /**
     * @param ApruveConfigInterface $apruveConfig
     *
     * @return ApruveRestClientInterface
     */
    public function create(ApruveConfigInterface $apruveConfig);
}
