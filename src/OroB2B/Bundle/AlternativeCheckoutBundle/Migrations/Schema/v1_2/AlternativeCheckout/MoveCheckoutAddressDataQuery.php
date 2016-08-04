<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Migrations\Schema\v1_2\AlternativeCheckout;

use OroB2B\Bundle\AlternativeCheckoutBundle\Migrations\Schema\v1_2\Checkout\MoveCheckoutAddressDataQuery as BaseQuery;

class MoveCheckoutAddressDataQuery extends BaseQuery
{
    /**
     * {@inheritdoc}
     */
    protected function getSourceTableName()
    {
        return 'orob2b_alternative_checkout';
    }
}
