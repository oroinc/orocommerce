<?php

namespace OroB2B\Bundle\PaymentBundle\Form;

use Symfony\Component\Form\FormTypeInterface;
use OroB2B\Bundle\PaymentBundle\Form\Type\AbstractPaymentMethodType;

class PaymentMethodTypeRegistry
{
    /**
     * @var FormTypeInterface[]
     */
    protected $paymentTypes = [];

    /**
     * Add payment method type to the registry
     *
     * @param AbstractPaymentMethodType $paymentType
     */
    public function addPaymentMethodType(AbstractPaymentMethodType $paymentType)
    {
        if (array_key_exists($paymentType->getName(), $this->paymentTypes)) {
            throw new \LogicException(
                sprintf('Payment method type with name "%s" already registered', $paymentType->getName())
            );
        }
        $this->paymentTypes[$paymentType->getName()] = $paymentType;
    }

    /**
     * @return AbstractPaymentMethodType[]
     */
    public function getPaymentMethodTypes()
    {
        return $this->paymentTypes;
    }

    /**
     * Get payment method type by name
     *
     * @param string $name
     * @return AbstractPaymentMethodType
     * @throws \LogicException Throw exception when provider with specified name not found
     */
    public function getPaymentMethodType($name)
    {
        if (!array_key_exists($name, $this->paymentTypes)) {
            throw new \LogicException(
                sprintf('Payment method type with name "%s" does not exist', $name)
            );
        }

        return $this->paymentTypes[$name];
    }
}
