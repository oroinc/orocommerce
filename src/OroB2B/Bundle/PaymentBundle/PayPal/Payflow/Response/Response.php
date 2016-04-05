<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response;

class Response implements ResponseInterface
{
    const PNREF_KEY = 'PNREF';
    const RESULT_KEY = 'RESULT';
    /**
     * @var \ArrayObject
     */
    protected $values;

    /**
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        $this->values = new \ArrayObject($values);
    }

    /** {@inheritdoc} */
    public function isSuccessful()
    {
        return $this->getResult() === ResponseStatusMap::APPROVED;
    }

    /** {@inheritdoc} */
    public function getReference()
    {
        return $this->values->offsetExists(self::PNREF_KEY) ? $this->values->offsetGet(self::PNREF_KEY) : null;
    }

    /** {@inheritdoc} */
    public function getResult()
    {
        return $this->values->offsetGet(self::RESULT_KEY);
    }

    /**
     * Throws exception if status not found
     * @return string
     */
    public function getMessage()
    {
        // Communication Error Response
        if ((int)$this->getResult() < 0) {
            return CommunicationErrorsStatusMap::getMessage($this->getResult());
        }

        // Return message by status code
        return ResponseStatusMap::getMessage($this->getResult());
    }

    /** {@inheritdoc} */
    public function getData()
    {
        return $this->values->getArrayCopy();
    }
}
