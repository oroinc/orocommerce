<?php

namespace Oro\Bundle\MoneyOrderBundle\Method;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\Action\CaptureActionInterface;
use Oro\Bundle\PaymentBundle\Method\Action\PurchaseActionInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

/**
 * Implements Money Order payment method
 */
class MoneyOrder implements PaymentMethodInterface, CaptureActionInterface, PurchaseActionInterface
{
    /**
     * @var MoneyOrderConfigInterface
     */
    protected $config;

    /**
     * @param MoneyOrderConfigInterface $config
     */
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

    public function getSourceAction(): string
    {
        return self::PENDING;
    }

    public function useSourcePaymentTransaction(): bool
    {
        return true;
    }

    public function purchase(PaymentTransaction $paymentTransaction): array
    {
        $paymentTransaction
            ->setAction($this->getSourceAction())
            ->setSuccessful(true)
            ->setActive(true);

        return ['successful' => true];
    }

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
