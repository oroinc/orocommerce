<?php

namespace Oro\Bundle\CheckoutBundle\Provider\AddressValidation;

use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Symfony\Component\Form\FormInterface;

/**
 * Extracts an address for address validation from the address form.
 */
interface CheckoutAddressFormAddressProviderInterface
{
    public function getAddress(FormInterface $addressForm): ?OrderAddress;
}
