<?php

namespace Oro\Bundle\CheckoutBundle\Form\DataTransformer;

use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Form\Type\OrderAddressSelectType;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Transforms OrderAddress to corresponding address identifier.
 */
class OrderAddressToAddressIdentifierViewTransformer implements DataTransformerInterface
{
    private OrderAddressManager $addressManager;

    private PropertyAccessorInterface $propertyAccessor;

    /** @var string[] */
    private array $requiredFields;

    public function __construct(
        OrderAddressManager $addressManager,
        PropertyAccessorInterface $propertyAccessor,
        array $requiredFields
    ) {
        $this->addressManager = $addressManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->requiredFields = $requiredFields;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($address)
    {
        // Transforms OrderAddress to an address key.
        if ($address instanceof OrderAddress) {
            if ($address->getCustomerAddress()) {
                $identifier = $this->addressManager->getIdentifier($address->getCustomerAddress());
            } elseif ($address->getCustomerUserAddress()) {
                $identifier = $this->addressManager->getIdentifier($address->getCustomerUserAddress());
            } elseif (!$this->addressIsEmpty($address)) {
                // Select new address if it was already created by customer.
                $identifier = OrderAddressSelectType::ENTER_MANUALLY;
            }

            return $identifier ?? '';
        }

        return $address;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        // Do nothing, it is expected that value will be transformed by the ChoiceToValueTransformer.
        return $value;
    }

    /**
     * Check if new address is fulfilled with some data. Assume address is not empty if one of the required fields
     * is not empty.
     */
    private function addressIsEmpty(OrderAddress $address): bool
    {
        foreach ($this->requiredFields as $field) {
            try {
                $value = $this->propertyAccessor->getValue($address, $field);
            } catch (NoSuchPropertyException $e) {
                $value = null;
            }

            if (null !== $value) {
                return false;
            }
        }

        return true;
    }
}
