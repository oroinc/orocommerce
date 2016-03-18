<?php

namespace OroB2B\Bundle\PaymentBundle\Form;

use Symfony\Component\Form\FormTypeInterface;

class PaymentMethodTypeRegistry
{
    /**
     * @var FormTypeInterface[]
     */
    protected $paymentTypes = [];

    /**
     * Add payment method type to the registry
     *
     * @param FormTypeInterface $paymentType
     */
    public function addPaymentMethodType(FormTypeInterface $paymentType)
    {
        if (array_key_exists($paymentType->getName(), $this->paymentTypes)) {
            throw new \LogicException(
                sprintf('Tax payment type with name "%s" already registered', $paymentType->getName())
            );
        }
        $this->paymentTypes[$paymentType->getName()] = $paymentType;
    }

    /**
     * @return FormTypeInterface[]
     */
    public function getPaymentMethodTypes()
    {
        return $this->paymentTypes;
    }

    /**
     * Get payment method type by name
     *
     * @param string $name
     * @return FormTypeInterface
     * @throws \LogicException Throw exception when provider with specified name not found
     */
    public function getPaymentMethodType($name)
    {
        if (!array_key_exists($name, $this->paymentTypes)) {
            throw new \LogicException(
                sprintf('Tax payment type with name "%s" does not exist', $name)
            );
        }

        return $this->paymentTypes[$name];
    }
}
