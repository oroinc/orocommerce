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
        return $this->getState() === ResponseStatusMap::APPROVED;
    }

    /** {@inheritdoc} */
    public function getReference()
    {
        return $this->values->offsetGet(self::PNREF_KEY);
    }

    /** {@inheritdoc} */
    public function getState()
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
        if ((int)$this->getState() < 0) {
            return CommunicationErrorsStatusMap::getMessage($this->getState());
        }

        // Return message by status code
        return ResponseStatusMap::getMessage($this->getState());
    }
}
