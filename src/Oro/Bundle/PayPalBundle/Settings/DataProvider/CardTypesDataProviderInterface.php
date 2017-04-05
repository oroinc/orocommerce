<?php

namespace Oro\Bundle\PayPalBundle\Settings\DataProvider;

/**
 * @deprecated since v1.2, to be removed in v1.3. Use CreditCardTypesDataProviderInterface instead.
 */
interface CardTypesDataProviderInterface
{
    /**
     * @return string[]
     */
    public function getCardTypes();
}
