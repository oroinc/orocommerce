<?php

namespace Oro\Bundle\ApruveBundle\Client;

use Oro\Bundle\ApruveBundle\Apruve\Client\Request\ApruveRequestInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

interface ApruveRestClientInterface
{
    /**
     * @param ApruveRequestInterface $apruveRequest
     *
     * @return RestResponseInterface
     */
    public function execute(ApruveRequestInterface $apruveRequest);
}
