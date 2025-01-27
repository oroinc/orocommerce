<?php

namespace Oro\Bundle\CheckoutBundle\Provider\AddressValidation;

use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Symfony\Component\Form\FormInterface;

/**
 * Extracts an address for address validation from the address form.
 */
class CheckoutAddressFormAddressProvider implements CheckoutAddressFormAddressProviderInterface
{
    #[\Override]
    public function getAddress(FormInterface $addressForm): ?OrderAddress
    {
        return $addressForm->getData();
    }
}
