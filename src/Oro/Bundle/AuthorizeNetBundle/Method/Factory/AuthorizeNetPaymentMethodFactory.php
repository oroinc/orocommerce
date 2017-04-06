<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Factory;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Gateway;
use Oro\Bundle\AuthorizeNetBundle\Method\AuthorizeNetPaymentMethod;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Symfony\Component\Routing\RouterInterface;

class AuthorizeNetPaymentMethodFactory implements AuthorizeNetPaymentMethodFactoryInterface
{
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
        return new AuthorizeNetPaymentMethod(
            $this->gateway,
            $config
        );
    }
}
