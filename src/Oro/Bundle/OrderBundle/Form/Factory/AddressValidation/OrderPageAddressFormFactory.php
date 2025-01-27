<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Form\Factory\AddressValidation;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressValidationBundle\Form\Factory\AddressValidationAddressFormFactoryInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\RequestHandler\OrderRequestHandler;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Creates an address form used for the address validation on the order create/edit page.
 */
class OrderPageAddressFormFactory implements AddressValidationAddressFormFactoryInterface
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private OrderRequestHandler $orderRequestHandler,
        private PropertyAccessorInterface $propertyAccessor,
        private string $addressFieldName
    ) {
    }

    #[\Override]
    public function createAddressForm(Request $request, AbstractAddress $address = null): FormInterface
    {
        $order = (new Order())
            ->setCustomer($this->orderRequestHandler->getCustomer())
            ->setCustomerUser($this->orderRequestHandler->getCustomerUser());

        $this->propertyAccessor->setValue($order, $this->addressFieldName, $address);

        return $this->formFactory
            ->create(OrderType::class, $order)
            ->get($this->addressFieldName);
    }
}
