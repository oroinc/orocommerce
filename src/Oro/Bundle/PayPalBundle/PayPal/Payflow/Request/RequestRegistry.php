<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Request;

class RequestRegistry
{
    /** @var RequestInterface[] */
    protected $requests = [];

    /**
     * @param RequestInterface $request
     * @return $this
     */
    public function addRequest(RequestInterface $request)
    {
        $this->requests[$request->getTransactionType()] = $request;

        return $this;
    }

    /**
     * @param string $transactionType
     * @return RequestInterface
     */
    public function getRequest($transactionType)
    {
        $transactionType = (string)$transactionType;

        if (array_key_exists($transactionType, $this->requests)) {
            return $this->requests[$transactionType];
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Request with type "%s" is missing. Registered requests are "%s"',
                $transactionType,
                implode(', ', array_keys($this->requests))
            )
        );
    }
}
