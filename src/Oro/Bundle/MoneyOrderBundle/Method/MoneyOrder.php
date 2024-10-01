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

    #[\Override]
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        if (!method_exists($this, $action)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" payment method "%s" action is not supported', $this->getIdentifier(), $action)
            );
        }

        return $this->$action($paymentTransaction);
    }

    #[\Override]
    public function getSourceAction(): string
    {
        return self::PENDING;
    }

    #[\Override]
    public function useSourcePaymentTransaction(): bool
    {
        return true;
    }

    #[\Override]
    public function purchase(PaymentTransaction $paymentTransaction): array
    {
        $paymentTransaction
            ->setAction($this->getSourceAction())
            ->setSuccessful(true)
            ->setActive(true);

        return ['successful' => true];
    }

    #[\Override]
    public function capture(PaymentTransaction $paymentTransaction): array
    {
        $paymentTransaction
            ->setAction(self::CAPTURE)
            ->setSuccessful(true)
            ->setActive(true);

        return ['successful' => true];
    }

    #[\Override]
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    #[\Override]
    public function isApplicable(PaymentContextInterface $context)
    {
        return true;
    }

    #[\Override]
    public function supports($actionName)
    {
        return \in_array($actionName, [self::PURCHASE, self::CAPTURE], true);
    }
}
