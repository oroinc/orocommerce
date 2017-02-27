<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Result\Factory;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResult;
use Symfony\Component\Translation\TranslatorInterface;

class UpsConnectionValidatorResultFactory implements UpsConnectionValidatorResultFactoryInterface
{
    const AUTHENTICATION_ERROR_SEVERITY_CODE = 'Authentication';
    const WRONG_MEASUREMENT_SYSTEM_ERROR_CODE = '111057';
    const UNAVAILABLE_SERVICE_BETWEEN_LOCATIONS_ERROR_CODE = '111210';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     *
     * @throws RestException
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function createResultByUpsClientResponse(RestResponseInterface $response)
    {
        $resultParams = [
            UpsConnectionValidatorResult::STATUS_KEY => true,
            UpsConnectionValidatorResult::ERROR_SEVERITY_KEY => null,
            UpsConnectionValidatorResult::ERROR_MESSAGE_KEY => null
        ];
        /** @var array $data */
        $data = $response->json();
        if (!is_array($data)) {
            throw new \LogicException($data);
        }
        if (array_key_exists('Fault', $data)
            && $this->getErrorCode($data) !== self::UNAVAILABLE_SERVICE_BETWEEN_LOCATIONS_ERROR_CODE
        ) {
            $resultParams[UpsConnectionValidatorResult::STATUS_KEY] = false;
            if ($this->getErrorSeverity($data) === self::AUTHENTICATION_ERROR_SEVERITY_CODE
                || $this->getErrorCode($data) === self::WRONG_MEASUREMENT_SYSTEM_ERROR_CODE
            ) {
                $resultParams[UpsConnectionValidatorResult::ERROR_SEVERITY_KEY] =
                    UpsConnectionValidatorResult::FAULT_SEVERITY;
                $resultParams[UpsConnectionValidatorResult::ERROR_MESSAGE_KEY] = $this->getErrorMessage($data);
            } else {
                $resultParams[UpsConnectionValidatorResult::ERROR_SEVERITY_KEY] =
                    UpsConnectionValidatorResult::WARNING_SEVERITY;
                $resultParams[UpsConnectionValidatorResult::ERROR_MESSAGE_KEY] = $this->translator
                    ->trans('oro.ups.connection_validation.result.not_authentication_error.message');
            }
        }

        return new UpsConnectionValidatorResult($resultParams);
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \LogicException
     */
    private function getErrorDetails(array $data)
    {
        return $this->getValueByKeyRecursively($data, 'ErrorDetail');
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws \LogicException
     */
    private function getErrorSeverity(array $data)
    {
        return $this->getValueByKeyRecursively($this->getErrorDetails($data), 'Severity');
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \LogicException
     */
    private function getPrimaryError(array $data)
    {
        return $this->getValueByKeyRecursively($this->getErrorDetails($data), 'PrimaryErrorCode');
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws \LogicException
     */
    private function getErrorCode(array $data)
    {
        return $this->getValueByKeyRecursively($this->getPrimaryError($data), 'Code');
    }

    /**
     * @param array $data
     *
     * @return string
     * @throws \LogicException
     */
    private function getErrorMessage(array $data)
    {
        return $this->getValueByKeyRecursively($this->getPrimaryError($data), 'Description');
    }

    /**
     * @param array  $arr
     * @param string $key
     *
     * @return array
     * @throws \LogicException
     */
    private function getValueByKeyRecursively(array $arr, $key)
    {
        if (array_key_exists($key, $arr)) {
            return $arr[$key];
        }

        foreach ($arr as $element) {
            if (is_array($element)) {
                return $this->getValueByKeyRecursively($element, $key);
            }
        }

        throw new \LogicException('UPS Error Response format has been changed');
    }
}
