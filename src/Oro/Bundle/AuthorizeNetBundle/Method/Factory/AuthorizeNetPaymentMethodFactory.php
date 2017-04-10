<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Factory;

use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Gateway;
use Oro\Bundle\AuthorizeNetBundle\Method\AuthorizeNetPaymentMethod;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;

class AuthorizeNetPaymentMethodFactory implements AuthorizeNetPaymentMethodFactoryInterface
{
    use LoggerAwareTrait;

    /**@var Gateway*/
    protected $gateway;

    /**
     * @param Gateway $gateway
     */
    public function __construct(Gateway $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * {@inheritDoc}
     */
    public function create(AuthorizeNetConfigInterface $config)
    {
        $method = new AuthorizeNetPaymentMethod($this->gateway, $config);
        if ($this->logger) {
            $method->setLogger($this->logger);
        }
        return $method;
    }
}
