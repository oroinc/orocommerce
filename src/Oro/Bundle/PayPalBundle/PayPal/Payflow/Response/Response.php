<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Response;

class Response implements ResponseInterface
{
    const PNREF_KEY = 'PNREF';
    const RESULT_KEY = 'RESULT';
    const RESPMSG_KEY = 'RESPMSG';
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
        return $this->getOffset(self::PNREF_KEY);
    }

    /** {@inheritdoc} */
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

    /** {@inheritdoc} */
    public function getMessage()
    {
        return $this->getOffset(self::RESPMSG_KEY);
    }

    /** {@inheritdoc} */
    public function getErrorMessage()
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
