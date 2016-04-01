<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response;

class Response implements ResponseInterface
{
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
        return $this->values->offsetGet('RESULT') === '0';
    }

    /** {@inheritdoc} */
    public function getReference()
    {
        return $this->values->offsetGet('PNREF');
    }

    /** {@inheritdoc} */
    public function getState()
    {
        return $this->values->offsetGet('RESULT');
    }

    /** {@inheritdoc} */
    public function getData()
    {
        return $this->values->getArrayCopy();
    }
}
