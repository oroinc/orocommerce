<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

class PaymentMethodViewRegistry
{
    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /** @var PaymentMethodViewInterface[] */
    protected $paymentMethodViews = [];

    /**
     * @param PaymentMethodRegistry $paymentMethodRegistry
     */
    public function __construct(PaymentMethodRegistry $paymentMethodRegistry)
    {
        $this->paymentMethodRegistry = $paymentMethodRegistry;
    }

    /**
     * Add payment method type to the registry
     *
     * @param PaymentMethodViewInterface $paymentType
     */
    public function addPaymentMethodView(PaymentMethodViewInterface $paymentType)
    {
        $this->paymentMethodViews[$paymentType->getPaymentMethodType()] = $paymentType;
    }

    /**
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews()
    {
        $paymentMethodViews = [];

        foreach ($this->paymentMethodViews as $paymentMethodView) {
            $paymentMethod = $this->paymentMethodRegistry->getPaymentMethod($paymentMethodView->getPaymentMethodType());
            if (!$paymentMethod->isEnabled()) {
                continue;
            }

            $paymentMethodViews[$paymentMethodView->getOrder()] = $paymentMethodView;
        }

        ksort($paymentMethodViews);

        $orderedPaymentMethodViews = [];
        /** @var PaymentMethodViewInterface $paymentMethodView */
        foreach ($paymentMethodViews as $paymentMethodView) {
            $orderedPaymentMethodViews[$paymentMethodView->getPaymentMethodType()] = $paymentMethodView;
        }

        return $orderedPaymentMethodViews;
    }
}
