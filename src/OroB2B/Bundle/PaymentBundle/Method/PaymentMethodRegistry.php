<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

class PaymentMethodRegistry
{
    /** @var PaymentMethodInterface[] */
    protected $paymentMethods = [];

    /**
     * @param PaymentMethodInterface $paymentMethod
     */
    public function addPaymentMethod(PaymentMethodInterface $paymentMethod)
    {
        $this->paymentMethods[$paymentMethod->getType()] = $paymentMethod;
    }

    /**
     * @param string $type
     * @return PaymentMethodInterface
     */
    public function getPaymentMethod($type)
    {
        $type = (string)$type;

        if (array_key_exists($type, $this->paymentMethods)) {
            return $this->paymentMethods[$type];
        }

        throw new \InvalidArgumentException(
            sprintf(
                'Payment method with "%s" is missing. Registered payment methods are "%s"',
                $type,
                implode(', ', array_keys($this->paymentMethods))
            )
        );
    }

    /**
     * @return PaymentMethodInterface[]
     */
    public function getPaymentMethods()
    {
        return $this->paymentMethods;
    }
}
