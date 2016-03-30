<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

class RequestRegistry
{
    /** @var RequestInterface[] */
    protected $requests = [];

    public function __construct()
    {
        $this
            ->addRequest(new AuthorizationRequest())
            ->addRequest(new SaleRequest())
            ->addRequest(new DelayedCaptureRequest())
            ->addRequest(new VoidRequest());
    }

    /**
     * @param RequestInterface $request
     * @return $this
     */
    public function addRequest(RequestInterface $request)
    {
        $this->requests[$request->getAction()] = $request;

        return $this;
    }

    /**
     * @param string $action
     * @return RequestInterface
     */
    public function getRequest($action)
    {
        $action = (string)$action;

        if (array_key_exists($action, $this->requests)) {
            return $this->requests[$action];
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Request with "%s" action is missing. Registered request are "%s"',
                $action,
                implode(', ', array_keys($this->requests))
            )
        );
    }
}
