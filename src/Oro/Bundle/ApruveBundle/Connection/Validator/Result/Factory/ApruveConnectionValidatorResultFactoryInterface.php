<?php

namespace Oro\Bundle\ApruveBundle\Connection\Validator\Result\Factory;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\ApruveBundle\Connection\Validator\Result\ApruveConnectionValidatorResultInterface;

interface ApruveConnectionValidatorResultFactoryInterface
{
    /**
     * @param RestResponseInterface $response
     *
     * @return ApruveConnectionValidatorResultInterface
     */
    public function createResultByApruveClientResponse(RestResponseInterface $response);

    /**
     * @param RestException $exception
     *
     * @return ApruveConnectionValidatorResultInterface
     */
    public function createExceptionResult(RestException $exception);
}
