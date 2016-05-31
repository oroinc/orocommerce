<?php

namespace OroB2B\Bundle\PaymentBundle\Twig;

use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class PaymentMethodExtension extends \Twig_Extension
{
    const PAYMENT_METHOD_EXTENSION_NAME = 'orob2b_payment_method';

    /** @var  PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /** @var  PaymentMethodViewRegistry */
    protected $paymentMethodViewRegistry;

    /**
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param PaymentMethodViewRegistry $paymentMethodViewRegistry
     */
    public function __construct(
        PaymentTransactionProvider $paymentTransactionProvider,
        PaymentMethodViewRegistry $paymentMethodViewRegistry
    ) {
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->paymentMethodViewRegistry = $paymentMethodViewRegistry;
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
            new \Twig_SimpleFunction('get_payment_method_label', [$this, 'getPaymentMethodLabel']),
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
            /** @var PaymentMethodViewInterface $paymentMethodView */
            $paymentMethods[] = $this->getPaymentMethodLabel($paymentTransaction->getPaymentMethod());
        }

        return $paymentMethods;
    }

    /**
     * @param string $paymentMethod
     * @return array
     */
    public function getPaymentMethodLabel($paymentMethod)
    {
        /** @var PaymentMethodViewInterface $paymentMethodView */
        try {
            $paymentMethodView = $this->paymentMethodViewRegistry->getPaymentMethodView($paymentMethod);

            return $paymentMethodView->getLabel();
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }
}
