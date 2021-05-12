<?php

namespace Oro\Bundle\OrderBundle\Validator\Constraints;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Provider\AddressProviderInterface;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a customer user address or a customer address is allowed to use for an order.
 */
class CustomerOrUserAddressGrantedValidator extends ConstraintValidator
{
    /** @var AddressProviderInterface */
    private $addressProvider;

    public function __construct(AddressProviderInterface $addressProvider)
    {
        $this->addressProvider = $addressProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof CustomerOrUserAddressGranted) {
            throw new UnexpectedTypeException($constraint, CustomerOrUserAddressGranted::class);
        }

        if (!$value instanceof Order) {
            return;
        }

        $address = $this->getAddress($value, $constraint->addressType);
        if (null === $address) {
            return;
        }

        $this->validateOrderAddress($address, $value, $constraint);
    }

    private function validateOrderAddress(
        OrderAddress $address,
        Order $order,
        CustomerOrUserAddressGranted $constraint
    ): void {
        $customerUserAddress = $address->getCustomerUserAddress();
        if (null !== $customerUserAddress && null !== $order->getCustomerUser()) {
            $this->validateAddress(
                $customerUserAddress->getId(),
                $this->addressProvider->getCustomerUserAddresses($order->getCustomerUser(), $constraint->addressType),
                sprintf('%sAddress.%s', $constraint->addressType, 'customerUserAddress'),
                $constraint->message
            );
        }

        $customerAddress = $address->getCustomerAddress();
        if (null !== $customerAddress && null !== $order->getCustomer()) {
            $this->validateAddress(
                $customerAddress->getId(),
                $this->addressProvider->getCustomerAddresses($order->getCustomer(), $constraint->addressType),
                sprintf('%sAddress.%s', $constraint->addressType, 'customerAddress'),
                $constraint->message
            );
        }
    }

    private function validateAddress(
        int $addressId,
        array $availableAddresses,
        string $addressPath,
        string $message
    ): void {
        foreach ($availableAddresses as $availableAddress) {
            if ($addressId === $availableAddress->getId()) {
                return;
            }
        }

        $this->context->buildViolation($message)
            ->atPath($addressPath)
            ->addViolation();
    }

    private function getAddress(Order $order, string $addressType): ?OrderAddress
    {
        switch ($addressType) {
            case OrderAddressProvider::ADDRESS_TYPE_BILLING:
                return $order->getBillingAddress();
            case OrderAddressProvider::ADDRESS_TYPE_SHIPPING:
                return $order->getShippingAddress();
            default:
                return null;
        }
    }
}
