<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

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
            BillingAddress::BILLTOFIRSTNAME,
            BillingAddress::BILLTOLASTNAME,
            BillingAddress::BILLTOSTREET,
            BillingAddress::BILLTOSTREET2,
            BillingAddress::BILLTOCITY,
            BillingAddress::BILLTOSTATE,
            BillingAddress::BILLTOZIP,
            BillingAddress::BILLTOCOUNTRY,
        ];
    }
}
