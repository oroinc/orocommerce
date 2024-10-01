<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Shipping address options
 */
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
    const SHIPTOEMAIL = 'SHIPTOEMAIL';
    const SHIPTOMIDDLENAME = 'SHIPTOMIDDLENAME';
    const SHIPTOCOMPANY = 'SHIPTOCOMPANY';
    const SHIPTOPHONE = 'SHIPTOPHONE';

    #[\Override]
    public function configureOption(OptionsResolver $resolver)
    {
        $keys = $this->getAllKeys();

        $resolver->setDefined($keys);

        foreach ($keys as $key) {
            $resolver->setAllowedTypes($key, 'string');
        }
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
            ShippingAddress::SHIPTOEMAIL,
            ShippingAddress::SHIPTOMIDDLENAME,
            ShippingAddress::SHIPTOCOMPANY,
            ShippingAddress::SHIPTOPHONE,
        ];
    }
}
