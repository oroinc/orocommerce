<?php

namespace Oro\Bundle\CheckoutBundle\Factory\MultiShipping;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Create checkout with specific line items from the original checkout.
 */
class CheckoutFactory implements CheckoutFactoryInterface
{
    private PropertyAccessorInterface $propertyAccessor;
    protected array $fieldsMap = [
        'owner',
        'billingAddress',
        'currency',
        'customerNotes',
        'customer',
        'customerUser',
        'deleted',
        'completed',
        'registeredCustomerUser',
        'shippingAddress',
        'source',
        'website',
        'shippingMethod',
        'shippingMethodType',
        'paymentMethod',
        'poNumber',
        'shipUntil',
        'organization'
    ];

    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    public function createCheckout(Checkout $checkoutSource, iterable $lineItems): Checkout
    {
        $checkout = $this->createCheckoutFromSource($checkoutSource);

        foreach ($lineItems as $lineItem) {
            $lineItemDuplicate = clone $lineItem;
            $checkout->addLineItem($lineItemDuplicate);
        }

        return $checkout;
    }

    protected function getFieldsMap()
    {
        return $this->fieldsMap;
    }

    private function createCheckoutFromSource(Checkout $checkoutSource): Checkout
    {
        $checkout = new Checkout();
        $fieldsMap = $this->getFieldsMap();

        foreach ($fieldsMap as $field) {
            $this->copyFieldValues($checkoutSource, $checkout, $field);
        }


        return $checkout;
    }

    private function copyFieldValues(Checkout $checkoutSource, Checkout $checkout, string $field): void
    {
        try {
            $fieldValue = $this->propertyAccessor->getValue($checkoutSource, $field);
            $this->propertyAccessor->setValue($checkout, $field, $fieldValue);
        } catch (NoSuchPropertyException $e) {
            // Skip field value copy.
        }
    }
}
