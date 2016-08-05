<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Migrations\Schema\v1_2;

use OroB2B\Bundle\CheckoutBundle\Migrations\Schema\v1_2\MoveCheckoutAddressDataQuery as BaseQuery;

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
