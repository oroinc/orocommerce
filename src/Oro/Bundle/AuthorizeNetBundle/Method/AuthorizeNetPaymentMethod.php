<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\Routing\RouterInterface;

class AuthorizeNetPaymentMethod implements PaymentMethodInterface
{
    /** @var RouterInterface */
    protected $router;

    /** @var AuthorizeNetConfigInterface */
    protected $config;

    /**
     * @param AuthorizeNetConfigInterface $config
     * @param RouterInterface $router
     */
    public function __construct(AuthorizeNetConfigInterface $config, RouterInterface $router)
    {
        $this->config = $config;
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        // TODO: Implement execute() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        return true;// TODO: Implement isApplicable() method.
    }
    /**
     * {@inheritdoc}
     */
    public function supports($actionName)
    {
        return true;// TODO: Implement supports() method.
    }
}
