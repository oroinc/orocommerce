<?php

namespace Oro\Bundle\PaymentBundle\Formatter;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProvidersRegistry;

class PaymentMethodLabelFormatter
{
    /**
     * @var PaymentMethodViewProvidersRegistry
     */
    protected $paymentMethodViewRegistry;

    /**
     * @param PaymentMethodViewProvidersRegistry $paymentMethodViewRegistry
     */
    public function __construct(PaymentMethodViewProvidersRegistry $paymentMethodViewRegistry)
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
