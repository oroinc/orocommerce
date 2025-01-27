<?php

namespace Oro\Bundle\CheckoutBundle\Provider\AddressValidation;

use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Symfony\Component\Form\FormInterface;

/**
 * Extracts a shipping address for address validation from the address form.
 */
class CheckoutAddressFormShippingAddressProvider implements CheckoutAddressFormAddressProviderInterface
{
    #[\Override]
    public function getAddress(FormInterface $addressForm): ?OrderAddress
    {
        $rootForm = $addressForm->getRoot();
        if (!$rootForm->has('ship_to_billing_address')) {
            return $addressForm->getData();
        }

        $addressFormConfig = $addressForm->getConfig();
        if ($rootForm->get('ship_to_billing_address')->getData()) {
            return $addressFormConfig->getOption('object')->getBillingAddress();
        }

        return $addressForm->getData();
    }
}
