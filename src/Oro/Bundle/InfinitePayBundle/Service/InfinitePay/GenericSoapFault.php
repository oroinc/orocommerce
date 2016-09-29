<?php

namespace Oro\Bundle\InfinitePayBundle\Service\InfinitePay;

class GenericSoapFault
{
    /**
     * @var string
     */
    protected $message;

    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return \Oro\Bundle\InfinitePayBundle\Service\InfinitePay\GenericSoapFault
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }
}
