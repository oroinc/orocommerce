<?php

namespace Oro\Bundle\PaymentBundle\Formatter;

use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;

/**
 * Formats payment method labels for display in the user interface.
 *
 * This formatter retrieves payment method views and provides their labels in various
 * formats (short label, full label, admin label), with graceful fallback to empty string
 * if the payment method is not found.
 */
class PaymentMethodLabelFormatter
{
    /**
     * @var PaymentMethodViewProviderInterface
     */
    protected $paymentMethodViewProvider;

    public function __construct(PaymentMethodViewProviderInterface $paymentMethodViewProvider)
    {
        $this->paymentMethodViewProvider = $paymentMethodViewProvider;
    }

    /**
     * @param string $paymentMethod
     * @param bool   $shortLabel
     *
     * @return string
     */
    public function formatPaymentMethodLabel($paymentMethod, $shortLabel = true)
    {
        try {
            $paymentMethodView = $this->paymentMethodViewProvider->getPaymentMethodView($paymentMethod);

            return $shortLabel ? $paymentMethodView->getShortLabel() : $paymentMethodView->getLabel();
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }

    /**
     * @param string $paymentMethod
     *
     * @return string
     */
    public function formatPaymentMethodAdminLabel($paymentMethod)
    {
        try {
            return $this->paymentMethodViewProvider->getPaymentMethodView($paymentMethod)->getAdminLabel();
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }
}
