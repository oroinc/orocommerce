<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResultInterface;

interface UpsConnectionValidatorResultFactoryInterface
{
    /**
     * @param RestResponseInterface $response
     *
     * @return UpsConnectionValidatorResultInterface
     */
    public function createResultByUpsClientResponse(RestResponseInterface $response);

    /**
     * @param RestException $exception
     *
     * @return UpsConnectionValidatorResultInterface
     */
    public function createExceptionResult(RestException $exception);
}
