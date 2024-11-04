<?php

namespace Oro\Bundle\PayPalBundle\PayPal\Payflow\Option;

/**
 * Billing address options
 */
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
    const BILLTOEMAIL = 'BILLTOEMAIL';
    const BILLTOMIDDLENAME = 'BILLTOMIDDLENAME';
    const BILLTOCOMPANY = 'BILLTOCOMPANY';
    const BILLTOPHONENUM = 'BILLTOPHONENUM';

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
            BillingAddress::BILLTOFIRSTNAME,
            BillingAddress::BILLTOLASTNAME,
            BillingAddress::BILLTOSTREET,
            BillingAddress::BILLTOSTREET2,
            BillingAddress::BILLTOCITY,
            BillingAddress::BILLTOSTATE,
            BillingAddress::BILLTOZIP,
            BillingAddress::BILLTOCOUNTRY,
            BillingAddress::BILLTOEMAIL,
            BillingAddress::BILLTOMIDDLENAME,
            BillingAddress::BILLTOCOMPANY,
            BillingAddress::BILLTOPHONENUM,
        ];
    }
}
