<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;

interface TimeInTransitResultFactoryInterface
{
    /**
     * @param RestResponseInterface $response
     *
     * @return TimeInTransitResultInterface
     */
    public function createResultByUpsClientResponse(RestResponseInterface $response);

    /**
     * @param RestException $exception
     *
     * @return TimeInTransitResultInterface
     */
    public function createExceptionResult(RestException $exception);
}
