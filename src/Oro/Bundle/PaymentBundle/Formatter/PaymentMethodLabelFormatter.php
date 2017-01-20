<?php

namespace Oro\Bundle\PaymentBundle\Formatter;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProvidersRegistryInterface;

class PaymentMethodLabelFormatter
{
    /**
     * @var PaymentMethodViewProvidersRegistryInterface
     */
    protected $paymentMethodViewRegistry;

    /**
     * @param PaymentMethodViewProvidersRegistryInterface $paymentMethodViewRegistry
     */
    public function __construct(PaymentMethodViewProvidersRegistryInterface $paymentMethodViewRegistry)
    {
        $this->paymentMethodViewRegistry = $paymentMethodViewRegistry;
    }

    /**
     * @param string $paymentMethod
     * @param bool $shortLabel
     * @return string
     */
    public function formatPaymentMethodLabel($paymentMethod, $shortLabel = true)
    {
        try {
            $paymentMethodView = $this->paymentMethodViewRegistry->getPaymentMethodView($paymentMethod);

            return $shortLabel ? $paymentMethodView->getShortLabel() : $paymentMethodView->getLabel();
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }

    /**
     * @param string $paymentMethod
     * @return string
     */
    public function formatPaymentMethodAdminLabel($paymentMethod)
    {
        try {
            return $this->paymentMethodViewRegistry->getPaymentMethodView($paymentMethod)->getAdminLabel();
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }
}
