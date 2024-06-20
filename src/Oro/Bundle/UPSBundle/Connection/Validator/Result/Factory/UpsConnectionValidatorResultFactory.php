<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\Client\Result\UpsErrorResultTrait;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResult;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResultInterface;

/**
 * Basic implementation of UPS Connection Validator Result Factory
 */
class UpsConnectionValidatorResultFactory implements UpsConnectionValidatorResultFactoryInterface
{
    use UpsErrorResultTrait;

    public const AUTHENTICATION_ERROR_SEVERITY_CODE = 'Authentication';
    public const WRONG_MEASUREMENT_SYSTEM_ERROR_CODE = '111057';
    public const UNAVAILABLE_SERVICE_BETWEEN_LOCATIONS_ERROR_CODE = '111210';

    public const AUTHENTICATION_SEVERITY = 'authentication';
    public const MEASUREMENT_SYSTEM_SEVERITY = 'measurement_system';
    public const UNEXPECTED_SEVERITY = 'unexpected';
    public const SERVER_SEVERITY = 'server';

    /**
     * {@inheritDoc}
     *
     * @throws RestException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function createResultByUpsClientResponse(
        RestResponseInterface $response
    ): UpsConnectionValidatorResultInterface {
        $resultParams = [
            UpsConnectionValidatorResult::STATUS_KEY => true,
            UpsConnectionValidatorResult::ERROR_SEVERITY_KEY => null,
            UpsConnectionValidatorResult::ERROR_MESSAGE_KEY => null,
        ];
        /** @var array $data */
        $data = $response->json();
        if (!is_array($data)) {
            throw new \LogicException($data);
        }
        if (array_key_exists('Fault', $data)
            && $this->getErrorCode($data) !== self::UNAVAILABLE_SERVICE_BETWEEN_LOCATIONS_ERROR_CODE
        ) {
            $resultParams = [
                UpsConnectionValidatorResult::STATUS_KEY => false,
                UpsConnectionValidatorResult::ERROR_SEVERITY_KEY => self::UNEXPECTED_SEVERITY,
                UpsConnectionValidatorResult::ERROR_MESSAGE_KEY => $this->getErrorMessage($data),
            ];

            if ($this->getErrorSeverity($data) === self::AUTHENTICATION_ERROR_SEVERITY_CODE) {
                $resultParams[UpsConnectionValidatorResult::ERROR_SEVERITY_KEY] = self::AUTHENTICATION_SEVERITY;
            } elseif ($this->getErrorCode($data) === self::WRONG_MEASUREMENT_SYSTEM_ERROR_CODE) {
                $resultParams[UpsConnectionValidatorResult::ERROR_SEVERITY_KEY] = self::MEASUREMENT_SYSTEM_SEVERITY;
            }
        }

        return new UpsConnectionValidatorResult($resultParams);
    }

    /**
     * {@inheritDoc}
     */
    public function createExceptionResult(RestException $exception): UpsConnectionValidatorResultInterface
    {
        return new UpsConnectionValidatorResult([
            UpsConnectionValidatorResult::STATUS_KEY => false,
            UpsConnectionValidatorResult::ERROR_SEVERITY_KEY => self::SERVER_SEVERITY,
            UpsConnectionValidatorResult::ERROR_MESSAGE_KEY => $exception->getMessage(),
        ]);
    }
}
