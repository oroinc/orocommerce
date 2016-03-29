<?php

namespace OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

use Symfony\Component\OptionsResolver\OptionsResolver;

class BillingAddress extends AbstractOption
{
    const BILLTOFIRSTNAME = 'BILLTOFIRSTNAME';
    const BILLTOLASTNAME = 'BILLTOLASTNAME';
    const BILLTOSTREET = 'BILLTOSTREET';
    const BILLTOSTREET2 = 'BILLTOSTREET2';
    const BILLTOCITY = 'BILLTOCITY';
    const BILLTOSTATE = 'BILLTOSTATE';
    const BILLTOZIP = 'BILLTOZIP';
    const BILLTOCOUNTRY = 'BILLTOCOUNTRY';

    /** {@inheritdoc} */
    public function configureOption(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(
                [
                    BillingAddress::BILLTOFIRSTNAME,
                    BillingAddress::BILLTOLASTNAME,
                    BillingAddress::BILLTOSTREET,
                    BillingAddress::BILLTOSTREET2,
                    BillingAddress::BILLTOCITY,
                    BillingAddress::BILLTOSTATE,
                    BillingAddress::BILLTOZIP,
                    BillingAddress::BILLTOCOUNTRY,
                ]
            )
            ->setAllowedTypes(
                [
                    BillingAddress::BILLTOFIRSTNAME => 'string',
                    BillingAddress::BILLTOLASTNAME => 'string',
                    BillingAddress::BILLTOSTREET => 'string',
                    BillingAddress::BILLTOSTREET2 => 'string',
                    BillingAddress::BILLTOCITY => 'string',
                    BillingAddress::BILLTOSTATE => 'string',
                    BillingAddress::BILLTOZIP => 'string',
                    BillingAddress::BILLTOCOUNTRY => 'string',
                ]
            );
    }
}
