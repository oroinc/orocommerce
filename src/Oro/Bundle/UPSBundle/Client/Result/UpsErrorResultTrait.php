<?php

namespace Oro\Bundle\UPSBundle\Client\Result;

trait UpsErrorResultTrait
{
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
     * @param array  $array
     * @param string $key
     *
     * @return string|array
     * @throws \LogicException
     */
    private function getValueByKeyRecursively(array $array, $key)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach ($array as $element) {
            if (is_array($element)) {
                return $this->getValueByKeyRecursively($element, $key);
            }
        }

        throw new \LogicException('UPS Error Response format has been changed');
    }
}
