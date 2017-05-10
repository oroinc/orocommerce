<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\Factory;

use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Gateway;
use Oro\Bundle\AuthorizeNetBundle\Method\AuthorizeNetPaymentMethod;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;

class AuthorizeNetPaymentMethodFactory implements AuthorizeNetPaymentMethodFactoryInterface
{
    use LoggerAwareTrait;

    /** @var Gateway */
    protected $gateway;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param Gateway $gateway
     * @param RequestStack $requestStack
     */
    public function __construct(Gateway $gateway, RequestStack $requestStack)
    {
        $this->gateway = $gateway;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function create(AuthorizeNetConfigInterface $config)
    {
        $method = new AuthorizeNetPaymentMethod($this->gateway, $config, $this->requestStack);
        if ($this->logger) {
            $method->setLogger($this->logger);
        }

        return $method;
    }
}
