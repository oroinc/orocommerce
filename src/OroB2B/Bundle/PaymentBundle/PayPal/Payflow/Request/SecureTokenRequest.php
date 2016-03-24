<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\SecureToken;

class SecureTokenRequest extends AbstractRequest
{
    /** @var RequestInterface */
    protected $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;

        parent::__construct();
    }

    public function configureFinalOptions()
    {
        $this->addOption(new SecureToken());
    }

    /** {@inheritdoc} */
    public function getAction()
    {
        return $this->request->getAction();
    }
}
