<?php

namespace OroB2B\Bundle\PaymentBundle\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class PaymentMethodExtension extends \Twig_Extension
{
    const PAYMENT_METHOD_EXTENSION_NAME = 'orob2b_payment_method';

    /** @var  PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param ConfigManager $configManager
     */
    public function __construct(PaymentTransactionProvider $paymentTransactionProvider, ConfigManager $configManager)
    {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::PAYMENT_METHOD_EXTENSION_NAME;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_payment_methods', [$this, 'getPaymentMethods']),
        ];
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getPaymentMethods($entity)
    {
        $paymentTransactions = $this->paymentTransactionProvider->getPaymentTransactions($entity);
        $paymentMethods = [];
        foreach ($paymentTransactions as $paymentTransaction) {
            $paymentMethods[] = $this->configManager
                ->get(sprintf('orob2b_payment.%s_label', $paymentTransaction->getPaymentMethod()));
        }

        return $paymentMethods;
    }
}
