<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Shipping address options
 */
class ShippingAddress extends AbstractOption
{
    public const SHIPTOFIRSTNAME = 'SHIPTOFIRSTNAME';
    public const SHIPTOLASTNAME = 'SHIPTOLASTNAME';
    public const SHIPTOSTREET = 'SHIPTOSTREET';
    public const SHIPTOSTREET2 = 'SHIPTOSTREET2';
    public const SHIPTOCITY = 'SHIPTOCITY';
    public const SHIPTOSTATE = 'SHIPTOSTATE';
    public const SHIPTOZIP = 'SHIPTOZIP';
    public const SHIPTOCOUNTRY = 'SHIPTOCOUNTRY';
    public const SHIPTOEMAIL = 'SHIPTOEMAIL';
    public const SHIPTOMIDDLENAME = 'SHIPTOMIDDLENAME';
    public const SHIPTOCOMPANY = 'SHIPTOCOMPANY';
    public const SHIPTOPHONE = 'SHIPTOPHONE';

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
