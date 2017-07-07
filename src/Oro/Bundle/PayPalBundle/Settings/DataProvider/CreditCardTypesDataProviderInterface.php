<?php

namespace Oro\Bundle\PayPalBundle\Settings\DataProvider;

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
