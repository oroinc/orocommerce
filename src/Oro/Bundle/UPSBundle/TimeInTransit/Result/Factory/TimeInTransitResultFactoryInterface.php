<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result\Factory;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\TimeInTransit\Result\TimeInTransitResultInterface;

/**
 * Defines the contract for factories that create Time In Transit result objects.
 *
 * Implementations of this interface parse UPS Time In Transit API responses and create
 * {@see TimeInTransitResultInterface} objects containing delivery estimates for available shipping services.
 * The factory also handles error responses and exceptions, converting them into result objects
 * that indicate failure with appropriate error information.
 */
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
