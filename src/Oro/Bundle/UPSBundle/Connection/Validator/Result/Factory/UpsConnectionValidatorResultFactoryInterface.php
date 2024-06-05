<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResultInterface;

/**
 * Interface for UPS Connection Validator Result Factory
 */
interface UpsConnectionValidatorResultFactoryInterface
{
    public function createResultByUpsClientResponse(
        RestResponseInterface $response
    ): UpsConnectionValidatorResultInterface;

    public function createExceptionResult(
        RestException $exception
    ): UpsConnectionValidatorResultInterface;
}
