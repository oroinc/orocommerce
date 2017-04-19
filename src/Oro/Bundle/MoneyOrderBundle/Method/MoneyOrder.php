<?php

namespace Oro\Bundle\MoneyOrderBundle\Method;

use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class MoneyOrder implements PaymentMethodInterface
{
    const TYPE = 'money_order';

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
        $paymentTransaction->setSuccessful(true);

        return [];
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
        return $actionName === self::PURCHASE;
    }
}
