<?php

namespace Oro\Bundle\ApruveBundle\Client;

use Oro\Bundle\ApruveBundle\Client\Exception\UnsupportedMethodException;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;

interface ApruveRestClientInterface
{
    /**
     * @param ApruveRequestInterface $apruveRequest
     *
     * @throws UnsupportedMethodException
     * @throws RestException
     *
     * @return RestResponseInterface
     */
    public function execute(ApruveRequestInterface $apruveRequest);
}
