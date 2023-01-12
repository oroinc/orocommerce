<?php

namespace Oro\Bundle\CheckoutBundle\Factory\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Creates a checkout with specific line items from a specific checkout.
 */
class CheckoutFactory implements CheckoutFactoryInterface
{
    private array $fields;
    private PropertyAccessorInterface $propertyAccessor;

    public function __construct(array $fields, PropertyAccessorInterface $propertyAccessor)
    {
        $this->fields = $fields;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritDoc}
     */
    public function createCheckout(Checkout $source, iterable $lineItems): Checkout
    {
        $checkout = new Checkout();
        foreach ($this->fields as $field) {
            $this->copyFieldValue($source, $checkout, $field);
        }
        foreach ($lineItems as $lineItem) {
            $checkout->addLineItem(clone $lineItem);
        }

        return $checkout;
    }

    private function copyFieldValue(Checkout $source, Checkout $checkout, string $field): void
    {
        try {
            $this->propertyAccessor->setValue(
                $checkout,
                $field,
                $this->propertyAccessor->getValue($source, $field)
            );
        } catch (NoSuchPropertyException $e) {
            // Skip field value copy.
        }
    }
}
