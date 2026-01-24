<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Response;

/**
 * Represents a PayPal Payflow API response.
 *
 * Parses and provides access to PayPal Payflow response data including transaction status,
 * reference numbers, and error messages.
 */
class Response implements ResponseInterface
{
    const PNREF_KEY = 'PNREF';
    const RESULT_KEY = 'RESULT';
    const RESPMSG_KEY = 'RESPMSG';
    /**
     * @var \ArrayObject
     */
    protected $values;

    public function __construct(array $values = [])
    {
        $this->values = new \ArrayObject($values);
    }

    #[\Override]
    public function isSuccessful()
    {
        return $this->getResult() === ResponseStatusMap::APPROVED;
    }

    #[\Override]
    public function getReference()
    {
        return $this->getOffset(self::PNREF_KEY);
    }

    #[\Override]
    public function getResult()
    {
        return $this->getOffset(self::RESULT_KEY);
    }

    /**
     * @param mixed $index
     * @param mixed $default
     * @return mixed|null
     */
    public function getOffset($index, $default = null)
    {
        return $this->values->offsetExists($index) ? $this->values->offsetGet($index) : $default;
    }

    #[\Override]
    public function getMessage()
    {
        return $this->getOffset(self::RESPMSG_KEY);
    }

    #[\Override]
    public function getErrorMessage()
    {
        // Communication Error Response
        if ((int)$this->getResult() < 0) {
            return CommunicationErrorsStatusMap::getMessage($this->getResult());
        }

        // Return message by status code
        return ResponseStatusMap::getMessage($this->getResult());
    }

    #[\Override]
    public function getData()
    {
        return $this->values->getArrayCopy();
    }
}
