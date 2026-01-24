<?php

namespace Oro\Bundle\PayPalBundle\Settings\DataProvider;

/**
 * Defines the contract for providing available credit card types.
 *
 * Returns the list of supported credit card types and the default card types
 * for payment processing.
 */
interface CreditCardTypesDataProviderInterface
{
    /**
     * @return string[]
     */
    public function getCardTypes();

    /**
     * @return string[]
     */
    public function getDefaultCardTypes();
}
