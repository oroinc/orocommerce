<?php

namespace Oro\Bundle\MoneyOrderBundle\Method;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodWithPostponedCaptureInterface;

/**
 * Implements Money Order payment method
 */
class MoneyOrder implements PaymentMethodWithPostponedCaptureInterface
{
    /**
     * @var MoneyOrderConfigInterface
     */
    protected $config;

    public function __construct(MoneyOrderConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        if (!method_exists($this, $action)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" payment method "%s" action is not supported', $this->getIdentifier(), $action)
            );
        }

        return $this->$action($paymentTransaction);
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceAction(): string
    {
        return self::PENDING;
    }

    /**
     * {@inheritdoc}
     */
    public function useSourcePaymentTransaction(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function purchase(PaymentTransaction $paymentTransaction): array
    {
        $paymentTransaction
            ->setAction($this->getSourceAction())
            ->setSuccessful(true)
            ->setActive(true);

        return ['successful' => true];
    }

    /**
     * {@inheritdoc}
     */
    public function capture(PaymentTransaction $paymentTransaction): array
    {
        $paymentTransaction
            ->setAction(self::CAPTURE)
            ->setSuccessful(true)
            ->setActive(true);

        return ['successful' => true];
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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionName)
    {
        return \in_array($actionName, [self::PURCHASE, self::CAPTURE], true);
    }
}
