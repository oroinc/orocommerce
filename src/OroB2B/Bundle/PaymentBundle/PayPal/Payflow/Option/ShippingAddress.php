<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $resolver
            ->setDefined(
                [
                    ShippingAddress::SHIPTOFIRSTNAME,
                    ShippingAddress::SHIPTOLASTNAME,
                    ShippingAddress::SHIPTOSTREET,
                    ShippingAddress::SHIPTOSTREET2,
                    ShippingAddress::SHIPTOCITY,
                    ShippingAddress::SHIPTOSTATE,
                    ShippingAddress::SHIPTOZIP,
                    ShippingAddress::SHIPTOCOUNTRY,
                ]
            )
            ->setAllowedTypes(
                [
                    ShippingAddress::SHIPTOFIRSTNAME => 'string',
                    ShippingAddress::SHIPTOLASTNAME => 'string',
                    ShippingAddress::SHIPTOSTREET => 'string',
                    ShippingAddress::SHIPTOSTREET2 => 'string',
                    ShippingAddress::SHIPTOCITY => 'string',
                    ShippingAddress::SHIPTOSTATE => 'string',
                    ShippingAddress::SHIPTOZIP => 'string',
                    ShippingAddress::SHIPTOCOUNTRY => 'string',
                ]
            );
    }
}
