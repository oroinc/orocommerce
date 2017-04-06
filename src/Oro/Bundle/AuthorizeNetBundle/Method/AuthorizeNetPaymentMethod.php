<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\Routing\RouterInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class AuthorizeNetPaymentMethod implements PaymentMethodInterface
{
    const ZERO_AMOUNT = 0;

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
        if (!$this->supports($action)) {
            throw new \InvalidArgumentException(sprintf('Unsupported action "%s"', $action));
        }

        return $this->{$action}($paymentTransaction) ?: [];
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
        return in_array(
            $actionName,
            [self::AUTHORIZE, self::CAPTURE, self::CHARGE, self::PURCHASE, self::VALIDATE],
            true
        );
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     */
    public function validate(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction
            ->setAmount(self::ZERO_AMOUNT)
            ->setCurrency(Option\Currency::US_DOLLAR)
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setActive(true)
            ->setSuccessful(true);
    }
}
