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
     * @param array $context
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews(array $context = [])
    {
        $paymentMethodViews = [];

        foreach ($this->paymentMethodViews as $paymentMethodView) {
            $paymentMethod = $this->paymentMethodRegistry->getPaymentMethod($paymentMethodView->getPaymentMethodType());
            if (!$paymentMethod->isEnabled()) {
                continue;
            }

            if (!$paymentMethod->isApplicable($context)) {
                continue;
            }

            $paymentMethodViews[$paymentMethodView->getOrder()][] = $paymentMethodView;
        }

        ksort($paymentMethodViews);
        if ($paymentMethodViews) {
            $paymentMethodViews = call_user_func_array('array_merge', $paymentMethodViews);
        }

        return $paymentMethodViews;
    }

    /**
     * @param string $paymentMethod
     * @return PaymentMethodViewInterface
     */
    public function getPaymentMethodView($paymentMethod)
    {
        if (!isset($this->paymentMethodViews[$paymentMethod])) {
            throw new \InvalidArgumentException(
                sprintf('There is no payment method view for "%s"', $paymentMethod)
            );
        }

        return $this->paymentMethodViews[$paymentMethod];
    }
}
