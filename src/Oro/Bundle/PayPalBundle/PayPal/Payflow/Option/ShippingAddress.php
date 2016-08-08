<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

class ShippingAddress extends AbstractOption
{
    const SHIPTOFIRSTNAME = 'SHIPTOFIRSTNAME';
    const SHIPTOLASTNAME = 'SHIPTOLASTNAME';
    const SHIPTOSTREET = 'SHIPTOSTREET';
    const SHIPTOSTREET2 = 'SHIPTOSTREET2';
    const SHIPTOCITY = 'SHIPTOCITY';
    const SHIPTOSTATE = 'SHIPTOSTATE';
    const SHIPTOZIP = 'SHIPTOZIP';
    const SHIPTOCOUNTRY = 'SHIPTOCOUNTRY';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $keys = $this->getAllKeys();

        $resolver
            ->setDefined($keys)
            ->setAllowedTypes(array_fill_keys($keys, 'string'));
    }

    /**
     * @return string[]
     */
    protected function getAllKeys()
    {
        return [
            ShippingAddress::SHIPTOFIRSTNAME,
            ShippingAddress::SHIPTOLASTNAME,
            ShippingAddress::SHIPTOSTREET,
            ShippingAddress::SHIPTOSTREET2,
            ShippingAddress::SHIPTOCITY,
            ShippingAddress::SHIPTOSTATE,
            ShippingAddress::SHIPTOZIP,
            ShippingAddress::SHIPTOCOUNTRY,
        ];
    }
}
