<?php

namespace Oro\Bundle\PaymentBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

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
     * @param PaymentMethodViewInterface $paymentType
     */
    public function addPaymentMethodView(PaymentMethodViewInterface $paymentType)
    {
        $this->paymentMethodViews[$paymentType->getPaymentMethodType()] = $paymentType;
    }

    /**
     * @param array $methodTypes
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews(array $methodTypes)
    {
        return array_map(function ($methodType) {
            return $this->getPaymentMethodView($methodType);
        }, $methodTypes);
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
